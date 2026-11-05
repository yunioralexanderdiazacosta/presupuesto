<?php

namespace App\Http\Requests\DailyHarvestSummary;

use Illuminate\Foundation\Http\FormRequest;

class DailyHarvestSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'nullable|date_format:Y-m-d',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer',
            'reason_ids' => 'nullable|array',
            'reason_ids.*' => 'integer',
        ];
    }

    public function summaryDate(): string
    {
        return $this->validated()['date'] ?? now()->toDateString();
    }

    public function branchIds(): array
    {
        return array_map('intval', $this->validated()['branch_ids'] ?? []);
    }

    public function reasonIds(): array
    {
        return array_map('intval', $this->validated()['reason_ids'] ?? []);
    }
}
