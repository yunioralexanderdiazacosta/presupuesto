<?php

namespace App\Http\Requests\Estimates;

use App\Models\EstimateStatus;
use App\Models\Fruit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreEstimateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'fruit_id' => 'required|integer|exists:fruits,id',
            'copy_from_id' => 'nullable|integer|exists:estimate_status,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ingresa un nombre para la estimación.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'fruit_id.required' => 'Selecciona una especie.',
            'fruit_id.exists' => 'La especie seleccionada no es válida.',
            'copy_from_id.exists' => 'La estimación a copiar no existe.',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $teamId = Auth::user()->team_id;
            $fruitId = (int) $this->input('fruit_id');

            if (!Fruit::where('id', $fruitId)->where('team_id', $teamId)->exists()) {
                $validator->errors()->add('fruit_id', 'La especie no pertenece a tu equipo.');
                return;
            }

            $name = trim((string) $this->input('name'));
            if (EstimateStatus::where('fruit_id', $fruitId)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                $validator->errors()->add('name', 'Ya existe una estimación con ese nombre para esta especie.');
            }

            // Solo se copia entre estimaciones de la misma especie: los cuarteles-variedad son de un único frutal
            if ($this->filled('copy_from_id')) {
                $source = EstimateStatus::find($this->input('copy_from_id'));
                if (!$source || (int) $source->fruit_id !== $fruitId) {
                    $validator->errors()->add('copy_from_id', 'Solo puedes copiar una estimación de la misma especie.');
                }
            }
        }];
    }
}
