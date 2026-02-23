<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Services\InvoiceOcrService;
use App\Models\Supplier;
use App\Models\CompanyReason;
use App\Models\TypeDocument;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExtractInvoiceFromPdfController extends Controller
{
    private $ocrService;

    public function __construct(InvoiceOcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    public function __invoke(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:10240'
        ]);

        try {
            // 1. Extraer datos del PDF con Mindee
            $ocrData = $this->ocrService->extractFromPdf($request->file('pdf'));

            Log::info('📊 Datos extraídos del OCR', [
                'supplier_name' => $ocrData['supplier_name'],
                'supplier_tax_id' => $ocrData['supplier_tax_id'],
                'invoice_number' => $ocrData['invoice_number']
            ]);

            // 2. Buscar proveedor
            $supplier = $this->findSupplier($ocrData);

            // 3. Buscar razón social
            $companyReason = $this->findCompanyReason($ocrData);

            // 4. Detectar tipo de documento
            $typeDocument = $this->findTypeDocument($ocrData);

            // 5. Detectar tipo de pago desde el texto del PDF
            $paymentInfo = $this->detectPaymentType($ocrData);

            // 6. Emparejar líneas de productos con productos de la BD
            $matchedProducts = $this->matchProducts($ocrData['product_lines'] ?? []);

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $ocrData['date'],
                    'number_document' => $ocrData['invoice_number'],
                    'type_document_id' => $typeDocument?->id,
                    'supplier_id' => $supplier?->id,
                    'company_reason_id' => $companyReason?->id,
                    'payment_type' => $paymentInfo['type'],
                    'payment_term' => $paymentInfo['term'],
                    'products' => $matchedProducts,
                ],
                'raw' => [
                    'supplier_name' => $ocrData['supplier_name'],
                    'supplier_rut' => $ocrData['supplier_tax_id'],
                    'customer_name' => $ocrData['customer_name'],
                    'customer_rut' => $ocrData['customer_tax_id'],
                    'document_type' => $ocrData['document_type'],
                    'payment_detected' => $paymentInfo['type'] == 2 ? 'Contado' : 'Crédito',
                ],
                'confidence' => [
                    'supplier' => $supplier ? 0.95 : 0,
                    'company_reason' => $companyReason ? 0.98 : 0,
                    'type_document' => $typeDocument ? 0.90 : 0,
                    'payment_type' => $paymentInfo['type'] == 2 ? 0.95 : 0.50,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    private function findSupplier($ocrData)
    {
        $teamId = Auth::user()->team_id;
        
        if (!$ocrData['supplier_tax_id'] && !$ocrData['supplier_name']) {
            return null;
        }

        // Primero buscar por RUT (normalizado: sin puntos ni guiones)
        if ($ocrData['supplier_tax_id']) {
            $cleanRut = preg_replace('/[^0-9kK]/', '', $ocrData['supplier_tax_id']);
            
            // Buscar el proveedor cuyo RUT limpio coincida
            $supplier = Supplier::where('team_id', $teamId)->get()->first(function ($s) use ($cleanRut) {
                $dbRut = preg_replace('/[^0-9kK]/', '', strtolower($s->rut ?? ''));
                return strtolower($cleanRut) === $dbRut;
            });

            if ($supplier) {
                return $supplier;
            }
        }

        // Si no encontró por RUT, buscar por nombre (LIKE, insensible a acentos en MySQL con collation)
        if ($ocrData['supplier_name']) {
            $supplier = Supplier::where('team_id', $teamId)
                ->where('name', 'LIKE', '%' . $ocrData['supplier_name'] . '%')
                ->first();

            if ($supplier) {
                return $supplier;
            }
        }

        return null;
    }

    private function findCompanyReason($ocrData)
    {
        $teamId = Auth::user()->team_id;

        if (!$ocrData['customer_tax_id']) {
            return null;
        }

        // Normalizar RUT del cliente para comparar
        $cleanRut = preg_replace('/[^0-9kK]/', '', $ocrData['customer_tax_id']);

        return CompanyReason::where('team_id', $teamId)->get()->first(function ($cr) use ($cleanRut) {
            $dbRut = preg_replace('/[^0-9kK]/', '', strtolower($cr->rut ?? ''));
            return strtolower($cleanRut) === $dbRut;
        });
    }

    private function findTypeDocument($ocrData)
    {
        $documentType = $ocrData['document_type'] ?? '';
        
        // Mapear texto detectado a códigos SII Chile
        $typeMap = [
            'factura electronica' => '33',
            'factura' => '33',
            'boleta electronica' => '39',
            'boleta' => '39',
            'nota de credito' => '61',
            'nota credito' => '61',
            'nota de debito' => '56',
            'nota debito' => '56',
        ];

        $documentTypeLower = strtolower($documentType);
        
        // Buscar coincidencia en el mapa
        foreach ($typeMap as $pattern => $code) {
            if (str_contains($documentTypeLower, $pattern)) {
                return TypeDocument::where('code', $code)->first();
            }
        }

        // Si no se detectó, intentar por defecto "Factura"
        return TypeDocument::where('code', '33')->first();
    }

    private function detectPaymentType($ocrData)
    {
        // Obtener todo el texto extraído del PDF
        $fullText = strtolower($ocrData['full_text'] ?? '');
        
        // Palabras clave que indican pago al CONTADO
        $contadoPatterns = [
            '/\bcontado\b/i',
            '/\bal contado\b/i',
            '/\bpago contado\b/i',
            '/\bpago al contado\b/i',
            '/\befectivo\b/i',
            '/\bcash\b/i',
        ];
        
        // Buscar cualquier patrón de contado
        foreach ($contadoPatterns as $pattern) {
            if (preg_match($pattern, $fullText)) {
                return [
                    'type' => 2,  // 2 = Contado
                    'term' => 0   // Sin plazo
                ];
            }
        }
        
        // Palabras clave que indican CRÉDITO específico
        if (preg_match('/\bcr[eé]dito\b/i', $fullText) || preg_match('/\bcredit\b/i', $fullText)) {
            // Intentar extraer número de días (30, 60, 90, etc.)
            if (preg_match('/(\d+)\s*d[ií]as?/i', $fullText, $matches)) {
                $days = (int)$matches[1];
                return ['type' => 1, 'term' => $days];
            }
            return ['type' => 1, 'term' => 30]; // Crédito por defecto 30 días
        }
        
        // Por defecto: Crédito 30 días
        return ['type' => 1, 'term' => 30];
    }

    /**
     * Emparejar líneas de productos extraídas del PDF con productos existentes en la BD.
     * Busca coincidencia por las primeras 1-2 palabras del nombre del producto (case-insensitive).
     *
     * @param array $productLines Líneas extraídas por InvoiceOcrService::extractProductLines()
     * @return array Productos con product_id (si se encontró), unit_price, amount, observations, unit_id
     */
    private function matchProducts(array $productLines)
    {
        if (empty($productLines)) {
            return [];
        }

        $teamId = Auth::user()->team_id;

        // Cargar todos los productos del equipo con su unidad (una sola query)
        $dbProducts = Product::where('team_id', $teamId)
            ->with('unit')
            ->get();

        // Cargar unidades para emparejar por nombre (LT → Litro, KG → Kilo, etc.)
        $units = Unit::all();
        $unitMap = $this->buildUnitMap($units);

        $matched = [];

        foreach ($productLines as $pdfProduct) {
            $productId = null;
            $unitId = null;
            $matchedName = null;

            // Intentar encontrar el producto en la BD
            $cleanName = strtolower(trim($pdfProduct['name']));

            // Estrategia 1: buscar por primeras 2 palabras
            $words = preg_split('/\s+/', $cleanName);
            $searchTerms = [];

            if (count($words) >= 2) {
                $searchTerms[] = $words[0] . ' ' . $words[1]; // 2 palabras
            }
            if (count($words) >= 1) {
                $searchTerms[] = $words[0]; // 1 palabra (fallback)
            }

            foreach ($searchTerms as $searchTerm) {
                $found = $dbProducts->first(function ($p) use ($searchTerm) {
                    return str_contains(strtolower($p->name), $searchTerm);
                });

                if ($found) {
                    $productId = $found->id;
                    $unitId = $found->unit_id;
                    $matchedName = $found->name;
                    break;
                }
            }

            // Estrategia 2: si no encontró, buscar por código del proveedor
            if (!$productId && !empty($pdfProduct['code'])) {
                $found = $dbProducts->first(function ($p) use ($pdfProduct) {
                    return str_contains(strtolower($p->name), strtolower($pdfProduct['code']));
                });
                if ($found) {
                    $productId = $found->id;
                    $unitId = $found->unit_id;
                    $matchedName = $found->name;
                }
            }

            // Si no se encontró unit_id del producto, emparejar por abreviatura de unidad del PDF
            if (!$unitId && !empty($pdfProduct['unit'])) {
                $pdfUnit = strtoupper($pdfProduct['unit']);
                $unitId = $unitMap[$pdfUnit] ?? null;
            }

            $matched[] = [
                'product_id' => $productId,
                'unit_id' => $unitId,
                'unit_price' => round($pdfProduct['unit_price'], 2),
                'amount' => $pdfProduct['quantity'],
                'observations' => $pdfProduct['raw_name'],
                'matched' => $productId !== null,
                'matched_name' => $matchedName,
                'pdf_name' => $pdfProduct['name'],
                'pdf_code' => $pdfProduct['code'] ?? '',
            ];
        }

        Log::info('Productos emparejados del PDF', [
            'total_extraidos' => count($productLines),
            'total_emparejados' => count(array_filter($matched, fn($m) => $m['matched'])),
            'productos' => array_map(fn($m) => [
                'pdf' => $m['pdf_name'],
                'db' => $m['matched_name'] ?? 'NO ENCONTRADO',
                'matched' => $m['matched'],
            ], $matched),
        ]);

        return $matched;
    }

    /**
     * Construir mapa de abreviaturas de unidad a unit_id.
     * Mapea abreviaturas comunes de facturas chilenas.
     */
    private function buildUnitMap($units)
    {
        // Mapeo de abreviaturas comunes en facturas → nombre de unidad en BD
        $abbreviations = [
            'LT' => ['litro', 'litros', 'lt', 'lts'],
            'KG' => ['kilo', 'kilos', 'kilogramo', 'kilogramos', 'kg'],
            'TM' => ['tonelada', 'toneladas', 'ton', 'tm'],
            'UN' => ['unidad', 'unidades', 'un', 'uni'],
            'MT' => ['metro', 'metros', 'mt', 'mts'],
            'GL' => ['galón', 'galon', 'galones', 'gl'],
            'CC' => ['cc', 'centímetro cúbico'],
            'ML' => ['mililitro', 'mililitros', 'ml'],
            'GR' => ['gramo', 'gramos', 'gr'],
            'HA' => ['hectárea', 'hectareas', 'hectarea', 'ha'],
            'JR' => ['jornal', 'jornada', 'jr'],
            'HR' => ['hora', 'horas', 'hr', 'hrs'],
            'SC' => ['saco', 'sacos'],
            'BL' => ['bolsa', 'bolsas'],
            'BI' => ['bidón', 'bidon', 'bidones'],
            'RO' => ['rollo', 'rollos'],
        ];

        $map = [];

        foreach ($units as $unit) {
            $unitNameLower = strtolower($unit->name ?? '');
            $unitLabelLower = strtolower($unit->label ?? $unit->name ?? '');

            foreach ($abbreviations as $abbrev => $names) {
                foreach ($names as $name) {
                    if ($unitNameLower === $name || $unitLabelLower === $name || str_contains($unitNameLower, $name)) {
                        $map[$abbrev] = $unit->id;
                        break 2; // Encontrado, pasar a siguiente unidad
                    }
                }
            }
        }

        return $map;
    }
}
