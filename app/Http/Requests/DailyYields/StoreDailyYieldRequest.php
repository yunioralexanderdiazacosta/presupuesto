<?php

namespace App\Http\Requests\DailyYields;

use App\Models\DailyYield;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreDailyYieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
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
            if ($validator->errors()->hasAny(['employee_id', 'date', 'workdays'])) {
                return;
            }

            $user = Auth::user();
            $date = $this->input('date');
            $workdays = (float) $this->input('workdays');
            $employeeId = $this->input('employee_id');

            // Consultar horario solo para saber si es día laborable
            $schedule = WorkSchedule::where('team_id', $user->team_id)
                ->where('season_id', session('season_id'))
                ->first();
            $scheduleHours = $schedule
                ? $schedule->hoursForDayOfWeek(Carbon::parse($date)->dayOfWeekIso)
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

            // Sumar workdays ya registradas para este empleado en esta fecha
            $usedWorkdays = DailyYield::where('employee_id', $employeeId)
                ->where('date', $date)
                ->where('team_id', $user->team_id)
                ->sum('workdays');

            $remaining = round($maxWorkday - $usedWorkdays, 2);

            if ($workdays > $remaining) {
                $validator->errors()->add('workdays', "Solo quedan {$remaining} JH disponibles (usadas {$usedWorkdays} JH).");
            }
        });
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'El trabajador es obligatorio.',
            'labor_type_id.required' => 'La labor es obligatoria.',
            'labor_rate_id.required_if' => 'El trato es obligatorio cuando el tipo es "a trato".',
            'rate.required' => 'La tarifa es obligatoria.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'workdays.required' => 'La jornada es obligatoria.',
            'workdays.min' => 'Mínimo 0.1 JH.',
            'workdays.max' => 'Máximo 1.0 JH por línea.',
            'cost_center_id.required' => 'El centro de costo es obligatorio.',
        ];
    }
}
