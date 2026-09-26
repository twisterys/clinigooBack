<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;

class BeneficiaryUpdateRequest extends FormRequest
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
        $mergeData = [];

        $firstName = $this->input('first_name') ?? $this->input('firstName');
        $lastName = $this->input('last_name') ?? $this->input('lastName');

        if (empty($firstName) && empty($lastName) && ($this->has('fullName') || $this->has('name'))) {
            $fullName = $this->input('fullName') ?? $this->input('full_name') ?? $this->input('name') ?? '';
            $parts = explode(' ', trim($fullName), 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        }

        if (! empty($firstName)) {
            $mergeData['first_name'] = $firstName;
        }
        if (! empty($lastName)) {
            $mergeData['last_name'] = $lastName;
        }

        $dob = $this->input('date_of_birth') ?? $this->input('birthDate') ?? $this->input('birth_date');
        if (! empty($dob)) {
            $mergeData['date_of_birth'] = $dob;
        }

        $cin = $this->input('cin_amo_cnss') ?? $this->input('cinNumber') ?? $this->input('cin_number');
        if (! empty($cin)) {
            $mergeData['cin_amo_cnss'] = $cin;
        }

        $notes = $this->input('medical_notes') ?? $this->input('allergies');
        if (! empty($notes)) {
            $mergeData['medical_notes'] = $notes;
        }

        $conditions = $this->input('conditions');
        if (is_array($conditions)) {
            $conditions = json_encode($conditions);
        }
        if (! empty($conditions)) {
            $mergeData['conditions'] = $conditions;
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
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
