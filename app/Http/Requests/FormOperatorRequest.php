<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'El nombre es obligatorio.',
            'name.max'          => 'El nombre no puede exceder 255 caracteres.',
            'position.required' => 'El cargo es obligatorio.',
            'position.max'      => 'El cargo no puede exceder 255 caracteres.',
        ];
    }
}
