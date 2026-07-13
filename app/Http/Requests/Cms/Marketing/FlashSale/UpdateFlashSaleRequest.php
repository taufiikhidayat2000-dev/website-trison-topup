<?php

namespace App\Http\Requests\Cms\Marketing\FlashSale;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFlashSaleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'icon_type' => 'required|string|in:emoji,image',
            'icon_emoji' => 'nullable|string|max:8',
            'icon_image' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:4096',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'countdown_style' => 'required|string|in:detailed,compact',
            'auto_start' => 'boolean',
            'auto_end' => 'boolean',
            'after_end_action' => 'required|string|in:revert_price,hide,sold_out,keep_showing',
            'status' => 'required|string|in:draft,scheduled,active,ended,disabled',
        ];
    }
}
