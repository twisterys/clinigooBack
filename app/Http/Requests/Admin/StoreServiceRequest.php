<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['nullable'],
            'icon' => ['nullable', 'file', 'mimes:png,svg,jpg,jpeg,webp', 'max:2048'],
            'base_price' => ['nullable', 'numeric'],
            'duration_minutes' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
