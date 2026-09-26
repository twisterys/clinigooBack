<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;

class BeneficiaryStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $firstName = $this->input('first_name') ?? $this->input('firstName');
        $lastName = $this->input('last_name') ?? $this->input('lastName');

        if (empty($firstName) && empty($lastName)) {
            $fullName = $this->input('fullName') ?? $this->input('full_name') ?? $this->input('name') ?? '';
            $parts = explode(' ', trim($fullName), 2);
            $firstName = $parts[0] ?? 'Nouveau';
            $lastName = ! empty($parts[1]) ? $parts[1] : 'Bénéficiaire';
        }

        $dob = $this->input('date_of_birth') ?? $this->input('birthDate') ?? $this->input('birth_date');
        $cin = $this->input('cin_amo_cnss') ?? $this->input('cinNumber') ?? $this->input('cin_number');
        $notes = $this->input('medical_notes') ?? $this->input('allergies');
        $conditions = $this->input('conditions');

        if (is_array($conditions)) {
            $conditions = json_encode($conditions);
        }

        $this->merge([
            'first_name' => $firstName ?: 'Nouveau',
            'last_name' => $lastName ?: 'Bénéficiaire',
            'date_of_birth' => $dob,
            'cin_amo_cnss' => $cin,
            'medical_notes' => $notes,
            'conditions' => $conditions,
            'is_default' => filter_var($this->input('is_default') ?? $this->input('isDefault') ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'relationship' => ['nullable', 'string'],
            'gender' => ['nullable', 'string'],
            'date_of_birth' => ['nullable'],
            'phone' => ['nullable', 'string'],
            'medical_notes' => ['nullable', 'string'],
            'cin_amo_cnss' => ['nullable', 'string'],
            'conditions' => ['nullable'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
