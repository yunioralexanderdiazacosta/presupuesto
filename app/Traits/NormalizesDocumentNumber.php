<?php

namespace App\Traits;

trait NormalizesDocumentNumber
{
    /**
     * Normaliza un N° de documento para comparaciones tolerantes a formato
     * (mayúsculas, espacios, guiones, ceros a la izquierda).
     */
    public static function normalizeDocumentNumber(?string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', (string) $value);
        return ltrim(strtoupper($clean), '0');
    }
}
