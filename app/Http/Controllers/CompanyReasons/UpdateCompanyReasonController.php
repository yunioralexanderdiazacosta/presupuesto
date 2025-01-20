<?php

namespace App\Http\Controllers\CompanyReasons;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormCompanyReasonRequest;
use App\Models\CompanyReason;


class UpdateCompanyReasonController extends Controller
{
    public function __invoke(CompanyReason $companyReason, FormCompanyReasonRequest $request)
    {
        $companyReason->name = $request->name;
        $companyReason->rut = $request->rut;
        $companyReason->legal_representative = $request->legal_representative;
        $companyReason->rut_representative = $request->rut_representative;
        $companyReason->address = $request->address;
        $companyReason->save();
    }
}
