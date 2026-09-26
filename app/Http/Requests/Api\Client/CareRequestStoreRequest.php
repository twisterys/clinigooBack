<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;

class CareRequestStoreRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'scheduled_at' => ['required'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
