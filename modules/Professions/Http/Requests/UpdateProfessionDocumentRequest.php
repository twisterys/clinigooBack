<?php

namespace Modules\Professions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessionDocumentRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required'],
            'description' => ['nullable'],
            'formats' => ['nullable', 'array'],
            'formats.*' => ['string', 'max:20'],
            'enabled_formats' => ['nullable', 'array'],
            'enabled_formats.*' => ['string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
