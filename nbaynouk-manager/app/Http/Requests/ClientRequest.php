<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'notes' => ['nullable', 'string']];
    }

    public function messages(): array
    {
        return ['name.required' => 'Le nom du client est obligatoire.', 'email.email' => 'Cette adresse e-mail n’est pas valide.'];
    }
}
