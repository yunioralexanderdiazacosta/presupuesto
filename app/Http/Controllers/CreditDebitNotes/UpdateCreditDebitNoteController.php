<?php

namespace App\Http\Controllers\CreditDebitNotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormCreditDebitNoteRequest;
use App\Models\CreditDebitNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UpdateCreditDebitNoteController extends Controller
{
    public function __invoke(CreditDebitNote $note, Request $request)
    {
        // Validar solo los campos editables
        $validated = $request->validate([
            'number' => 'required|string|max:255',
            'date'   => 'required|date',
            'reason' => 'nullable|string',
        ]);

        // Por motivos fiscales y de auditoría, solo se permite editar:
        // - Número de documento
        // - Fecha (dentro del mismo período)
        // - Motivo/razón
        //
        // NO se permite editar: tipo, proveedor, factura, items, cantidades, precios, checkboxes
        $note->update([
            'number' => $validated['number'],
            'date'   => $validated['date'],
            'reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('success', 'Nota actualizada correctamente');
    }
}
