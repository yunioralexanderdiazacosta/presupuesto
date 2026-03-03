<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'name' => 'required',
            'username' => 'required|max:255|unique:users,username,'. $this->user->id,
            'email' => 'required|max:255|email',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name'
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'Debe seleccionar al menos un rol.',
            'roles.min' => 'Debe seleccionar al menos un rol.',
            'roles.*.exists' => 'El rol seleccionado no es válido.'
        ];
    }
}
