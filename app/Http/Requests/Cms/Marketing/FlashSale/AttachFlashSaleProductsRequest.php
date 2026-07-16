<?php

namespace App\Http\Requests\Cms\Marketing\FlashSale;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttachFlashSaleProductsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:p_p_o_b_products,id',
            'pricing_type' => 'required|string|in:percent,manual',
            'discount_percent' => 'required_if:pricing_type,percent|numeric|between:0,100',
            'original_price' => 'nullable|numeric|min:1',
            'flash_price' => 'required_if:pricing_type,manual',
            'flash_stock' => 'required|integer|min:1',
        ];
    }
}
