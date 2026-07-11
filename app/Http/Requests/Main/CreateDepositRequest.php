<?php

namespace App\Http\Requests\Main;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateDepositRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $min = (int) (getSetting('deposit_min_amount') ?: 10000);
        $max = (int) (getSetting('deposit_max_amount') ?: 10000000);

        return [
            'amount' => "required|integer|min:{$min}|max:{$max}",
            'channel' => 'required|string|max:255',
        ];
    }
}
