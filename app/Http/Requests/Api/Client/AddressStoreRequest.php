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
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $addressLine = $this->input('address_line') ?? $this->input('street') ?? $this->input('custom_name') ?? $this->input('customName') ?? 'Tanger, Maroc';
        $city = $this->input('city') ?: 'Tanger';
        $label = $this->input('custom_name') ?? $this->input('customName') ?? $this->input('label') ?? $this->input('type') ?? 'home';
        $type = $this->input('type') ?? $this->input('label') ?? 'home';

        $building = $this->input('building_name') ?? $this->input('building');
        $door = $this->input('apartment_number') ?? $this->input('door') ?? $this->input('apartment');
        $intercom = $this->input('intercom_code') ?? $this->input('intercom');

        $this->merge([
            'label' => $label,
            'type' => $type,
            'address_line' => $addressLine,
            'city' => $city,
            'building_name' => $building,
            'apartment_number' => $door,
            'intercom_code' => $intercom,
            'is_default' => filter_var($this->input('is_default') ?? $this->input('isDefault') ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string'],
            'label' => ['nullable', 'string'],
            'address_line' => ['required', 'string'],
            'city' => ['required', 'string'],
            'postal_code' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'instructions' => ['nullable', 'string'],
            'floor' => ['nullable', 'string'],
            'apartment_number' => ['nullable', 'string'],
            'building_name' => ['nullable', 'string'],
            'intercom_code' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
