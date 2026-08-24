<?php

namespace App\Http\Requests;

use App\Enums\BillingType;
use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'exists:businesses,id'], 'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ProjectStatus::class)], 'billing_type' => ['required', Rule::enum(BillingType::class)],
            'amount' => ['required', 'decimal:0,2', 'min:0'], 'currency' => ['required', 'string', 'size:3'],
            'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'next_payment_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string'],
            'services' => ['nullable', 'array'], 'services.*' => ['integer', 'exists:services,id'],
            'team' => ['nullable', 'array'], 'team.*.selected' => ['nullable', 'boolean'], 'team.*.role' => ['nullable', 'string', 'max:255'],
            'create_first_period' => ['nullable', 'boolean'], 'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return ['business_id.required' => 'Veuillez sélectionner une entreprise.', 'name.required' => 'Le nom du projet est obligatoire.', 'amount.required' => 'Le montant est obligatoire.'];
    }
}
