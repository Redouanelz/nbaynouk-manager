<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\ProjectExpenseCategory;
use App\Enums\ProjectExpenseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = $this->route('project')->getKey();

        return [
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'expense_date' => ['required', 'date'],
            'category' => ['nullable', Rule::enum(ProjectExpenseCategory::class)],
            'status' => ['required', Rule::enum(ProjectExpenseStatus::class)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'supplier' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:20000'],
            'billing_period_id' => ['nullable', Rule::exists('billing_periods', 'id')->where('project_id', $projectId)],
            'service_id' => ['nullable', Rule::exists('project_service', 'service_id')->where(fn ($query) => $query->where('project_id', $projectId)->where('is_active', true))],
        ];
    }

    public function messages(): array
    {
        return ['amount.gt' => 'Le montant doit être supérieur à 0.', 'billing_period_id.exists' => 'La période doit appartenir à ce projet.', 'service_id.exists' => 'Le service doit être lié à ce projet.'];
    }
}
