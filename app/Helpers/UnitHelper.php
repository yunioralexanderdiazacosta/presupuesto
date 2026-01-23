<?php

if (!function_exists('convertToBaseUnit')) {
    /**
     * Convierte una cantidad de una unidad a otra.
     * Soporta conversiones básicas: cc↔lt, gr↔kg, ml↔lt
     * 
     * REGLA CRÍTICA: Todas las cantidades en la tabla OUTFLOWS (kardex)
     * deben estar en la unidad BASE del producto.
     * 
     * @param float $quantity Cantidad a convertir
     * @param string $fromUnitName Nombre de la unidad origen (ej: "cc", "gr", "lt")
     * @param string $toUnitName Nombre de la unidad destino (ej: "lt", "kg")
     * @return float Cantidad convertida
     * 
     * @example convertToBaseUnit(1500, 'cc', 'lt') // returns 1.5
     * @example convertToBaseUnit(2500, 'gr', 'kg') // returns 2.5
     */
    function convertToBaseUnit($quantity, $fromUnitName, $toUnitName)
    {
        $from = strtolower(trim($fromUnitName));
        $to = strtolower(trim($toUnitName));
        
        // Si ya están en la misma unidad, no convertir
        if ($from === $to) {
            return $quantity;
        }
        
        // ========================================
        // CONVERSIONES cc ↔ lt (centímetros cúbicos a litros)
        // ========================================
        
        // cc → lt (dividir por 1000)
        if ($from === 'cc' && in_array($to, ['lt', 'l', 'lts', 'litro', 'litros'])) {
            return $quantity / 1000;
        }
        
        // lt → cc (multiplicar por 1000)
        if (in_array($from, ['lt', 'l', 'lts', 'litro', 'litros']) && $to === 'cc') {
            return $quantity * 1000;
        }
        
        // ========================================
        // CONVERSIONES ml ↔ lt (mililitros a litros)
        // ========================================
        
        // ml → lt (dividir por 1000)
        if ($from === 'ml' && in_array($to, ['lt', 'l', 'lts', 'litro', 'litros'])) {
            return $quantity / 1000;
        }
        
        // lt → ml (multiplicar por 1000)
        if (in_array($from, ['lt', 'l', 'lts', 'litro', 'litros']) && $to === 'ml') {
            return $quantity * 1000;
        }
        
        // ========================================
        // CONVERSIONES gr ↔ kg (gramos a kilogramos)
        // ========================================
        
        // gr → kg (dividir por 1000)
        if ($from === 'gr' && in_array($to, ['kg', 'k', 'kgs', 'kilogramo', 'kilogramos'])) {
            return $quantity / 1000;
        }
        
        // kg → gr (multiplicar por 1000)
        if (in_array($from, ['kg', 'k', 'kgs', 'kilogramo', 'kilogramos']) && $to === 'gr') {
            return $quantity * 1000;
        }
        
        // ========================================
        // CONVERSIONES mg ↔ gr (miligramos a gramos)
        // ========================================
        
        // mg → gr (dividir por 1000)
        if ($from === 'mg' && $to === 'gr') {
            return $quantity / 1000;
        }
        
        // gr → mg (multiplicar por 1000)
        if ($from === 'gr' && $to === 'mg') {
            return $quantity * 1000;
        }
        
        // ========================================
        // SIN CONVERSIÓN DISPONIBLE
        // ========================================
        
        // Si no hay conversión directa, devolver cantidad original
        // y registrar advertencia en log
        \Log::warning("No se pudo convertir unidad: {$fromUnitName} → {$toUnitName}. Cantidad original: {$quantity}");
        
        return $quantity;
    }
}
