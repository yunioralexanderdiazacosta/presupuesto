<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Variety;
use App\Models\Fruit;
use Inertia\Inertia;

class VarietiesController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $varieties = Variety::with('fruit')->where('team_id', $user->team_id)->paginate(10);

        $fruits = Fruit::where('team_id', $user->team_id)->get()->transform(function($fruit){
            return [
                'label' => $fruit->name,
                'value' => $fruit->id
            ];
        });

        return Inertia::render('Varieties', compact('varieties', 'fruits'));
    }
}
