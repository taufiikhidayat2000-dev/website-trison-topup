<?php

namespace App\Http\Requests\Cms\PPOB\PPOBBrand;

use App\Http\Requests\Cms\PPOB\PPOBBrand\Concerns\ValidatesManualFields;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePPOBBrandRequest extends FormRequest
{
    use ValidatesManualFields;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'p_p_o_b_category_id' => 'required|exists:p_p_o_b_categories,id',
            'name' => 'required|string|max:255',
            'provider' => 'required|in:digiflazz,lapakgaming,gift,manual_topup',
            'description' => 'nullable|string|max:1000',
            'featured' => 'required|boolean',
            'order' => 'required|integer|min:1',
            'settings' => 'nullable|array',
            'settings.type' => 'required_with:settings|string|in:id,id+server,manual',
            'settings.label_id' => 'required_unless:settings.type,manual|string|max:255',
            'settings.label_server' => 'required_if:settings.type,id+server|string|max:255',
            'settings.servers' => 'nullable|array',
            ...$this->manualFieldRules(),
            'image' => 'nullable|image|max:5120', // max 5MB
            'banner' => 'nullable|image|max:5120', // max 5MB
            'default_product_image' => 'nullable|image|max:2048', // max 2MB
            'status' => 'required|boolean',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateManualFields($validator));
    }
}
