<?php

namespace App\Http\Requests\Cms\Member;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdjustBalanceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:credit,debit',
            'amount' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
        ];
    }
}
