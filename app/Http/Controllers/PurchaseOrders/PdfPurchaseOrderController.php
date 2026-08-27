<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PdfPurchaseOrderController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();

        if ((int) $purchaseOrder->team_id !== (int) $user->team_id) {
            abort(403);
        }

        $purchaseOrder->load([
            'team:id,name',
            'supplier',
            'companyReason',
            'costCenters:id,name',
            'requestedBy:id,name',
            'approvedBy:id,name',
            'items.product.unit',
            'items.unit',
        ]);

        $pdf = Pdf::loadView('pdfs.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream('orden-compra-' . $purchaseOrder->order_number . '.pdf');
    }
}
