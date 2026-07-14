<?php

namespace App\Http\Requests\Cms\PPOB\PPOBBrand\Concerns;

use Illuminate\Validation\Validator;

/**
 * Shared "manual" checkout-type field validation for Store/UpdatePPOBBrandRequest.
 */
trait ValidatesManualFields
{
    /**
     * Keys already used elsewhere inside `orders.submited` (id/id+server
     * checkout data, and the gift/manual_topup fulfillment tracking fields
     * appended by StoreTransactionAction and the gift/manual-topup media
     * collections). A manual field can't reuse one of these keys or its
     * submitted value would be silently overwritten/clobbered later.
     */
    private const RESERVED_MANUAL_FIELD_KEYS = [
        'account_id',
        'server_id',
        'gift_send',
        'dispute',
        'done',
        'admin_account_ign',
        'admin_add_friend',
        'admin_add_friend_timestamp',
        'user_confirm_friend',
        'user_confirm_friend_timestamp',
        'admin_add_friend_proof',
        'user_confirm_friend_proof',
        'gift_send_proof',
    ];

    /**
     * Validation rules for `settings.manual_fields`, shared by Store/UpdatePPOBBrandRequest.
     */
    protected function manualFieldRules(): array
    {
        return [
            'settings.manual_fields' => 'required_if:settings.type,manual|array',
            'settings.manual_fields.*.key' => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'settings.manual_fields.*.label' => 'required|string|max:255',
            'settings.manual_fields.*.type' => 'required|string|in:text,email,password,select',
            'settings.manual_fields.*.options' => 'nullable|array',
            'settings.manual_fields.*.options.*' => 'nullable|string|max:255',
            'settings.manual_fields.*.required' => 'nullable|boolean',
        ];
    }

    /**
     * Cross-field checks that plain rule strings can't express: unique keys,
     * dropdown fields must have options, and no key may collide with a
     * reserved `orders.submited` key.
     */
    protected function validateManualFields(Validator $validator): void
    {
        $data = $validator->getData();

        if (($data['settings']['type'] ?? null) !== 'manual') {
            return;
        }

        $fields = $data['settings']['manual_fields'] ?? [];
        $keys = array_filter(array_column($fields, 'key'));

        if (count($keys) !== count(array_unique($keys))) {
            $validator->errors()->add('settings.manual_fields', 'Field key harus unik satu sama lain.');
        }

        foreach ($fields as $index => $field) {
            if (($field['type'] ?? null) === 'select' && empty(array_filter($field['options'] ?? []))) {
                $validator->errors()->add(
                    "settings.manual_fields.{$index}.options",
                    'Dropdown field harus memiliki minimal satu opsi.'
                );
            }

            if (in_array($field['key'] ?? null, self::RESERVED_MANUAL_FIELD_KEYS, true)) {
                $validator->errors()->add(
                    "settings.manual_fields.{$index}.key",
                    "Key \"{$field['key']}\" adalah key sistem yang sudah dipakai dan tidak boleh digunakan untuk field manual."
                );
            }
        }
    }
}
