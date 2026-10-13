<?php

namespace Modules\Professions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfessionRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['nullable'],
            'slug' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
            'documents' => ['nullable', 'array'],
            'documents.*.id' => ['required_with:documents.*', 'integer', 'exists:profession_documents,id'],
            'documents.*.required' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
