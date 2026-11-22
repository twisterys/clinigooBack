<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\IdentityUniquenessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $patientParam = $this->route('patient') ?? $this->route('id');
            $rawId = is_object($patientParam) ? $patientParam->id : $patientParam;
            $user = User::patients()->find($rawId)
                ?? User::patients()->whereHas('patientProfile', fn ($q) => $q->where('id', $rawId))->first();
            $ignoreUserId = $user?->id ?? (is_numeric($rawId) ? (int) $rawId : null);

            if (! empty($this->input('email')) && IdentityUniquenessService::isEmailTaken($this->input('email'), $ignoreUserId)) {
                $validator->errors()->add('email', 'Cette adresse email est déjà associée à un compte.');
            }
            if (! empty($this->input('profile_email')) && IdentityUniquenessService::isEmailTaken($this->input('profile_email'), $ignoreUserId)) {
                $validator->errors()->add('profile_email', 'Cette adresse email est déjà associée à un compte.');
            }
            if (! empty($this->input('phone')) && IdentityUniquenessService::isPhoneTaken($this->input('phone'), $ignoreUserId)) {
                $validator->errors()->add('phone', 'Ce numéro de téléphone est déjà associé à un compte.');
            }
            if (! empty($this->input('cin_amo_cnss')) && IdentityUniquenessService::isCinTaken($this->input('cin_amo_cnss'), $ignoreUserId)) {
                $validator->errors()->add('cin_amo_cnss', 'Ce numéro de CIN est déjà associé à un compte.');
            }
        });
    }
}
