<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $invoices = Invoice::where('team_id', $user->team_id)->paginate(10)->through(function($invoice){
            return [
                'id' => $invoice->id,
                'date' => $invoice->date,
                'number' => $invoice->number
            ];
        }); 

        return Inertia::render('Invoices', compact('invoices'));
    }
}
