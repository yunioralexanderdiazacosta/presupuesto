<?php

namespace App\Http\Requests\Groupings;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupingRequest extends FormRequest
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
            'name' => ['required','string','max:255'],
            'season_id' => ['required','exists:seasons,id'],
            'team_id' => ['nullable','exists:teams,id'],
            'cost_center_ids' => ['nullable','array'],
            'cost_center_ids.*' => ['exists:cost_centers,id'],
        ];
    }
}
