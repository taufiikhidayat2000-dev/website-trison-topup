<?php

use App\Http\Controllers\Cms\PPOB\ImportDigiflazzController;
use App\Http\Controllers\Cms\PPOB\ImportLapakGamingController;
use App\Http\Controllers\Cms\PPOB\PPOBBrandController;
use App\Http\Controllers\Cms\PPOB\PPOBCategoryController;
use App\Http\Controllers\Cms\PPOB\PPOBProductCategoryController;
use App\Http\Controllers\Cms\PPOB\PPOBProductController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'ppob',
    'as' => 'ppob.',
], function () {
    // Registered before the `products` resource so `{product}` doesn't swallow these.
    Route::get('products/import/template', [PPOBProductController::class, 'importTemplate'])->name('products.import.template');
    Route::get('products/import', [PPOBProductController::class, 'importForm'])->name('products.import.form');
    Route::post('products/import', [PPOBProductController::class, 'import'])->name('products.import');

    Route::resources([
        'categories' => PPOBCategoryController::class,
        'brands' => PPOBBrandController::class,
        'products' => PPOBProductController::class,
    ]);
    Route::resource('product-categories', PPOBProductCategoryController::class)
        ->parameters(['product-categories' => 'productCategory']);

    // Additional Routes
    Route::post('brands/json-all', [PPOBBrandController::class, 'jsonAll'])->name('brands.json-all');
    Route::get('import-digiflazz', [ImportDigiflazzController::class, 'index'])->name('import-digiflazz.index');
    Route::post('import-digiflazz', [ImportDigiflazzController::class, 'sync'])->name('import-digiflazz.sync');
    Route::get('import-lapakgaming', [ImportLapakGamingController::class, 'index'])->name('import-lapakgaming.index');
    Route::post('import-lapakgaming', [ImportLapakGamingController::class, 'sync'])->name('import-lapakgaming.sync');
});
