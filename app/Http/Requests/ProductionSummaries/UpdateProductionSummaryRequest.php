<?php

namespace App\Http\Requests\ProductionSummaries;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductionSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kg_harvested'  => 'required|numeric|min:0',
            'kg_exported'   => 'nullable|numeric|min:0',
            'observations'  => 'nullable|string',
        ];
    }
}
