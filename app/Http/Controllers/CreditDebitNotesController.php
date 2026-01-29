<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CreditDebitNote;
use Inertia\Inertia;

class CreditDebitNotesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $term = $request->term ?? '';

        $notes = CreditDebitNote::with(['supplier', 'invoice', 'items.product'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->when($request->term, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('number', 'like', '%'.$search.'%')
                      ->orWhereHas('supplier', function($subQuery) use ($search) {
                          $subQuery->where('name', 'like', '%'.$search.'%');
                      });
                });
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function($note){
                $productNames = $note->items->map(function($item) {
                    return $item->product ? $item->product->name : null;
                })->filter()->unique()->values()->all();
                $total = 0;
                foreach ($note->items as $item) {
                    $total += ($item->quantity * $item->unit_price);
                }
                return [
                    'id'          => $note->id,
                    'date'        => $note->date ? $note->date->format('d-m-Y') : null,
                    'type'        => $note->type,
                    'supplier'    => $note->supplier,
                    'invoice'     => $note->invoice,
                    'number'      => $note->number,
                    'reason'      => $note->reason,
                    'affects_inventory' => $note->affects_inventory,
                    'products'    => implode(', ', $productNames),
                    'total'       => $total,
                ];
            });

        return Inertia::render('CreditDebitNotes', compact('notes', 'term'));
    }
}
