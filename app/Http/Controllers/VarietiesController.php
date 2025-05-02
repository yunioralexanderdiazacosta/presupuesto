<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Variety;
use App\Models\Fruit;
use Inertia\Inertia;

class VarietiesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? '';

        $varieties = Variety::with('fruit')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->paginate(10)->withQueryString();

        $fruits = Fruit::where('team_id', $user->team_id)->get()->transform(function($fruit){
            return [
                'label' => $fruit->name,
                'value' => $fruit->id
            ];
        });

        return Inertia::render('Varieties', compact('varieties', 'fruits', 'term'));
    }
}
