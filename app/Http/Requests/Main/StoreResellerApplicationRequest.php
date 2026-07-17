<?php

namespace App\Http\Requests\Main;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreResellerApplicationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => 'required|string|max:255',
            'note' => 'nullable|string|max:1000',
        ];
    }

    /**
     * A user can only have one pending application at a time, and can't
     * apply again once already a reseller - both checked here (not just in
     * the UI) so the rule holds even if the request is replayed/forged.
     */
    protected function passedValidation(): void
    {
        if ($this->user()->hasRole('reseller')) {
            throw ValidationException::withMessages([
                'business_name' => 'Anda sudah menjadi reseller.',
            ]);
        }

        if ($this->user()->resellerApplications()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'business_name' => 'Anda masih memiliki pengajuan yang sedang diproses.',
            ]);
        }
    }
}
