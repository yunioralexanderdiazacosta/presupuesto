<?php

namespace App\Http\Requests\ProductionAnalysis;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveClassificationGroupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fruit_id' => ['required', Rule::exists('fruits', 'id')->where('team_id', $this->user()->team_id)],
            'classification_type' => ['required', 'string', 'max:50'],
            'groups' => ['present', 'array'],
            'groups.*.name' => ['required', 'string', 'max:60'],
            'groups.*.values' => ['required', 'array', 'min:1'],
            'groups.*.values.*' => ['required', 'string', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $seen = [];
            foreach ((array) $this->input('groups', []) as $group) {
                foreach ((array) ($group['values'] ?? []) as $value) {
                    $key = mb_strtolower(trim((string) $value));
                    if (isset($seen[$key])) {
                        $validator->errors()->add('groups', "El valor \"{$value}\" está en más de un grupo.");
                        return;
                    }
                    $seen[$key] = true;
                }
            }
        });
    }
}
