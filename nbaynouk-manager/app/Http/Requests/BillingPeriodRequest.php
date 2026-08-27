<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillingPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'], 'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'], 'due_date' => ['required', 'date'], 'description' => ['nullable', 'string', 'max:255']];
    }
}
