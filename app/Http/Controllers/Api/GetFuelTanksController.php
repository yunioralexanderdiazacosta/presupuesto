<?php

namespace App\Http\Controllers\Api;

use App\Models\FuelTank;
use Illuminate\Support\Facades\Auth;

class GetFuelTanksController
{
    public function __invoke()
    {
        $user = Auth::user();

        return FuelTank::with(['branch', 'product'])
            ->where('team_id', $user->team_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'value'        => $t->id,
                'label'        => $t->name,
                'branch_id'    => $t->branch_id,
                'branch_name'  => $t->branch?->name,
                'product_id'   => $t->product_id,
                'product_name' => $t->product?->name,
                'capacity'     => $t->capacity,
            ]);
    }
}
