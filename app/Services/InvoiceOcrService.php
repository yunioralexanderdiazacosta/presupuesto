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

        // Capturar todo el texto del PDF para detección de palabras clave
        $fullText = '';
        if (isset($mindeeResponse->inference->pages)) {
            foreach ($mindeeResponse->inference->pages as $page) {
                if (isset($page->prediction->raw_text)) {
                    $fullText .= ' ' . $page->prediction->raw_text;
                }
            }
        }
        
        // Si no hay texto en pages, intentar desde document
        if (empty($fullText) && isset($mindeeResponse->document->inference->pages)) {
            foreach ($mindeeResponse->document->inference->pages as $page) {
                if (isset($page->raw_text)) {
                    $fullText .= ' ' . $page->raw_text;
                } elseif (isset($page->prediction->raw_text)) {
                    $fullText .= ' ' . $page->prediction->raw_text;
                }
            }
        }

        // Obtener RUT del proveedor - Mindee lo devuelve en supplier_company_registration
        $supplierTaxId = null;
        
        // Intentar desde supplier_company_registration (singular - formato estándar)
        if (isset($fields['supplier_company_registration'])) {
            $registration = $fields['supplier_company_registration'];
            
            // La estructura es: registration->items[0]->fields['number']->value
            if (is_object($registration) && isset($registration->items) && !empty($registration->items)) {
                $item = $registration->items[0];
                if (isset($item->fields) && isset($item->fields['number'])) {
                    $supplierTaxId = $item->fields['number']->value ?? null;
                }
            }
        }
        
        // Si no, intentar supplier_company_registrations (plural)
        if (empty($supplierTaxId) && isset($fields['supplier_company_registrations'])) {
            $registration = $fields['supplier_company_registrations'];
            if (is_object($registration) && isset($registration->items) && !empty($registration->items)) {
                $item = $registration->items[0];
                if (isset($item->fields) && isset($item->fields['number'])) {
                    $supplierTaxId = $item->fields['number']->value ?? null;
                }
            }
        }
        
        // Si Mindee no detectó el RUT del proveedor, intentar extraerlo del texto completo
        if (!$supplierTaxId && $fullText) {
            Log::info('Intentando extraer RUT desde texto completo', [
                'longitud_texto' => strlen($fullText),
                'primeros_500_chars' => substr($fullText, 0, 500)
            ]);
            $supplierTaxId = $this->extractSupplierRutFromText($fullText, $getValue($fields['supplier_name'] ?? null));
        }

        // Obtener nombre del proveedor - intentar extraer el nombre completo del texto
        $supplierName = $getValue($fields['supplier_name'] ?? null);
        
        // Si tenemos RUT y texto completo, intentar extraer el nombre completo (razón social)
        if ($supplierTaxId && $fullText) {
            $fullSupplierName = $this->extractFullSupplierName($fullText, $supplierTaxId, $supplierName);
            if ($fullSupplierName) {
                $supplierName = $fullSupplierName;
            }
        }

        // Obtener RUT del cliente
        $customerTaxId = null;
        if (isset($fields['customer_company_registrations'])) {
            $registration = $fields['customer_company_registrations'];
            if (is_object($registration) && isset($registration->items) && !empty($registration->items)) {
                $item = $registration->items[0];
                if (isset($item->fields) && isset($item->fields['number'])) {
                    $customerTaxId = $item->fields['number']->value ?? null;
                }
            }
        }

        return [
            'invoice_number' => $getValue($fields['invoice_number'] ?? null),
            'date' => $getValue($fields['date'] ?? null),
            'document_type' => $getValue($fields['document_type'] ?? null), // Tipo de documento
            'supplier_name' => $supplierName,
            'supplier_tax_id' => $supplierTaxId,
            'customer_name' => $getValue($fields['customer_name'] ?? null),
            'customer_tax_id' => $customerTaxId,
            'total_amount' => $getValue($fields['total_amount'] ?? null),
            'full_text' => trim($fullText), // Texto completo para análisis
        ];
    }

    public function cleanRut($rut)
    {
        return preg_replace('/[^0-9kK]/', '', $rut);
    }

    /**
     * Extrae el RUT del proveedor desde el texto completo del PDF
     * Busca patrones comunes en facturas chilenas
     */
    private function extractSupplierRutFromText($fullText, $supplierName = null)
    {
        Log::info('🔍 Iniciando extracción de RUT desde texto', [
            'supplier_name' => $supplierName,
            'texto_disponible' => !empty($fullText)
        ]);
        
        // Patrones comunes de RUT en facturas (formato chileno)
        $patterns = [
            // RUT: 12.345.678-9 o RUT 12.345.678-9
            '/RUT[:\s]+([0-9]{1,2}[\.\s]?[0-9]{3}[\.\s]?[0-9]{3}[-\s]?[0-9kK])/i',
            // R.U.T: 12.345.678-9
            '/R\.U\.T[:\s]+([0-9]{1,2}[\.\s]?[0-9]{3}[\.\s]?[0-9]{3}[-\s]?[0-9kK])/i',
            // Formato: 12.345.678-9 (sin prefijo, pero con formato completo)
            '/\b([0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9kK])\b/',
            // Formato: 12345678-9 (sin puntos)
            '/\b([0-9]{7,8}-[0-9kK])\b/',
        ];

        $foundRuts = [];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $fullText, $matches)) {
                foreach ($matches[1] as $rut) {
                    $cleanedRut = $this->cleanRut($rut);
                    if (strlen($cleanedRut) >= 8 && strlen($cleanedRut) <= 9) {
                        $foundRuts[] = $cleanedRut;
                    }
                }
            }
        }

        // Si encontramos RUTs, retornar el primero (usualmente es el del emisor/proveedor)
        if (!empty($foundRuts)) {
            return $foundRuts[0];
        }
        
        return null;
    }

    /**
     * Extraer el nombre completo (razón social) del proveedor desde el texto del PDF
     * En facturas chilenas, la razón social suele aparecer cerca del RUT
     */
    private function extractFullSupplierName($fullText, $supplierTaxId, $shortName)
    {
        // Formatear el RUT con puntos y guion para buscarlo en el texto
        $rutFormatted = $this->formatRut($supplierTaxId);
        
        // Buscar el RUT en el texto
        $rutPosition = stripos($fullText, $rutFormatted);
        
        if ($rutPosition === false) {
            // Intentar buscar sin puntos
            $rutWithoutDots = preg_replace('/\./', '', $rutFormatted);
            $rutPosition = stripos($fullText, $rutWithoutDots);
        }
        
        if ($rutPosition === false) {
            return null;
        }
        
        // Extraer contexto alrededor del RUT (200 caracteres antes y después)
        $contextStart = max(0, $rutPosition - 200);
        $contextEnd = min(strlen($fullText), $rutPosition + 200);
        $context = substr($fullText, $contextStart, $contextEnd - $contextStart);
        
        // Patrones para encontrar el nombre completo
        $patterns = [
            '/([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s\.\/]+(?:LIMITADA|LTDA|S\.?A\.?|SPA|EIRL|S\.?R\.?L\.?))\s*(?:RUT|R\.U\.T\.?)?[:\s]*' . preg_quote($rutFormatted, '/') . '/i',
            '/([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{10,60})\s*(?:RUT|R\.U\.T\.?)?[:\s]*' . preg_quote($rutFormatted, '/') . '/i',
            '/' . preg_quote($rutFormatted, '/') . '\s*([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s\.\/]+(?:LIMITADA|LTDA|S\.?A\.?|SPA|EIRL))/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $context, $matches)) {
                $fullName = trim($matches[1]);
                $fullName = preg_replace('/\s+/', ' ', $fullName);
                
                // Verificar que el nombre extraído tenga al menos parte del nombre corto
                if ($shortName && stripos($fullName, $shortName) !== false) {
                    return $fullName;
                }
                
                // Si el nombre extraído es significativamente más largo que el corto
                if (strlen($fullName) > strlen($shortName) + 5) {
                    return $fullName;
                }
            }
        }
        return null;
    }

    /**
     * Formatear RUT en formato chileno: 12.345.678-9
     */
    private function formatRut($rut)
    {
        // Limpiar el RUT
        $rut = preg_replace('/[^0-9kK]/', '', $rut);
        
        if (strlen($rut) < 8) {
            return $rut;
        }
        
        // Separar dígito verificador
        $verifier = substr($rut, -1);
        $number = substr($rut, 0, -1);
        
        // Formatear con puntos
        $formatted = number_format($number, 0, '', '.');
        
        return $formatted . '-' . $verifier;
    }
}
