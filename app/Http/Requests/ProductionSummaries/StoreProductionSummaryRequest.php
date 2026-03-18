<?php

namespace App\Http\Requests\ProductionSummaries;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*.variety_id'    => 'required|exists:varieties,id',
            '*.kg_harvested'  => 'required|numeric|min:0',
            '*.kg_exported'   => 'nullable|numeric|min:0',
            '*.observations'  => 'nullable|string',
        ];
    }
}
