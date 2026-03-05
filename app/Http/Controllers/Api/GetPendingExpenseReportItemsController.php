<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReportItem;
use Illuminate\Support\Facades\Auth;

class GetPendingExpenseReportItemsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $seasonId = session('season_id');

        $items = ExpenseReportItem::whereNull('invoice_id')
            ->whereHas('expenseReport', function ($q) use ($user, $seasonId) {
                $q->where('team_id', $user->team_id)
                    ->where('season_id', $seasonId)
                    ->whereIn('status', ['aprobada', 'pagada']);
            })
            ->with([
                'expenseReport:id,number,status',
                'supplier:id,name',
            ])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'expense_report_number' => $item->expenseReport->number ?? '',
                    'expense_report_id' => $item->expense_report_id,
                    'date' => $item->date->format('d/m/Y'),
                    'date_raw' => $item->date->format('Y-m-d'),
                    'supplier_id' => $item->supplier_id,
                    'supplier_name' => $item->supplier->name ?? '',
                    'product_name' => $item->product_name ?? '',
                    'description' => $item->description,
                    'amount' => (float) $item->amount,
                    'has_receipt' => !empty($item->receipt_path),
                ];
            });

        return response()->json($items);
    }
}
