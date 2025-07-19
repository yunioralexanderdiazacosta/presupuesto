<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class FormEstimateRequest extends FormRequest
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
               'estimate_name' => 'required|string|max:255',
            'kilos_ha' => 'required|integer',
            'cost_center_id' => 'required|exists:cost_centers,id',
            'season_id' => 'required|exists:seasons,id',
            'team_id' => 'required|exists:teams,id',
            'observations' => 'nullable|string',
           
           
        ];
    }
}
