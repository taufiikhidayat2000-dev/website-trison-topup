<?php

namespace App\Http\Requests\Cms\PPOB\PPOBProduct;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportPPOBProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:5120',
        ];
    }
}
