<?php

use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;

function createSearchableBrand(string $name): PPOBBrand
{
    $category = PPOBCategory::create(['name' => $name.' Category', 'status' => true]);

    return PPOBBrand::create([
        'p_p_o_b_category_id' => $category->id,
        'name' => $name,
        'provider' => 'manual_topup',
        'status' => true,
    ]);
}

test('search returns matching active brands', function () {
    createSearchableBrand('Mobile Legends');
    createSearchableBrand('Free Fire');

    $response = $this->getJson('/search/products?q=mobile');

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['name' => 'Mobile Legends']);
});

test('search returns nothing for an unmatched term', function () {
    createSearchableBrand('Mobile Legends');

    $response = $this->getJson('/search/products?q=zzznotfound');

    $response->assertOk();
    $response->assertJsonCount(0);
});

test('search ignores inactive brands', function () {
    $brand = createSearchableBrand('Inactive Game');
    $brand->update(['status' => false]);

    $response = $this->getJson('/search/products?q=inactive');

    $response->assertOk();
    $response->assertJsonCount(0);
});

test('search treats underscore as a literal character, not a SQL wildcard', function () {
    createSearchableBrand('Free Fire');

    $response = $this->getJson('/search/products?q='.urlencode('_'));

    $response->assertOk();
    $response->assertJsonCount(0);
});

test('search requires a query and rejects overly long input', function () {
    $this->getJson('/search/products')->assertStatus(422);
    $this->getJson('/search/products?q='.str_repeat('a', 101))->assertStatus(422);
});
