<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['project_id' => ['required', 'exists:projects,id'], 'billing_period_id' => ['nullable', 'exists:billing_periods,id'], 'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'], 'payment_date' => ['required', 'date'], 'method' => ['nullable', Rule::enum(PaymentMethod::class)], 'reference' => ['nullable', 'string', 'max:255'], 'note' => ['nullable', 'string', 'max:20000']];
    }

    public function messages(): array
    {
        return ['amount.gt' => 'Le montant doit être supérieur à 0.', 'project_id.required' => 'Veuillez sélectionner un projet.'];
    }
}
