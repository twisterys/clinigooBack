<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $patientParam = $this->route('patient') ?? $this->route('id');
        $rawId = is_object($patientParam) ? $patientParam->id : $patientParam;

        $user = User::patients()->find($rawId)
            ?? User::patients()->whereHas('patientProfile', fn ($q) => $q->where('id', $rawId))->first();

        $ignoreId = $user?->id ?? $rawId;

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'firstName' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($ignoreId),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birthday' => ['nullable', 'date'],
            'date_of_birth' => ['nullable', 'date'],
            'birthDate' => ['nullable', 'date'],
            'sexe' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'max:50'],
            'profile_email' => ['nullable', 'email', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'cin_amo_cnss' => ['nullable', 'string', 'max:100'],
            'medical_notes' => ['nullable', 'string'],
            'conditions' => ['nullable'],
        ];
    }
}
