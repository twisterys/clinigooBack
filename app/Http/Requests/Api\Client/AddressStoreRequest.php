<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;

class AddressStoreRequest extends FormRequest
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
            'label' => ['nullable', 'string'],
            'address_line' => ['required', 'string'],
            'city' => ['required', 'string'],
            'postal_code' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-999.9999999,999.9999999'],
            'longitude' => ['nullable', 'numeric', 'between:-999.9999999,999.9999999'],
            'instructions' => ['nullable', 'string'],
            'is_default' => ['required'],
        ];
    }
}
