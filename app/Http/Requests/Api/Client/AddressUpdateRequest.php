<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;

class AddressUpdateRequest extends FormRequest
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

        $addressLine = $this->input('address_line') ?? $this->input('street') ?? $this->input('custom_name') ?? $this->input('customName');
        if ($addressLine) {
            $mergeData['address_line'] = $addressLine;
        }

        $building = $this->input('building_name') ?? $this->input('building');
        if ($building) {
            $mergeData['building_name'] = $building;
        }

        $door = $this->input('apartment_number') ?? $this->input('door') ?? $this->input('apartment');
        if ($door) {
            $mergeData['apartment_number'] = $door;
        }

        $intercom = $this->input('intercom_code') ?? $this->input('intercom');
        if ($intercom) {
            $mergeData['intercom_code'] = $intercom;
        }

        if ($this->has('is_default') || $this->has('isDefault')) {
            $mergeData['is_default'] = filter_var($this->input('is_default') ?? $this->input('isDefault'), FILTER_VALIDATE_BOOLEAN);
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
            'type' => ['nullable', 'string'],
            'label' => ['nullable', 'string'],
            'address_line' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
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
