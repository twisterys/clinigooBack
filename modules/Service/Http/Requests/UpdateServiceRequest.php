<?php

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'service_category_id' => ['sometimes', 'required', 'exists:service_categories,id'],
            'name' => ['sometimes', 'required'],
            'description' => ['nullable'],
            'base_price' => ['nullable', 'numeric'],
            'duration_minutes' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];

        if ($this->hasFile('icon')) {
            $rules['icon'] = ['nullable', 'file', 'mimes:png,svg,jpg,jpeg,webp', 'max:2048'];
        } else {
            $rules['icon'] = ['nullable'];
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }
}
