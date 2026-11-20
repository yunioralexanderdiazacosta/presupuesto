<?php

namespace App\Support;

class ProportionalSplit
{
    /**
     * Reparte un total en proporción a los pesos, con los decimales indicados.
     * Los redondeos se asignan a las partes con mayor resto para que la suma sea exactamente el total.
     *
     * @param  array<int|string, float|int>  $weights
     * @return array<int|string, float> Mismas claves que $weights
     */
    public static function split(float $total, array $weights, int $decimals = 2): array
    {
        $weightSum = array_sum($weights);
        if ($weightSum <= 0) {
            return array_map(fn() => 0.0, $weights);
        }

        $scale = 10 ** $decimals;
        $totalUnits = (int) round($total * $scale);

        $units = [];
        $remainders = [];
        foreach ($weights as $key => $weight) {
            $exact = $totalUnits * $weight / $weightSum;
            $units[$key] = (int) floor($exact + 1e-9);
            $remainders[$key] = $exact - $units[$key];
        }

        $missing = $totalUnits - array_sum($units);
        if ($missing > 0) {
            // Ordenar por resto descendente; a igual resto gana la parte que viene primero
            $order = array_keys($remainders);
            usort($order, fn($a, $b) => $remainders[$b] <=> $remainders[$a] ?: array_search($a, array_keys($weights)) <=> array_search($b, array_keys($weights)));
            foreach (array_slice($order, 0, $missing) as $key) {
                $units[$key]++;
            }
        }

        return array_map(fn($u) => $u / $scale, $units);
    }
}
