<?php

namespace App\Http\Requests\Consumptions;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsumptionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'cost_center_id' => 'required|exists:cost_centers,id',
            'date' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'team_id' => 'required|exists:teams,id',
            'season_id' => 'required|exists:seasons,id',
            'operation_id' => 'nullable|exists:operations,id',
            'machinery_id' => 'nullable|exists:machineries,id',
            'project_id' => 'nullable|exists:projects,id',
            'observations' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.invoice_item_id' => 'required|exists:invoice_product,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.observations' => 'nullable|string',
        ];
    }
}
