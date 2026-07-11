<?php

use App\Http\Controllers\Cms\Web\FaqController;
use App\Http\Controllers\Cms\Web\ReviewController;
use App\Http\Controllers\Cms\Web\SliderController;
use App\Http\Controllers\Cms\Web\VoucherController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'web',
    'as' => 'web.',
], function () {
    Route::resources([
        'sliders' => SliderController::class,
        'faqs' => FaqController::class,
        'vouchers' => VoucherController::class,
    ]);

    Route::group([
        'prefix' => 'reviews',
        'as' => 'reviews.',
    ], function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('/export', [ReviewController::class, 'export'])->name('export');
        Route::get('/{review}', [ReviewController::class, 'show'])->name('show');
        Route::patch('/{review}/status', [ReviewController::class, 'updateStatus'])->name('update-status');
        Route::post('/{review}/reply', [ReviewController::class, 'reply'])->name('reply');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
    });
});
