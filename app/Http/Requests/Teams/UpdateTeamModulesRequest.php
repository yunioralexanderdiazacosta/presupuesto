<?php

namespace App\Http\Requests\Teams;

use App\Support\ModuleAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'disabled_modules' => 'array',
            'disabled_modules.*' => Rule::in(ModuleAccess::catalogKeys()),
        ];
    }
}
