<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class InvoiceOcrService
{
    public function __construct()
    {
        // No requiere API key - procesamiento 100% local
    }

    public function extractFromPdf($file)
    {
        try {
            Log::info('Iniciando extracción PDF con smalot/pdfparser (local)', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);

            // Parsear PDF localmente
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $fullText = $pdf->getText();

            Log::info('Texto extraído del PDF', [
                'longitud' => strlen($fullText),
                'primeros_500' => substr($fullText, 0, 500),
            ]);

            if (empty(trim($fullText))) {
                throw new \Exception('No se pudo extraer texto del PDF. El archivo puede ser una imagen escaneada.');
            }

            return $this->extractFieldsFromText($fullText);

        } catch (\Exception $e) {
            Log::error('PDF parsing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Extraer todos los campos de la factura desde el texto plano del PDF
     */
    private function extractFieldsFromText($fullText)
    {
        // Extraer RUT del proveedor (primer RUT encontrado = emisor)
        $supplierTaxId = $this->extractSupplierRutFromText($fullText);

        // Extraer nombre/razón social del proveedor
        $supplierName = $this->extractSupplierNameFromText($fullText, $supplierTaxId);

        // Extraer RUTs - el segundo suele ser el cliente/receptor
        $allRuts = $this->extractAllRuts($fullText);
        $customerTaxId = null;
        if (count($allRuts) >= 2) {
            // El segundo RUT distinto es el receptor
            foreach ($allRuts as $rut) {
                if ($rut !== $supplierTaxId) {
                    $customerTaxId = $rut;
                    break;
                }
            }
        }

        // Extraer número de documento
        $invoiceNumber = $this->extractInvoiceNumber($fullText);

        // Extraer fecha
        $date = $this->extractDate($fullText);

        // Extraer monto total
        $totalAmount = $this->extractTotalAmount($fullText);

        // Detectar tipo de documento
        $documentType = $this->detectDocumentType($fullText);

        // Extraer nombre del cliente
        $customerName = $this->extractCustomerName($fullText);

        Log::info('Campos extraídos del PDF', [
            'supplier_name' => $supplierName,
            'supplier_tax_id' => $supplierTaxId,
            'customer_tax_id' => $customerTaxId,
            'invoice_number' => $invoiceNumber,
            'date' => $date,
            'total_amount' => $totalAmount,
            'document_type' => $documentType,
        ]);

        return [
            'invoice_number' => $invoiceNumber,
            'date' => $date,
            'document_type' => $documentType,
            'supplier_name' => $supplierName,
            'supplier_tax_id' => $supplierTaxId,
            'customer_name' => $customerName,
            'customer_tax_id' => $customerTaxId,
            'total_amount' => $totalAmount,
            'full_text' => trim($fullText),
        ];
    }

    /**
     * Extraer número de factura/documento
     */
    private function extractInvoiceNumber($text)
    {
        $patterns = [
            // N° 12345 o Nº 12345 o N° Factura 12345
            '/N[°º]\s*(?:Factura|Documento|Doc\.?)?\s*[:\s]*(\d{1,10})/i',
            // Folio N° 12345 o Folio: 12345
            '/Folio\s*(?:N[°º])?\s*[:\s]*(\d{1,10})/i',
            // FACTURA ELECTRONICA N° 12345
            '/FACTURA\s+(?:ELECTR[OÓ]NICA\s+)?N[°º]\s*(\d{1,10})/i',
            // NOTA DE CREDITO ELECTRONICA N° 12345
            '/NOTA\s+DE\s+(?:CR[EÉ]DITO|D[EÉ]BITO)\s+(?:ELECTR[OÓ]NICA\s+)?N[°º]\s*(\d{1,10})/i',
            // BOLETA ELECTRONICA N° 12345
            '/BOLETA\s+(?:ELECTR[OÓ]NICA\s+)?N[°º]\s*(\d{1,10})/i',
            // Documento N° 12345
            '/Documento\s*N[°º]\s*[:\s]*(\d{1,10})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Extraer fecha del documento
     */
    private function extractDate($text)
    {
        $patterns = [
            // Fecha Emisión: 15/01/2025 o 15-01-2025
            '/Fecha\s*(?:de\s+)?(?:Emisi[oó]n|emisi[oó]n)\s*[:\s]*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i',
            // Fecha: 15/01/2025
            '/Fecha\s*[:\s]+(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i',
            // 15 de Enero de 2025
            '/(\d{1,2})\s+de\s+(Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre)\s+(?:de\s+)?(\d{4})/i',
            // Formato ISO: 2025-01-15
            '/Fecha\s*[:\s]*(\d{4}-\d{2}-\d{2})/i',
            // Buscar cualquier fecha dd/mm/yyyy en el texto
            '/\b(\d{2}[\/-]\d{2}[\/-]\d{4})\b/',
        ];

        $monthMap = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
            'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
            'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12',
        ];

        foreach ($patterns as $i => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Formato "15 de Enero de 2025"
                if ($i === 2) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $month = $monthMap[strtolower($matches[2])] ?? '01';
                    $year = $matches[3];
                    return "{$year}-{$month}-{$day}";
                }

                $dateStr = $matches[1];

                // Convertir dd/mm/yyyy o dd-mm-yyyy a yyyy-mm-dd
                if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $dateStr, $parts)) {
                    return "{$parts[3]}-" . str_pad($parts[2], 2, '0', STR_PAD_LEFT) . "-" . str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                }

                // Si ya es ISO yyyy-mm-dd
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                    return $dateStr;
                }

                return $dateStr;
            }
        }

        return null;
    }

    /**
     * Extraer monto total
     */
    private function extractTotalAmount($text)
    {
        $patterns = [
            // TOTAL $ 1.234.567 o Total: $1.234.567
            '/TOTAL\s*\$?\s*[:\s]*\$?\s*([\d\.]+)/i',
            // Monto Total: $1.234.567
            '/Monto\s+Total\s*[:\s]*\$?\s*([\d\.]+)/i',
            // Total a Pagar: $1.234.567
            '/Total\s+a\s+Pagar\s*[:\s]*\$?\s*([\d\.]+)/i',
            // VALOR TOTAL $ 1.234.567
            '/VALOR\s+TOTAL\s*\$?\s*[:\s]*([\d\.]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Remover puntos de miles y convertir a número
                $amount = str_replace('.', '', $matches[1]);
                return (int) $amount;
            }
        }

        return null;
    }

    /**
     * Detectar tipo de documento desde el texto
     */
    private function detectDocumentType($text)
    {
        $textUpper = strtoupper($text);

        if (str_contains($textUpper, 'NOTA DE CREDITO') || str_contains($textUpper, 'NOTA DE CRÉDITO')) {
            return 'Nota de Crédito Electrónica';
        }
        if (str_contains($textUpper, 'NOTA DE DEBITO') || str_contains($textUpper, 'NOTA DE DÉBITO')) {
            return 'Nota de Débito Electrónica';
        }
        if (str_contains($textUpper, 'GUIA DE DESPACHO') || str_contains($textUpper, 'GUÍA DE DESPACHO')) {
            return 'Guía de Despacho Electrónica';
        }
        if (str_contains($textUpper, 'BOLETA ELECTRONICA') || str_contains($textUpper, 'BOLETA ELECTRÓNICA')) {
            return 'Boleta Electrónica';
        }
        if (str_contains($textUpper, 'FACTURA ELECTRONICA') || str_contains($textUpper, 'FACTURA ELECTRÓNICA') || str_contains($textUpper, 'FACTURA')) {
            return 'Factura Electrónica';
        }

        return null;
    }

    /**
     * Extraer nombre del cliente/receptor
     */
    private function extractCustomerName($text)
    {
        $patterns = [
            // Señor(es): NOMBRE o Sr(es): NOMBRE
            '/Se[ñn]or(?:es)?\s*[:\s]+([A-ZÁÉÍÓÚÑ][A-Za-záéíóúñÁÉÍÓÚÑ\s\.\/&]+)/i',
            // Razón Social Cliente: NOMBRE
            '/Raz[oó]n\s+Social\s*[:\s]+([A-ZÁÉÍÓÚÑ][A-Za-záéíóúñÁÉÍÓÚÑ\s\.\/&]+)/i',
            // Cliente: NOMBRE
            '/Cliente\s*[:\s]+([A-ZÁÉÍÓÚÑ][A-Za-záéíóúñÁÉÍÓÚÑ\s\.\/&]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $name = trim($matches[1]);
                // Limpiar el nombre (remover texto extra después del nombre)
                $name = preg_replace('/\s{2,}.*$/', '', $name);
                return $name;
            }
        }

        return null;
    }

    /**
     * Extraer nombre/razón social del proveedor desde texto
     */
    private function extractSupplierNameFromText($text, $supplierTaxId)
    {
        // Si tenemos RUT, intentar extraer nombre completo cerca del RUT
        if ($supplierTaxId) {
            $fullName = $this->extractFullSupplierName($text, $supplierTaxId, '');
            if ($fullName) {
                return $fullName;
            }
        }

        // Buscar patrones comunes de razón social del emisor
        // En facturas chilenas, la razón social del emisor suele estar al inicio
        $patterns = [
            // Líneas con formato de razón social típica
            '/^([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s\.\/&]+(?:LIMITADA|LTDA|S\.?A\.?|SPA|EIRL|S\.?R\.?L\.?))/im',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $name = trim($matches[1]);
                $name = preg_replace('/\s+/', ' ', $name);
                if (strlen($name) >= 5 && strlen($name) <= 100) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * Extraer todos los RUTs encontrados en el texto (únicos, en orden de aparición)
     */
    private function extractAllRuts($text)
    {
        $patterns = [
            '/RUT[:\s]+([0-9]{1,2}[\.\s]?[0-9]{3}[\.\s]?[0-9]{3}[-\s]?[0-9kK])/i',
            '/R\.U\.T[:\s]+([0-9]{1,2}[\.\s]?[0-9]{3}[\.\s]?[0-9]{3}[-\s]?[0-9kK])/i',
            '/\b([0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9kK])\b/',
            '/\b([0-9]{7,8}-[0-9kK])\b/',
        ];

        $foundRuts = [];
        $seen = [];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $rut) {
                    $cleanedRut = $this->cleanRut($rut);
                    if (strlen($cleanedRut) >= 8 && strlen($cleanedRut) <= 9 && !in_array($cleanedRut, $seen)) {
                        $foundRuts[] = $cleanedRut;
                        $seen[] = $cleanedRut;
                    }
                }
            }
        }

        return $foundRuts;
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
                
                // Si no hay nombre corto para comparar, aceptar si es razonable
                if (empty($shortName) && strlen($fullName) >= 5) {
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
