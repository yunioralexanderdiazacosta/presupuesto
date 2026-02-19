<?php

namespace App\Http\Controllers\CompanyReasons;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyReason;
use App\Models\CostCenter;
use App\Models\Invoice;
use App\Models\Machinery;

class DeleteCompanyReasonController extends Controller
{
    public function __invoke(CompanyReason $companyReason)
    {
        $relations = [];

        $invoices = Invoice::where('company_reason_id', $companyReason->id)->count();
        if ($invoices > 0) {
            $relations[] = "{$invoices} factura(s)";
        }

        $costCenters = CostCenter::where('company_reason_id', $companyReason->id)->count();
        if ($costCenters > 0) {
            $relations[] = "{$costCenters} centro(s) de costo";
        }

        $machineries = Machinery::where('company_reason_id', $companyReason->id)->count();
        if ($machineries > 0) {
            $relations[] = "{$machineries} maquinaria(s)";
        }

        if (!empty($relations)) {
            $detail = implode(', ', $relations);
            return back()->with('error', "No se puede eliminar \"{$companyReason->name}\" porque tiene registros asociados: {$detail}.");
        }

        $companyReason->delete();
        return back()->with('success', 'Razón social eliminada correctamente.');
    }
}
