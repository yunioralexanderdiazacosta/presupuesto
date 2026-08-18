<?php

namespace App\Http\Requests\PaymentRequests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'character' => 'required|in:normal,importante,urgente',
            'concept_observations' => 'nullable|string|max:1000',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'cost_center_ids' => 'required|array|min:1',
            'cost_center_ids.*' => 'exists:cost_centers,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ];
    }
}
