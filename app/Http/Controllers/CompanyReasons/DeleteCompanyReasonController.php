<?php

namespace App\Http\Controllers\CompanyReasons;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyReason;

class DeleteCompanyReasonController extends Controller
{
    public function __invoke(CompanyReason $companyReason)
    {
        $companyReason->delete();
    }
}
