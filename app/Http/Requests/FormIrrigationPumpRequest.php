<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormIrrigationPumpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'sectors' => 'required|array|min:1',
            'sectors.*.name' => 'required|string|max:255',
            'sectors.*.surface' => 'required|numeric|min:0.01',
            'sectors.*.observations' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la bomba es obligatorio',
            'sectors.required' => 'Debe agregar al menos un sector',
            'sectors.min' => 'Debe agregar al menos un sector',
            'sectors.*.name.required' => 'El nombre del sector es obligatorio',
            'sectors.*.surface.required' => 'La superficie del sector es obligatoria',
            'sectors.*.surface.min' => 'La superficie debe ser mayor a 0',
        ];
    }
}
