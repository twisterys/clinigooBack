<?php

namespace App\Http\Requests\Admin;

use App\Services\IdentityUniquenessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePatientRequest extends FormRequest
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
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'firstName' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
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
            if (empty($this->input('first_name')) && empty($this->input('firstName')) && empty($this->input('name'))) {
                $validator->errors()->add('name', 'The name or first_name field is required.');
            }
            if (! empty($this->input('email')) && IdentityUniquenessService::isEmailTaken($this->input('email'))) {
                $validator->errors()->add('email', 'Cette adresse email est déjà associée à un compte.');
            }
            if (! empty($this->input('profile_email')) && IdentityUniquenessService::isEmailTaken($this->input('profile_email'))) {
                $validator->errors()->add('profile_email', 'Cette adresse email est déjà associée à un compte.');
            }
            if (! empty($this->input('phone')) && IdentityUniquenessService::isPhoneTaken($this->input('phone'))) {
                $validator->errors()->add('phone', 'Ce numéro de téléphone est déjà associé à un compte.');
            }
            if (! empty($this->input('cin_amo_cnss')) && IdentityUniquenessService::isCinTaken($this->input('cin_amo_cnss'))) {
                $validator->errors()->add('cin_amo_cnss', 'Ce numéro de CIN est déjà associé à un compte.');
            }
        });
    }
}
