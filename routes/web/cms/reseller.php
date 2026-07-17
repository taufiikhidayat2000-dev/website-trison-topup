<?php

use App\Http\Controllers\Cms\Reseller\ApproveResellerApplicationController;
use App\Http\Controllers\Cms\Reseller\RejectResellerApplicationController;
use App\Http\Controllers\Cms\Reseller\ResellerApplicationController;
use App\Http\Controllers\Cms\Reseller\ResellerController;
use App\Http\Controllers\Cms\Reseller\RevokeResellerController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'reseller',
    'as' => 'reseller.',
], function () {
    Route::get('/', [ResellerController::class, 'index'])->name('index');
    Route::patch('/{reseller}/revoke', RevokeResellerController::class)->name('revoke');

    Route::group([
        'prefix' => 'applications',
        'as' => 'applications.',
    ], function () {
        Route::get('/', [ResellerApplicationController::class, 'index'])->name('index');
        Route::patch('/{application}/approve', ApproveResellerApplicationController::class)->name('approve');
        Route::patch('/{application}/reject', RejectResellerApplicationController::class)->name('reject');
    });
});
