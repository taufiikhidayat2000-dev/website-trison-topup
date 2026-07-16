<?php

namespace App\Http\Requests\Cms\Marketing\FlashSale;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFlashSaleProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pricing_type' => 'required|string|in:percent,manual',
            'discount_percent' => 'required_if:pricing_type,percent|numeric|between:0,100',
            'original_price' => 'nullable|numeric|min:1',
            'flash_price' => 'required_if:pricing_type,manual',
            'flash_stock' => 'required|integer|min:1',
            'status' => 'required|string|in:active,sold_out,inactive',
        ];
    }
}
