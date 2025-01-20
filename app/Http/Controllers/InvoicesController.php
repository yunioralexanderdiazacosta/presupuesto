<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    public function __invoke()
    {
        Inertia::render('Invoices');
    }
}
