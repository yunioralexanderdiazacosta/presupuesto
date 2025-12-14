<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Services\InvoiceOcrService;
use App\Models\Supplier;
use App\Models\CompanyReason;
use App\Models\TypeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            // 2. Buscar proveedor
            $supplier = $this->findSupplier($ocrData);

            // 3. Buscar razón social
            $companyReason = $this->findCompanyReason($ocrData);

            // 4. Detectar tipo de documento
            $typeDocument = $this->findTypeDocument($ocrData);

            // 5. Inferir tipo de pago (por ahora simple)
            $paymentInfo = ['type' => 1, 'term' => 30]; // Default: Crédito 30 días

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
                ],
                'raw' => [
                    'supplier_name' => $ocrData['supplier_name'],
                    'supplier_rut' => $ocrData['supplier_tax_id'],
                    'customer_name' => $ocrData['customer_name'],
                    'customer_rut' => $ocrData['customer_tax_id'],
                    'document_type' => $ocrData['document_type'],
                ],
                'confidence' => [
                    'supplier' => $supplier ? 0.95 : 0,
                    'company_reason' => $companyReason ? 0.98 : 0,
                    'type_document' => $typeDocument ? 0.90 : 0,
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

        return Supplier::where('team_id', $teamId)
            ->where(function ($q) use ($ocrData) {
                if ($ocrData['supplier_tax_id']) {
                    $q->where('rut', $ocrData['supplier_tax_id']);
                }
                if ($ocrData['supplier_name']) {
                    $q->orWhere('name', 'LIKE', '%' . $ocrData['supplier_name'] . '%');
                }
            })
            ->first();
    }

    private function findCompanyReason($ocrData)
    {
        $teamId = Auth::user()->team_id;

        if (!$ocrData['customer_tax_id']) {
            return null;
        }

        return CompanyReason::where('team_id', $teamId)
            ->where('rut', $ocrData['customer_tax_id'])
            ->first();
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
}
