<?php

namespace App\Http\Requests\Users;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Solo un Super Admin puede otorgar el rol Super Admin a otro usuario.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (in_array('Super Admin', (array) $this->input('roles', []), true) && !$this->user()?->hasRole('Super Admin')) {
                $validator->errors()->add('roles', 'No tienes permisos para asignar el rol Super Admin.');
            }
        });
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
            'username' => 'required|max:255|unique:users',
            'email' => 'required|max:255|email',
            'password' => 'required|max:255',
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
