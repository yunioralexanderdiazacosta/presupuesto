<?php

namespace App\Http\Requests\Users;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\ModuleAccess;

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
        $restrict = $this->boolean('restrict_modules');

        return [
            'name' => 'required',
            'username' => 'required|max:255|unique:users,username,'. $this->user->id,
            'email' => 'required|max:255|email',
            // Un usuario con acceso restringido por módulos puede no tener roles
            'roles' => $restrict ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
            'roles.*' => 'exists:roles,name',
            'restrict_modules' => 'boolean',
            'allowed_modules' => $restrict ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'allowed_modules.*' => Rule::in(ModuleAccess::catalogKeys()),
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'Debe seleccionar al menos un rol.',
            'roles.min' => 'Debe seleccionar al menos un rol.',
            'roles.*.exists' => 'El rol seleccionado no es válido.',
            'allowed_modules.required' => 'Debe seleccionar al menos un módulo permitido.',
            'allowed_modules.min' => 'Debe seleccionar al menos un módulo permitido.',
            'allowed_modules.*.in' => 'Uno de los módulos seleccionados no es válido.',
        ];
    }
}
