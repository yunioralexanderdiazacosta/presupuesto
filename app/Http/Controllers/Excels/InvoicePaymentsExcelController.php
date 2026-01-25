<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\InvoicePaymentsExport;
use Maatwebsite\Excel\Facades\Excel;

class InvoicePaymentsExcelController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        if (!$season_id) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una campaña activa.');
        }

        $filename = 'pagos_facturas_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new InvoicePaymentsExport($user->team_id, $season_id),
            $filename
        );
    }
}
