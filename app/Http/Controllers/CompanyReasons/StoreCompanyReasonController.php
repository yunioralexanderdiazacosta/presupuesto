<?php

namespace App\Http\Controllers\CompanyReasons;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormCompanyReasonRequest;
use App\Models\CompanyReason;

class StoreCompanyReasonController extends Controller
{
    public function __invoke(FormCompanyReasonRequest $request)
    {
        $user = Auth::user();

        CompanyReason::Create([
            'name' => $request->name,
            'rut' => $request->rut,
            'legal_representative' => $request->legal_representative,
            'rut_representative' => $request->rut_representative, 
            'address' => $request->address,
            'team_id' => $user->team_id
        ]);
    }
}
