<?php

namespace App\Http\Requests;

use App\Support\ActiveBudgetVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormBudgetVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ActiveBudgetVersion::isAdmin($this->user());
    }

    public function rules(): array
    {
        $editing = $this->route('budgetVersion');

        $rules = [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('budget_versions', 'name')
                    ->where('season_id', session('season_id'))
                    ->ignore($editing?->id),
            ],
            'observations' => 'nullable|string|max:1000',
        ];

        // Al crear se elige la versión desde la cual se copia
        if (!$editing) {
            $rules['source_version_id'] = [
                'required',
                'integer',
                Rule::exists('budget_versions', 'id')
                    ->where('season_id', session('season_id'))
                    ->where('team_id', $this->user()->team_id),
            ];
            $rules['activate'] = 'boolean';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'El nombre es obligatorio.',
            'name.unique'                => 'Ya existe una versión con ese nombre en esta temporada.',
            'name.max'                   => 'El nombre no puede superar los 100 caracteres.',
            'source_version_id.required' => 'Seleccione la versión desde la cual copiar.',
            'source_version_id.exists'   => 'La versión de origen no es válida para esta temporada.',
        ];
    }
}
