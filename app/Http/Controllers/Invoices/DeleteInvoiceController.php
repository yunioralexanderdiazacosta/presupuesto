<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;

class DeleteInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice)
    {
        $invoice->delete();
    }
}
