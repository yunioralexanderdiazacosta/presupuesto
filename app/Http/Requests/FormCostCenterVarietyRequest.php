<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormCostCenterVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cost_center_id'       => 'required|exists:cost_centers,id',
            'variety_id'           => 'required|exists:varieties,id',
            'fruit_id'             => 'required|exists:fruits,id',
            'rootstock_id'         => 'nullable|exists:rootstocks,id',
            'development_state_id' => 'nullable|exists:development_states,id',
            'surface'              => 'required|numeric|min:0',
            'year_plantation'      => 'nullable|integer|min:1900|max:2100',
            'observations'         => 'nullable|string',
        ];
    }
}
