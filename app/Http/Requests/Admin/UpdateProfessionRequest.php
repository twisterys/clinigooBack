<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessionRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['sometimes', 'required'],
            'description' => ['nullable'],
            'slug' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
            'documents' => ['nullable', 'array'],
            'documents.*.id' => ['required_with:documents.*', 'integer', 'exists:profession_documents,id'],
            'documents.*.required' => ['boolean'],
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
