<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Supplier;
use Inertia\Inertia;
use App\Exports\SuppliersTemplateExport;
use App\Imports\SuppliersImport;
use Maatwebsite\Excel\Facades\Excel;

class SuppliersController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? '';

        $suppliers = Supplier::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%')->orWhere('rut', 'like', '%'.$search.'%');
        })
        ->where('team_id', $user->team_id)
        ->orderBy('name', 'asc')
        ->paginate(2000)
        ->withQueryString();

        return Inertia::render('Suppliers', compact('suppliers', 'term'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new SuppliersImport, $request->file('file'));
            return response()->json(['message' => 'Importación exitosa']);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = array_map(fn($failure) => [
                'row'    => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ], $e->failures());

            return response()->json([
                'message'  => 'Errores en el archivo',
                'failures' => $failures,
            ], 422);
        }
    }

    public function template()
    {
        return Excel::download(new SuppliersTemplateExport, 'plantilla_proveedores.xlsx');
    }
}
