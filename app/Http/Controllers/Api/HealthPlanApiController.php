<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthPlan;
use Illuminate\Http\Request;

class HealthPlanApiController extends Controller
{
    public function index()
    {
        return HealthPlan::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($h) => ['value' => $h->id, 'label' => $h->name]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:60|unique:health_plans,name']);

        $plan = HealthPlan::create(['name' => $request->name]);

        return response()->json(['id' => $plan->id, 'name' => $plan->name]);
    }

    public function destroy(HealthPlan $healthPlan)
    {
        $healthPlan->delete();
        return response()->json(['success' => true]);
    }
}
