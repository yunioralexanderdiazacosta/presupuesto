<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as HttpClient;

class DatabaseChatService
{
    /**
     * Crear cliente HTTP para OpenAI (igual que InvoiceOcrService).
     */
    private function makeHttpClient(): HttpClient
    {
        $options = [
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => (int) config('openai.request_timeout', 60),
            'headers'  => [
                'Authorization' => 'Bearer ' . config('openai.api_key'),
                'Content-Type'  => 'application/json',
            ],
        ];

        $possiblePaths = [
            base_path('cacert.pem'),
            'C:\\MAMP\\bin\\php\\php8.1.0\\extras\\ssl\\cacert.pem',
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
        ];
        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                $options['verify'] = $path;
                break;
            }
        }

        return new HttpClient($options);
    }

    /**
     * Convierte una pregunta en lenguaje natural a SQL, ejecuta la consulta
     * y devuelve una respuesta en español generada por GPT.
     *
     * @param  string  $question
     * @param  int     $teamId
     * @param  int     $seasonId
     * @return array   ['answer' => string, 'sql_preview' => string|null]
     */
    public function answer(string $question, int $teamId, int $seasonId): array
    {
        $apiKey = config('openai.api_key');
        if (!$apiKey) {
            throw new \Exception('OPENAI_API_KEY no está configurada en el archivo .env');
        }

        $schema = $this->getSchemaDescription();

        $systemPrompt = <<<PROMPT
Eres un asistente de base de datos para un sistema de gestión presupuestaria agrícola en Chile.
Tu tarea es convertir preguntas en lenguaje natural a una consulta SQL MySQL válida.

REGLAS CRÍTICAS:
1. Responde ÚNICAMENTE con un objeto JSON con este formato exacto (sin texto adicional):
   {"sql": "SELECT ...", "explanation": "descripción de la consulta"}
2. Solo puedes usar sentencias SELECT. NUNCA uses INSERT, UPDATE, DELETE, DROP, ALTER, CREATE, TRUNCATE.
3. SIEMPRE filtra por equipo y temporada usando los placeholders literales:
   - Para tablas con team_id: usa WHERE tabla.team_id = {TEAM_ID}
   - Para tablas con season_id: usa AND tabla.season_id = {SEASON_ID}
4. Para tablas sin team_id (level2s, level3s, level4s), filtra via JOIN con level1s que sí tiene team_id.
5. La tabla 'investments' solo tiene season_id (no team_id). La tabla 'products' solo tiene team_id.
6. Incluye siempre LIMIT 50 como máximo.
7. Los montos están en CLP (Pesos Chilenos).
8. Si la pregunta no puede responderse con el esquema disponible, responde:
   {"sql": null, "explanation": "No tengo datos suficientes para responder eso."}

ESQUEMA DE BASE DE DATOS:
$schema

Responde SOLO con el JSON, sin markdown, sin explicaciones adicionales.
PROMPT;

        $client = $this->makeHttpClient();

        // ── Paso 1: Pedir a GPT que genere la consulta SQL ──────────────────────
        $sqlResponse = $client->post('chat/completions', [
            'json' => [
                'model'       => 'gpt-4o-mini',
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $question],
                ],
                'temperature' => 0.1,
                'max_tokens'  => 600,
            ],
        ]);

        $sqlData    = json_decode($sqlResponse->getBody()->getContents(), true);
        $rawContent = $sqlData['choices'][0]['message']['content'] ?? '{}';

        // Limpiar posible markdown (```json ... ```)
        $rawContent = preg_replace('/^```[a-z]*\n?/i', '', trim($rawContent));
        $rawContent = preg_replace('/```$/', '', trim($rawContent));

        $parsed = json_decode(trim($rawContent), true);

        Log::info('🤖 AI Chat - SQL generado', ['raw' => $rawContent]);

        if (empty($parsed) || empty($parsed['sql'])) {
            return [
                'answer'      => $parsed['explanation'] ?? 'No pude generar una consulta para esa pregunta.',
                'sql_preview' => null,
            ];
        }

        $sql = $parsed['sql'];

        // ── Paso 2: Validar que sea un SELECT seguro ──────────────────────────
        $this->validateSql($sql);

        // ── Paso 3: Inyectar team_id y season_id (cast a int = seguro) ─────────
        $sql = str_replace('{TEAM_ID}',   (int) $teamId,   $sql);
        $sql = str_replace('{SEASON_ID}', (int) $seasonId, $sql);

        Log::info('🤖 AI Chat - SQL final a ejecutar', ['sql' => $sql]);

        // ── Paso 4: Ejecutar la consulta ──────────────────────────────────────
        $results = DB::select($sql);

        if (empty($results)) {
            return [
                'answer'      => 'No encontré registros que correspondan a tu consulta para esta temporada.',
                'sql_preview' => $sql,
            ];
        }

        // ── Paso 5: Pedir a GPT que resuma los resultados en español ──────────
        $resultsJson = json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Truncar si es muy grande para no exceder tokens
        if (strlen($resultsJson) > 8000) {
            $resultsJson = substr($resultsJson, 0, 8000) . '... [truncado]';
        }

        $answerResponse = $client->post('chat/completions', [
            'json' => [
                'model'    => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'Eres un asistente agrícola en Chile. Responde la pregunta del usuario en español de forma clara y concisa basándote ÚNICAMENTE en los datos JSON proporcionados. Formatea los montos con puntos de miles y símbolo $ (ej: $1.234.567). Sé directo y usa viñetas cuando haya múltiples ítems.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Pregunta: {$question}\n\nDatos encontrados:\n{$resultsJson}",
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens'  => 800,
            ],
        ]);

        $answerData = json_decode($answerResponse->getBody()->getContents(), true);
        $answer     = $answerData['choices'][0]['message']['content'] ?? 'No pude interpretar los resultados.';

        return [
            'answer'      => $answer,
            'sql_preview' => $sql,
        ];
    }

    /**
     * Valida que la SQL sea un SELECT seguro.
     * Lanza excepción si detecta operaciones no permitidas.
     */
    private function validateSql(string $sql): void
    {
        $upper = strtoupper(trim($sql));

        if (!str_starts_with($upper, 'SELECT')) {
            throw new \Exception('La consulta generada no es un SELECT válido.');
        }

        $forbidden = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE',
            'ALTER',  'CREATE', 'EXEC',   'EXECUTE', 'GRANT',
            'REVOKE', 'CALL',   'LOAD',   'OUTFILE', 'DUMPFILE',
        ];

        foreach ($forbidden as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/', $upper)) {
                throw new \Exception("Consulta no permitida: contiene la operación {$keyword}.");
            }
        }
    }

    /**
     * Descripción del esquema de BD para el prompt del sistema.
     */
    private function getSchemaDescription(): string
    {
        return <<<SCHEMA
outflows (salidas/gastos generales)
  columnas: id, team_id, season_id, quantity (decimal), date (date), level3_id, notes, user_id, operation_id, machinery_id, project_id
  filtrar: WHERE outflows.team_id = {TEAM_ID} AND outflows.season_id = {SEASON_ID}

investments (inversiones planificadas)
  columnas: id, season_id, name, amount (decimal CLP), month_execute, estado (pendiente/ejecutado/cancelado), responsable, observations
  NOTA: NO tiene team_id — filtrar SOLO por season_id: WHERE investments.season_id = {SEASON_ID}

budgets (presupuestos)
  columnas: id, team_id, season_id, name, observations
  filtrar: WHERE budgets.team_id = {TEAM_ID} AND budgets.season_id = {SEASON_ID}

products (productos e insumos)
  columnas: id, team_id, name, level1_id, level2_id, level3_id, level4_id, unit_id
  NOTA: NO tiene season_id — filtrar SOLO por team_id: WHERE products.team_id = {TEAM_ID}

level1s (categorías nivel 1 del plan de cuentas)
  columnas: id, team_id, season_id, name
  filtrar: WHERE level1s.team_id = {TEAM_ID} AND level1s.season_id = {SEASON_ID}

level2s (categorías nivel 2)
  columnas: id, level1_id, name
  NOTA: NO tiene team_id ni season_id — filtrar via: JOIN level1s ON level2s.level1_id = level1s.id WHERE level1s.team_id = {TEAM_ID} AND level1s.season_id = {SEASON_ID}

level3s (categorías nivel 3)
  columnas: id, level2_id, name
  NOTA: NO tiene team_id ni season_id — filtrar via: JOIN level2s ON level3s.level2_id = level2s.id JOIN level1s ON level2s.level1_id = level1s.id WHERE level1s.team_id = {TEAM_ID} AND level1s.season_id = {SEASON_ID}

level4s (ítems del presupuesto con monto)
  columnas: id, level3_id, name, amount (decimal CLP presupuestado)
  NOTA: NO tiene team_id ni season_id — filtrar igual que level3s pasando por level2s y level1s

suppliers (proveedores)
  columnas: id, team_id, name, rut
  filtrar: WHERE suppliers.team_id = {TEAM_ID}

invoices (facturas recibidas)
  columnas: id, team_id, season_id, number, date, due_date, supplier_id, company_reason_id, type_document_id
  NOTA: NO tiene columna total — el total se calcula desde invoice_products
  filtrar: WHERE invoices.team_id = {TEAM_ID} AND invoices.season_id = {SEASON_ID}

invoice_products (ítems/líneas de facturas)
  columnas: id, invoice_id, product_id, unit_price (decimal CLP), amount (decimal CLP = subtotal de la línea), observations
  NOTA: para suma total de facturas usar SUM(invoice_products.amount)

fuel_outflows (salidas de combustible)
  columnas: id, team_id, season_id, liters (decimal — NO se llama quantity), date, observations, product_id, machinery_id, operator_id, cost_center_id
  filtrar: WHERE fuel_outflows.team_id = {TEAM_ID} AND fuel_outflows.season_id = {SEASON_ID}

agrochemical_outflows (salidas de agroquímicos)
  columnas: id, team_id, season_id, quantity (decimal), date, observations, product_id, cost_center_id, application_order_id
  filtrar: WHERE agrochemical_outflows.team_id = {TEAM_ID} AND agrochemical_outflows.season_id = {SEASON_ID}

fertilizer_outflows (salidas de fertilizantes)
  columnas: id, team_id, season_id, quantity (decimal), date, observations, product_id, cost_center_id, fertilizer_order_id
  filtrar: WHERE fertilizer_outflows.team_id = {TEAM_ID} AND fertilizer_outflows.season_id = {SEASON_ID}

cost_centers (centros de costo: campos/cuarteles)
  columnas: id, season_id, name, surface (decimal hectáreas), fruit_id, variety_id, parcel_id, development_state_id, company_reason_id, status (boolean)
  NOTA: NO tiene team_id — filtrar SOLO por season_id: WHERE cost_centers.season_id = {SEASON_ID}

employees (empleados)
  columnas: id, team_id, name, rut
  filtrar: WHERE employees.team_id = {TEAM_ID}

machinery (maquinaria)
  columnas: id, team_id, name
  filtrar: WHERE machinery.team_id = {TEAM_ID}

services (servicios contratados)
  columnas: id, team_id, season_id, product_name (nombre del servicio), price (decimal CLP), quantity (decimal), subfamily_id, user_id
  filtrar: WHERE services.team_id = {TEAM_ID} AND services.season_id = {SEASON_ID}

harvests (cosechas registradas)
  columnas: id, team_id, season_id, product_name, price, quantity, unit_id, subfamily_id, user_id
  NOTA: NO tiene cost_center_id directamente — la relación es via tabla pivot harvest_items
  filtrar: WHERE harvests.team_id = {TEAM_ID} AND harvests.season_id = {SEASON_ID}

seasons (temporadas)
  columnas: id, name, start_date, end_date

units (unidades de medida)
  columnas: id, name

varieties (variedades de fruta)
  columnas: id, name, fruit_id

fruits (tipos de fruta)
  columnas: id, name
SCHEMA;
    }
}
