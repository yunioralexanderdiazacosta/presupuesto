<?php

namespace App\Http\Controllers\MonthlyDiscounts;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Month;
use App\Models\MonthlyDiscount;
use App\Models\MonthlyDiscountType;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MonthlyDiscountController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $discounts = MonthlyDiscount::with([
            'contract.employee',
            'discountType',
            'month',
        ])
            ->where('team_id', $user->team_id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($d) => [
                'id'                  => $d->id,
                'employee_name'       => $d->contract->employee->full_name ?? '-',
                'contract_id'         => $d->contract_id,
                'discount_type_id'    => $d->monthly_discount_type_id,
                'discount_type_name'  => $d->discountType->name ?? '-',
                'month_id'            => $d->month_id,
                'month_name'          => $d->month->name ?? '-',
                'amount'              => $d->amount,
                'observations'        => $d->observations,
            ]);

        $contracts = Contract::with('employee')
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->employee->full_name ?? "Contrato #{$c->id}",
            ]);

        $discountTypes = MonthlyDiscountType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($t) => ['value' => $t->id, 'label' => $t->name]);

        $months = Month::orderBy('id')->get(['id', 'name'])
            ->map(fn($m) => ['value' => $m->id, 'label' => $m->name]);

        return Inertia::render('MonthlyDiscounts/Index', [
            'discounts'     => $discounts,
            'contracts'     => $contracts,
            'discountTypes' => $discountTypes,
            'months'        => $months,
        ]);
    }
}
