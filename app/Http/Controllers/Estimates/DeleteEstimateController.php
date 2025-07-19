<?php

namespace App\Http\Controllers\Estimates;

use App\Http\Controllers\Controller;
use App\Models\Estimate;

class DeleteEstimateController extends Controller
{
    public function __invoke(Estimate $estimate)
    {
        // Delete related items if necessary
        // $estimate->items()->delete(); // Uncomment if there are related items to delete

        // Delete the estimate itself
        $estimate->delete();
        
        // Optionally, you can return a response or redirect
        return response()->json(['message' => 'Estimate deleted successfully.']);
    }
}
