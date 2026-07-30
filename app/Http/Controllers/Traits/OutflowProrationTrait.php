<?php

namespace App\Http\Controllers\Traits;

/**
 * Lógica de prorrateo de consumos (outflows) por razón social del centro de costo.
 *
 * La razón social de un consumo se toma del centro de costo asignado en la
 * salida (NO de la factura de origen). Un consumo puede repartirse en varios
 * CC de distintas razones sociales, por eso el monto se prorratea por
 * superficie (hectáreas). Ambos dashboards de salidas usan estos helpers para
 * calcular el "consumido" de forma idéntica.
 */
trait OutflowProrationTrait
{
    /**
     * Indica si un consumo "toca" alguna de las razones sociales filtradas, es
     * decir, si al menos uno de sus centros de costo pertenece a esa(s) RS.
     * Sin filtro devuelve true (todos los consumos cuentan).
     *
     * Requiere el outflow con costCenters.costCenter cargado.
     */
    protected function outflowMatchesCompanyReason($outflow, $company_reason_id): bool
    {
        if (!$company_reason_id) return true;
        $ids = is_array($company_reason_id) ? $company_reason_id : [$company_reason_id];
        return $outflow->costCenters->contains(
            fn($occ) => $occ->costCenter && in_array((int) $occ->costCenter->company_reason_id, $ids, true)
        );
    }

    /**
     * Calcula el monto de un consumo prorrateado entre sus centros de costo por
     * superficie (hectáreas). Si se pasa $company_reason_id, devuelve solo la
     * porción correspondiente a los CC de esa(s) razón(es) social(es).
     *
     * El monto completo (cantidad × precio) es único por consumo; el pivote
     * outflow_cost_center no guarda cantidad por CC, por eso se reparte por
     * superficie. Si la superficie total es 0, se reparte en partes iguales.
     *
     * Requiere el outflow con invoiceProduct, creditDebitNoteItem y
     * costCenters.costCenter cargados.
     */
    protected function proratedOutflowAmount($outflow, $company_reason_id = null): float
    {
        // Monto completo del consumo (cantidad × precio unitario)
        $unitPrice = 0.0;
        if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
            $unitPrice = (float) $outflow->invoiceProduct->unit_price;
        } elseif ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
            $unitPrice = (float) $outflow->creditDebitNoteItem->unit_price;
        }
        $full = (float) $outflow->quantity * $unitPrice;
        if ($full == 0.0) return 0.0;

        $ccs = $outflow->costCenters;

        // Sin centros de costo: sin filtro aporta completo; con filtro no aporta
        if ($ccs === null || $ccs->isEmpty()) {
            return $company_reason_id ? 0.0 : $full;
        }

        // CC que corresponden a la(s) razón(es) social(es) filtradas (o todos)
        if ($company_reason_id) {
            $ids = is_array($company_reason_id) ? $company_reason_id : [$company_reason_id];
            $matching = $ccs->filter(
                fn($occ) => $occ->costCenter && in_array((int) $occ->costCenter->company_reason_id, $ids, true)
            );
        } else {
            $matching = $ccs;
        }

        if ($matching->isEmpty()) return 0.0;

        // Prorrateo por superficie; si la superficie total es 0, reparto equitativo
        $totalSurface = $ccs->sum(fn($occ) => (float) ($occ->costCenter->surface ?? 0));
        if ($totalSurface > 0) {
            $matchSurface = $matching->sum(fn($occ) => (float) ($occ->costCenter->surface ?? 0));
            return $full * ($matchSurface / $totalSurface);
        }
        return $full * ($matching->count() / $ccs->count());
    }
}
