<?php

namespace Modules\Professions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfessionDocumentRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['nullable'],
            'formats' => ['nullable', 'array'],
            'formats.*' => ['string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
