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
            'hours' => 'required|numeric|min:0.5',
            'bonus_type_id' => 'nullable|exists:bonus_types,id',
            'bonus_amount' => 'nullable|integer|min:0',
            'target_price' => 'nullable|integer|min:0',
            'target_price_bonus' => 'nullable|integer|min:0',
            'cost_center_id' => 'required|exists:cost_centers,id',
            'observations' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('hours')) {
                return;
            }

            $dailyYield = $this->route('dailyYield');
            $user = Auth::user();
            $hours = (float) $this->input('hours');

            $schedule = WorkSchedule::where('team_id', $user->team_id)
                ->where('season_id', session('season_id'))
                ->first();
            $maxHours = $schedule
                ? $schedule->hoursForDayOfWeek(Carbon::parse($dailyYield->date)->dayOfWeekIso)
                : 8.0;

            // Si el horario marca 0h para este día, permitir sin límite
            if ($maxHours <= 0) {
                return;
            }

            if ($hours > $maxHours) {
                $validator->errors()->add('hours', "Máximo {$maxHours}h permitidas para este día.");
                return;
            }

            // Sumar horas de otras líneas (excluyendo la actual)
            $usedHours = DailyYield::where('employee_id', $dailyYield->employee_id)
                ->where('date', $dailyYield->date)
                ->where('team_id', $user->team_id)
                ->where('id', '!=', $dailyYield->id)
                ->sum('hours');

            $remaining = round($maxHours - $usedHours, 1);

            if ($hours > $remaining) {
                $validator->errors()->add('hours', "Solo quedan {$remaining}h disponibles (máx {$maxHours}h, otras líneas {$usedHours}h).");
            }
        });
    }
}
