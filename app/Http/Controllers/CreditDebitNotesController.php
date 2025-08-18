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

        $notes = CreditDebitNote::with('supplier', 'invoice')
            ->when($request->term, function ($query, $search) {
                $query->where('number', 'like', '%'.$search.'%');
            })
            ->orWhereHas('supplier', function($query) use ($term){
                $query->where('name', 'like', '%'.$term.'%');
            })
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->paginate(10);

        $notes->getCollection()->transform(function($note){
            return [
                'id'          => $note->id,
                'date'        => $note->date,
                'type'        => $note->type,
                'supplier'    => $note->supplier,
                'invoice'     => $note->invoice,
                'number'      => $note->number,
                'reason'      => $note->reason,
                'affects_inventory' => $note->affects_inventory,
            ];
        });

        return Inertia::render('CreditDebitNotes', compact('notes', 'term'));
    }
}
