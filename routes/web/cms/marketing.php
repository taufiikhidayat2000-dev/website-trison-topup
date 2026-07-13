<?php

use App\Http\Controllers\Cms\Marketing\FlashSaleController;
use App\Http\Controllers\Cms\Marketing\FlashSaleProductController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'marketing',
    'as' => 'marketing.',
], function () {
    Route::resource('flash-sales', FlashSaleController::class);

    Route::group([
        'prefix' => 'flash-sales/{flashSale}/products',
        'as' => 'flash-sales.products.',
    ], function () {
        Route::get('search', [FlashSaleProductController::class, 'search'])->name('search');
        Route::post('/', [FlashSaleProductController::class, 'store'])->name('store');
        Route::patch('{flashSaleProduct}', [FlashSaleProductController::class, 'update'])->name('update');
        Route::delete('{flashSaleProduct}', [FlashSaleProductController::class, 'destroy'])->name('destroy');
    });
});
