<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReportItem;
use App\Traits\NormalizesDocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetMatchingExpenseReportItemController extends Controller
{
    use NormalizesDocumentNumber;

    /**
     * Busca un documento pendiente de rendición (sin factura vinculada) que
     * coincida con el proveedor + N° de documento que se está por guardar.
     * Se usa para ofrecer la vinculación con confirmación al crear una factura.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|integer',
            'number_document' => 'required|string',
        ]);

        $user = Auth::user();
        $normalized = static::normalizeDocumentNumber($request->number_document);

        if ($normalized === '') {
            return response()->json(['match' => null]);
        }

        $item = ExpenseReportItem::whereNull('invoice_id')
            ->where('supplier_id', $request->supplier_id)
            ->whereHas('expenseReport', function ($q) use ($user) {
                $q->where('team_id', $user->team_id)
                    ->whereIn('status', ['aprobada', 'pagada']);
            })
            ->with('expenseReport:id,number')
            ->get()
            ->first(fn ($i) => static::normalizeDocumentNumber($i->document_number) === $normalized);

        if (!$item) {
            return response()->json(['match' => null]);
        }

        return response()->json([
            'match' => [
                'id' => $item->id,
                'expense_report_id' => $item->expense_report_id,
                'expense_report_number' => $item->expenseReport->number ?? '',
                'date' => $item->date->format('d/m/Y'),
                'amount' => (float) $item->amount,
                'description' => $item->description,
            ],
        ]);
    }
}
