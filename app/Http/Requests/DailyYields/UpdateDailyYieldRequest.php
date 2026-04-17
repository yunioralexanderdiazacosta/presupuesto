<?php

namespace App\Http\Requests\DailyYields;

use App\Models\DailyYield;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateDailyYieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_type' => 'required|in:trato,dia',
            'labor_type_id' => 'required|exists:labor_types,id',
            'labor_rate_id' => 'nullable|required_if:payment_type,trato|exists:labor_rates,id',
            'rate' => 'required|integer|min:0',
            'quantity' => 'required|numeric|min:0',
            'workdays' => 'required|numeric|min:0.1|max:1',
            'bonus_type_id' => 'nullable|exists:bonus_types,id',
            'bonus_amount' => 'nullable|integer|min:0',
            'target_price' => 'nullable|integer|min:0',
            'target_price_bonus' => 'nullable|integer|min:0',
            'cost_center_ids' => 'nullable|array',
            'cost_center_ids.*' => 'exists:cost_centers,id',
            'observations' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('workdays')) {
                return;
            }

            $dailyYield = $this->route('dailyYield');
            $user = Auth::user();
            $workdays = (float) $this->input('workdays');

            // Consultar horario solo para saber si es día laborable
            $schedule = WorkSchedule::where('team_id', $user->team_id)
                ->where('season_id', session('season_id'))
                ->first();
            $scheduleHours = $schedule
                ? $schedule->hoursForDayOfWeek(Carbon::parse($dailyYield->date)->dayOfWeekIso)
                : 8.0;

            // Si el horario marca 0h para este día (día libre), permitir sin límite
            if ($scheduleHours <= 0) {
                return;
            }

            // Tope fijo: 1.0 JH por día laborable
            $maxWorkday = 1.0;

            if ($workdays > $maxWorkday) {
                $validator->errors()->add('workdays', "Máximo {$maxWorkday} JH permitidas para este día.");
                return;
            }

            // Sumar workdays de otras líneas (excluyendo la actual)
            $usedWorkdays = DailyYield::where('employee_id', $dailyYield->employee_id)
                ->where('date', $dailyYield->date)
                ->where('team_id', $user->team_id)
                ->where('id', '!=', $dailyYield->id)
                ->sum('workdays');

            $remaining = round($maxWorkday - $usedWorkdays, 2);

            if ($workdays > $remaining) {
                $validator->errors()->add('workdays', "Solo quedan {$remaining} JH disponibles (otras líneas {$usedWorkdays} JH).");
            }
        });
    }
}
