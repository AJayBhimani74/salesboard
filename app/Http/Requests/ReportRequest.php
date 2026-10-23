<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Reports\ReportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'region' => ['nullable', Rule::in(Order::REGIONS)],
            'category' => ['nullable', Rule::in(Order::CATEGORIES)],
        ];
    }

    public function filters(): array
    {
        return ReportService::normalise($this->validated());
    }
}
