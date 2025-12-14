<?php

namespace App\Services;

use Mindee\ClientV2;
use Mindee\Input\InferenceParameters;
use Mindee\Input\PathInput;
use Illuminate\Support\Facades\Log;

class InvoiceOcrService
{
    private $apiKey;
    private $modelId = 'c2d7047a-afdd-468f-be0a-f5233b6afb5d';

    public function __construct()
    {
        $this->apiKey = config('services.mindee.api_key');
    }

    public function extractFromPdf($file)
    {
        try {
            Log::info('Iniciando extracción OCR con Mindee SDK', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);

            // Inicializar cliente de Mindee
            $mindeeClient = new ClientV2($this->apiKey);

            // Parámetros de inferencia
            $inferenceParams = new InferenceParameters($this->modelId);

            // Cargar archivo
            $inputSource = new PathInput($file->getRealPath());

            // Procesar con polling automático
            $response = $mindeeClient->enqueueAndGetInference($inputSource, $inferenceParams);

            Log::info('Respuesta exitosa de Mindee', [
                'pages' => count($response->inference->pages ?? []),
                'fields_raw' => json_encode($response->inference->result->fields ?? [])
            ]);

            return $this->normalizeData($response);

        } catch (\Exception $e) {
            Log::error('OCR failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function normalizeData($mindeeResponse)
    {
        // Acceder a los campos extraídos
        $fields = $mindeeResponse->inference->result->fields ?? [];

        // Helper para extraer valor de campo Mindee
        $getValue = function($field) {
            if (is_object($field) && isset($field->value)) {
                return $field->value;
            }
            if (is_array($field) && isset($field['value'])) {
                return $field['value'];
            }
            if (is_array($field) && !empty($field)) {
                // Para arrays como company_registrations
                $first = $field[0] ?? null;
                if (is_object($first) && isset($first->value)) {
                    return $first->value;
                }
                if (is_array($first) && isset($first['value'])) {
                    return $first['value'];
                }
            }
            return $field;
        };

        return [
            'invoice_number' => $getValue($fields['invoice_number'] ?? null),
            'date' => $getValue($fields['date'] ?? null),
            'document_type' => $getValue($fields['document_type'] ?? null), // Tipo de documento
            'supplier_name' => $getValue($fields['supplier_name'] ?? null),
            'supplier_tax_id' => isset($fields['supplier_company_registrations']) 
                ? $this->cleanRut($getValue($fields['supplier_company_registrations'])) 
                : null,
            'customer_name' => $getValue($fields['customer_name'] ?? null),
            'customer_tax_id' => isset($fields['customer_company_registrations']) 
                ? $this->cleanRut($getValue($fields['customer_company_registrations'])) 
                : null,
            'total_amount' => $getValue($fields['total_amount'] ?? null),
        ];
    }

    public function cleanRut($rut)
    {
        return preg_replace('/[^0-9kK]/', '', $rut);
    }
}
