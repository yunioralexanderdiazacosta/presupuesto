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
            if ($validator->errors()->hasAny(['employee_id', 'date', 'hours'])) {
                return;
            }

            $user = Auth::user();
            $date = $this->input('date');
            $hours = (float) $this->input('hours');
            $employeeId = $this->input('employee_id');

            // Obtener máximo de horas según horario del día
            $schedule = WorkSchedule::where('team_id', $user->team_id)
                ->where('season_id', session('season_id'))
                ->first();
            $maxHours = $schedule
                ? $schedule->hoursForDayOfWeek(Carbon::parse($date)->dayOfWeekIso)
                : 8.0;

            // Si el horario marca 0h para este día, permitir sin límite
            if ($maxHours <= 0) {
                return;
            }

            // Validar que las horas de esta línea no excedan el máximo
            if ($hours > $maxHours) {
                $validator->errors()->add('hours', "Máximo {$maxHours}h permitidas para este día.");
                return;
            }

            // Sumar horas ya registradas para este empleado en esta fecha
            $usedHours = DailyYield::where('employee_id', $employeeId)
                ->where('date', $date)
                ->where('team_id', $user->team_id)
                ->sum('hours');

            $remaining = round($maxHours - $usedHours, 1);

            if ($hours > $remaining) {
                $validator->errors()->add('hours', "Solo quedan {$remaining}h disponibles (máx {$maxHours}h, usadas {$usedHours}h).");
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
            'hours.required' => 'Las horas son obligatorias.',
            'hours.min' => 'Mínimo 0.5 horas.',
            'cost_center_id.required' => 'El centro de costo es obligatorio.',
        ];
    }
}
