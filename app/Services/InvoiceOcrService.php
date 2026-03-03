<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as HttpClient;

class InvoiceOcrService
{
    /**
     * Crear cliente HTTP configurado para la API de OpenAI.
     * Usa Guzzle directo (ya incluido en Laravel) — sin dependencias extra.
     */
    private function makeHttpClient(): HttpClient
    {
        $options = [
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => (int) config('openai.request_timeout', 60),
            'headers' => [
                'Authorization' => 'Bearer ' . config('openai.api_key'),
                'Content-Type' => 'application/json',
            ],
        ];

        // En MAMP/Windows, buscar cacert.pem para SSL
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
     * Extraer datos de una factura PDF usando OpenAI.
     * Estrategia: extraer texto con smalot/pdfparser → enviar a GPT-4o-mini.
     * Fallback: si OpenAI falla, usar regex básico sobre el texto extraído.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    public function extractFromPdf($file)
    {
        $filename = $file->getClientOriginalName();

        // Paso 1: Extraer texto del PDF con smalot/pdfparser
        try {
            $config = new \Smalot\PdfParser\Config();
            $config->setRetainImageContent(false);
            $config->setIgnoreEncryption(true); // Permitir PDFs protegidos (owner-password del SII)
            $parser = new \Smalot\PdfParser\Parser([], $config);
            
            $pdf = $parser->parseFile($file->getRealPath());
            $fullText = $pdf->getText();
        } catch (\Exception $e) {
            Log::error('❌ No se pudo extraer texto del PDF', [
                'error' => $e->getMessage(),
                'file' => $filename,
            ]);
            throw new \Exception('No se pudo leer el PDF: ' . $e->getMessage());
        }

        if (empty(trim($fullText))) {
            Log::warning('⚠️ PDF sin texto extraíble (posible imagen escaneada)', ['file' => $filename]);
            throw new \Exception('El PDF no contiene texto seleccionable. Puede ser una imagen escaneada.');
        }

        Log::info('📄 Texto extraído del PDF', [
            'filename' => $filename,
            'longitud' => strlen($fullText),
        ]);

        // Paso 2: Enviar texto a OpenAI para extracción inteligente
        try {
            $result = $this->callOpenAi($fullText, $filename);

            Log::info('✅ Datos extraídos por OpenAI', [
                'supplier_name' => $result['supplier_name'],
                'supplier_tax_id' => $result['supplier_tax_id'],
                'invoice_number' => $result['invoice_number'],
                'product_lines' => count($result['product_lines']),
            ]);

            // Log detallado de productos con cantidades para debug
            foreach ($result['product_lines'] as $i => $line) {
                Log::info("📦 Producto #{$i}: {$line['name']}", [
                    'quantity' => $line['quantity'],
                    'unit' => $line['unit'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['total'],
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Error con OpenAI, usando fallback regex', [
                'error' => $e->getMessage(),
                'file' => $filename,
            ]);

            // Fallback: regex básico sobre el texto
            return $this->extractWithRegexFallback($fullText);
        }
    }

    /**
     * Enviar texto de factura a GPT-4o-mini para extracción estructurada.
     * Usa Guzzle directo contra la API REST de OpenAI.
     */
    private function callOpenAi(string $pdfText, string $filename): array
    {
        $apiKey = config('openai.api_key');
        if (empty($apiKey)) {
            throw new \Exception('La API key de OpenAI no está configurada. Revisa OPENAI_API_KEY en .env');
        }

        $prompt = $this->buildPrompt();

        // Limitar texto a ~8000 chars para no exceder tokens
        $text = mb_substr($pdfText, 0, 8000);

        $client = $this->makeHttpClient();

        $response = $client->post('chat/completions', [
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un experto en extracción de datos de facturas chilenas. Siempre respondes en formato JSON válido, sin markdown ni bloques de código.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt . "\n\n--- TEXTO DE LA FACTURA ---\n" . $text,
                    ],
                ],
                'max_tokens' => 4096,
                'temperature' => 0.1,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $content = $body['choices'][0]['message']['content'] ?? '';

        Log::info('📝 Respuesta raw de OpenAI', ['content' => substr($content, 0, 2000)]);

        // Parsear JSON de la respuesta
        $data = $this->parseJsonResponse($content);

        // Normalizar al contrato esperado por ExtractInvoiceFromPdfController
        return $this->normalizeResponse($data);
    }

    /**
     * Construir el prompt para OpenAI Vision.
     */
    private function buildPrompt(): string
    {
        return <<<'PROMPT'
Analiza esta factura chilena y extrae TODOS los datos en formato JSON con esta estructura exacta:

{
    "invoice_number": "número de factura/documento (solo el número, sin texto)",
    "date": "fecha de emisión en formato YYYY-MM-DD",
    "due_date": "fecha de vencimiento en formato YYYY-MM-DD (buscar 'Fecha Vencimiento', 'Vencimiento', 'Fecha de Pago', 'Vence' o similar. Si no se encuentra, usar null)",
    "document_type": "tipo de documento. Usar EXACTAMENTE uno de estos valores: Factura, Factura exenta, Boleta, Boleta honorarios exenta, Boleta honorarios afecta. Reglas: si dice 'Factura Electrónica' o 'Factura Afecta' → 'Factura'. Si dice 'Factura Exenta' o 'Factura No Afecta' → 'Factura exenta'. Si dice 'Boleta Electrónica' o 'Boleta de Venta' → 'Boleta'. Si dice 'Boleta de Honorarios' y tiene IVA → 'Boleta honorarios afecta'. Si dice 'Boleta de Honorarios' sin IVA o exenta → 'Boleta honorarios exenta'. Si dice 'Nota de Crédito' o 'Nota de Débito' → 'Factura'",
    "supplier_name": "nombre o razón social del emisor/proveedor",
    "supplier_tax_id": "RUT del emisor/proveedor (solo dígitos y k, sin puntos ni guión, ej: 812908006)",
    "customer_name": "nombre o razón social del receptor/cliente",
    "customer_tax_id": "RUT del receptor/cliente (solo dígitos y k, sin puntos ni guión)",
    "total_amount": 0,
    "payment_info": {
        "type": "contado o credito",
        "term_days": 0
    },
    "product_lines": [
        {
            "code": "código del producto si existe",
            "name": "SOLO el nombre comercial del producto, SIN presentación, SIN envase, SIN marca del fabricante",
            "unit": "unidad de medida abreviada (LT, KG, UN, MT, etc.)",
            "quantity": 0.0,
            "unit_price": 0.0,
            "total": 0.0
        }
    ]
}

REGLAS IMPORTANTES:
1. Los RUTs chilenos deben extraerse sin puntos ni guión (ej: "76.543.210-K" → "76543210k")
2. La fecha SIEMPRE en formato YYYY-MM-DD (tanto date como due_date)
3. Los montos y precios son números, NO strings
4. Para product_lines: extrae TODAS las líneas de detalle de productos/servicios
5. Si un campo no se puede detectar, usar null (no inventar)
6. El tipo de pago: detecta si dice "contado", "efectivo", "cash" → type="contado". Si dice "crédito" o tiene plazo → type="credito" con los días
7. CANTIDAD (quantity) - MUY IMPORTANTE:
   - La quantity SIEMPRE debe ser el valor de la columna "Cant", "Cantidad" o "Qty" de la factura.
   - Es la cantidad de unidades compradas en esa línea de la factura.
   - NO confundir con el envase/presentación que aparece en el nombre del producto (ej: "x 20 lt" = envase de 20 litros, NO es la cantidad comprada).
   - unit_price es el precio unitario de la columna "P. Unit", "Precio" o similar.
   - total es el valor de la columna "Total" o "Monto" de esa línea.
   - Ejemplo: Si la línea dice "DEFENDER POTASIO x 20 lt" con Cant=3, P.Unit=50000, Total=150000 → quantity: 3 (NO 20)
8. LIMPIEZA DE NOMBRES DE PRODUCTOS (MUY IMPORTANTE):
   - Elimina sufijos de categoría: "-FITOSANITARIOS(A)-NV", "-FERTILIZANTES(B)-UREAS", etc.
   - Elimina la presentación/envase del nombre: "x 20 lt", "x 10 LT", "X 5 KG", "x 1 UN", etc. Eso es la presentación, NO la cantidad.
   - Elimina la marca del fabricante si aparece al final: "SYNGENTA", "BAYER", "BASF", "ANASAC", "ARYSTA", "FMC", "SUMMIT AGRO", "UPL", "NUFARM", "CORTEVA", "AGROSPEC", "STOLLER", etc.
   - Ejemplo: "STIMPLEX X 10 LT SYNGENTA" con Cant=2 → name: "STIMPLEX", quantity: 2, unit: "UN"
   - Ejemplo: "FOSFIMAX 40 - 20 x 20 lt" con Cant=5 → name: "FOSFIMAX 40 - 20", quantity: 5, unit: "UN"
   - Ejemplo: "DEFENDER POTASIO x 20 lt" con Cant=3 → name: "DEFENDER POTASIO", quantity: 3, unit: "UN"
   - El nombre debe quedar SOLO con el nombre comercial del producto
9. UNIDAD DE MEDIDA (unit):
   - Usa la unidad de medida de la columna "Unidad" o "U.M." de la factura si existe.
   - Si la factura no tiene columna de unidad, usar "UN" (unidades) por defecto.
   - Si la unidad aparece como "LT", "KG", "UN", "MT", "GL", "CC", "ML", "GR", "HA", "HR", "SC", "BL", "TM", mantener esa abreviatura.
   - NO usar la unidad del envase del nombre del producto (ej: "x 20 lt" → la unidad NO es LT, es UN)

Responde SOLO con el JSON, sin explicaciones ni bloques de código markdown.
PROMPT;
    }

    /**
     * Parsear la respuesta JSON de OpenAI, limpiando posible markdown.
     */
    private function parseJsonResponse(string $content): array
    {
        // Limpiar posible markdown ```json ... ```
        $content = trim($content);
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('❌ Error al parsear JSON de OpenAI', [
                'error' => json_last_error_msg(),
                'content' => $content,
            ]);
            throw new \Exception('La respuesta de OpenAI no es JSON válido: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Normalizar la respuesta de OpenAI al contrato esperado por
     * ExtractInvoiceFromPdfController.
     */
    private function normalizeResponse(array $data): array
    {
        // Normalizar RUTs (asegurar lowercase para 'k')
        $supplierTaxId = isset($data['supplier_tax_id'])
            ? strtolower(preg_replace('/[^0-9kK]/', '', $data['supplier_tax_id']))
            : null;

        $customerTaxId = isset($data['customer_tax_id'])
            ? strtolower(preg_replace('/[^0-9kK]/', '', $data['customer_tax_id']))
            : null;

        // Normalizar líneas de productos
        $productLines = [];
        foreach (($data['product_lines'] ?? []) as $line) {
            $rawName = $line['name'] ?? '';
            $cleanName = $this->cleanProductName($rawName);
            $productLines[] = [
                'code' => $line['code'] ?? '',
                'raw_name' => $rawName,
                'name' => $cleanName,
                'unit' => strtoupper($line['unit'] ?? 'UN'),
                'quantity' => (float) ($line['quantity'] ?? 1),
                'unit_price' => (float) ($line['unit_price'] ?? 0),
                'total' => (float) ($line['total'] ?? 0),
            ];
        }

        // Detectar tipo de pago
        $paymentType = 'credito';
        $paymentTermDays = 30;
        if (isset($data['payment_info'])) {
            $paymentType = strtolower($data['payment_info']['type'] ?? 'credito');
            $paymentTermDays = (int) ($data['payment_info']['term_days'] ?? 30);
        }

        // Construir full_text para que detectPaymentType funcione en el controlador
        $fullText = '';
        if ($paymentType === 'contado') {
            $fullText = 'Pago al contado. Contado.';
        } else {
            $fullText = "Crédito {$paymentTermDays} días.";
        }

        return [
            'invoice_number' => $data['invoice_number'] ?? null,
            'date' => $data['date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'document_type' => $data['document_type'] ?? null,
            'supplier_name' => $data['supplier_name'] ?? null,
            'supplier_tax_id' => $supplierTaxId,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_tax_id' => $customerTaxId,
            'total_amount' => isset($data['total_amount']) ? (int) $data['total_amount'] : null,
            'full_text' => $fullText,
            'product_lines' => $productLines,
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    // FALLBACK: Regex básico si OpenAI no está disponible
    // ════════════════════════════════════════════════════════════════════

    /**
     * Fallback: extraer datos con regex básico del texto ya extraído.
     */
    private function extractWithRegexFallback(string $fullText): array
    {
        Log::info('🔄 Usando fallback regex');

        return [
            'invoice_number' => $this->extractWithRegex($fullText, '/N[°º]\s*(?:Factura|Documento)?\s*[:\s]*(\d{1,10})/i')
                ?? $this->extractWithRegex($fullText, '/Folio\s*(?:N[°º])?\s*[:\s]*(\d{1,10})/i'),
            'date' => $this->extractDateFromText($fullText),
            'due_date' => $this->extractDueDateFromText($fullText),
            'document_type' => $this->detectDocumentTypeFromText($fullText),
            'supplier_name' => null,
            'supplier_tax_id' => $this->extractFirstRut($fullText),
            'customer_name' => null,
            'customer_tax_id' => null,
            'total_amount' => null,
            'full_text' => trim($fullText),
            'product_lines' => [],
        ];
    }

    // ─── Limpieza de nombres de productos ───

    /**
     * Limpiar nombre de producto: quitar presentación, envase y marca.
     * Ej: "STIMPLEX X 10 LT SYNGENTA" → "STIMPLEX"
     * Ej: "FOSFIMAX 40 - 20 x 20 lt" → "FOSFIMAX 40 - 20"
     */
    private function cleanProductName(string $name): string
    {
        $clean = trim($name);

        // 1. Eliminar sufijos de categoría SII: -FITOSANITARIOS(A)-NV, -FERTILIZANTES(B)-UREAS, etc.
        $clean = preg_replace('/\s*-\s*(FITOSANITARIOS?|FERTILIZANTES?|HERBICIDAS?|INSECTICIDAS?|FUNGICIDAS?|COADYUVANTES?|REGULADORES?)\b.*/i', '', $clean);

        // 2. Eliminar presentación/envase: "x 20 lt", "X 10 LT", "x20lt", " 20 LT" al final
        $clean = preg_replace('/\s+[xX]\s*\d+[\.,]?\d*\s*(LT|KG|UN|MT|GL|CC|ML|GR|HA|HR|SC|BL|TM|L|K)s?\b.*/i', '', $clean);

        // 3. Eliminar marcas conocidas al final del nombre
        $brands = [
            'SYNGENTA', 'BAYER', 'BASF', 'ANASAC', 'ARYSTA', 'FMC', 'SUMMIT AGRO',
            'UPL', 'NUFARM', 'CORTEVA', 'AGROSPEC', 'STOLLER', 'ADAMA', 'CHEMTURA',
            'DOW', 'DUPONT', 'MONSANTO', 'MAKHTESHIM', 'CHEMINOVA', 'HELM', 'ROTAM',
            'AGRICULTURAL SOLUTION', 'PHYTOK', 'OSKU', 'COSMOAGRO', 'BIOCROP',
            'TRADECORP', 'VALAGRO', 'COMPO EXPERT', 'ICL', 'YARA', 'SQM',
        ];
        foreach ($brands as $brand) {
            $clean = preg_replace('/\s+' . preg_quote($brand, '/') . '\s*$/i', '', $clean);
        }

        // 4. Limpiar espacios extra
        $clean = trim(preg_replace('/\s{2,}/', ' ', $clean));

        return $clean ?: trim($name); // Si queda vacío, devolver original
    }

    // ─── Helpers mínimos para el fallback ───

    private function extractWithRegex(string $text, string $pattern): ?string
    {
        return preg_match($pattern, $text, $m) ? $m[1] : null;
    }

    private function extractFirstRut(string $text): ?string
    {
        $pattern = '/\b([0-9]{1,2}\.?[0-9]{3}\.?[0-9]{3}-?[0-9kK])\b/';
        if (preg_match($pattern, $text, $m)) {
            return strtolower(preg_replace('/[^0-9kK]/', '', $m[1]));
        }
        return null;
    }

    private function extractDateFromText(string $text): ?string
    {
        if (preg_match('/Fecha\s*(?:de\s+)?(?:Emisi[oó]n)?\s*[:\s]*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i', $text, $m)) {
            if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $m[1], $parts)) {
                return "{$parts[3]}-" . str_pad($parts[2], 2, '0', STR_PAD_LEFT) . "-" . str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            }
        }
        return null;
    }

    private function detectDocumentTypeFromText(string $text): ?string
    {
        $textUpper = strtoupper($text);
        // Orden específico: patrones más específicos primero
        if (str_contains($textUpper, 'BOLETA DE HONORARIOS') || str_contains($textUpper, 'BOLETA HONORARIOS')) {
            if (str_contains($textUpper, 'EXENTA') || !str_contains($textUpper, 'IVA')) {
                return 'Boleta honorarios exenta';
            }
            return 'Boleta honorarios afecta';
        }
        if (str_contains($textUpper, 'FACTURA EXENTA') || str_contains($textUpper, 'FACTURA NO AFECTA')) return 'Factura exenta';
        if (str_contains($textUpper, 'NOTA DE CREDITO') || str_contains($textUpper, 'NOTA DE CRÉDITO')) return 'Factura';
        if (str_contains($textUpper, 'NOTA DE DEBITO') || str_contains($textUpper, 'NOTA DE DÉBITO')) return 'Factura';
        if (str_contains($textUpper, 'BOLETA')) return 'Boleta';
        if (str_contains($textUpper, 'FACTURA')) return 'Factura';
        return null;
    }

    /**
     * Extraer fecha de vencimiento del texto del PDF.
     */
    private function extractDueDateFromText(string $text): ?string
    {
        // Buscar patrones: "Fecha Vencimiento", "Vencimiento", "Fecha de Pago", "Vence"
        $patterns = [
            '/Fecha\s*(?:de\s+)?Vencimiento\s*[:\s]*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i',
            '/Vencimiento\s*[:\s]*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i',
            '/Fecha\s*(?:de\s+)?Pago\s*[:\s]*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i',
            '/Vence\s*[:\s]*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $m[1], $parts)) {
                    return "{$parts[3]}-" . str_pad($parts[2], 2, '0', STR_PAD_LEFT) . "-" . str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                }
            }
        }

        return null;
    }

}
