<?php

use App\Http\Requests\Cms\PPOB\PPOBBrand\StorePPOBBrandRequest;
use App\Models\PPOB\PPOBCategory;
use Illuminate\Support\Facades\Validator;

function ppobBrandBasePayload(array $settings): array
{
    $category = PPOBCategory::create(['name' => 'Validation Test Category', 'status' => true]);

    return [
        'p_p_o_b_category_id' => $category->id,
        'name' => 'Validation Test Brand',
        'provider' => 'gift',
        'featured' => false,
        'order' => 1,
        'status' => true,
        'settings' => $settings,
    ];
}

function validatePPOBBrandPayload(array $data): Illuminate\Validation\Validator
{
    $request = new StorePPOBBrandRequest;
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    return $validator;
}

test('manual checkout type rejects a dropdown field with no options', function () {
    $validator = validatePPOBBrandPayload(ppobBrandBasePayload([
        'type' => 'manual',
        'manual_fields' => [
            ['key' => 'login_via', 'label' => 'Login Via', 'type' => 'select', 'options' => [], 'required' => true],
        ],
    ]));

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('settings.manual_fields.0.options'))->toBeTrue();
});

test('manual checkout type rejects duplicate field keys', function () {
    $validator = validatePPOBBrandPayload(ppobBrandBasePayload([
        'type' => 'manual',
        'manual_fields' => [
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ['key' => 'email', 'label' => 'Email Again', 'type' => 'text', 'required' => true],
        ],
    ]));

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('settings.manual_fields'))->toBeTrue();
});

test('manual checkout type rejects a field key that collides with a reserved submited key', function () {
    $validator = validatePPOBBrandPayload(ppobBrandBasePayload([
        'type' => 'manual',
        'manual_fields' => [
            ['key' => 'done', 'label' => 'Done', 'type' => 'text', 'required' => true],
        ],
    ]));

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('settings.manual_fields.0.key'))->toBeTrue();
});

test('manual checkout type passes with valid, non-colliding field definitions', function () {
    $validator = validatePPOBBrandPayload(ppobBrandBasePayload([
        'type' => 'manual',
        'manual_fields' => [
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ['key' => 'login_via', 'label' => 'Login Via', 'type' => 'select', 'options' => ['Google', 'Facebook'], 'required' => true],
        ],
    ]));

    expect($validator->fails())->toBeFalse();
});

test('legacy id checkout type still validates without manual_fields', function () {
    $validator = validatePPOBBrandPayload(ppobBrandBasePayload([
        'type' => 'id',
        'label_id' => 'ID',
    ]));

    expect($validator->fails())->toBeFalse();
});
