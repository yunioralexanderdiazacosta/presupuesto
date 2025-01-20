<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CreateInvoiceController extends Controller
{
    public function __invoke()
    {
        $typeDocuments = TypeDocument::get()->transform(function($type){
            return [
                'label' => $type->name,
                'value' => $type->id
            ];
        });

        return Inertia::render('Invoices/Create', compact('typeDocuments'));
    }
}
