<?php

use App\Http\Controllers\Cms\Deposit\DepositController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'deposits',
    'as' => 'deposits.',
], function () {
    Route::get('/', [DepositController::class, 'index'])->name('index');
});
