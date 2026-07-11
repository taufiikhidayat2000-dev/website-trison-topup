<?php

use App\Http\Controllers\Api\V1\Callback\DigiflazzController;
use App\Http\Controllers\Api\V1\Callback\LinkQuController;
use App\Http\Controllers\Api\V1\Callback\MidtransController;
use Illuminate\Support\Facades\Route;

// Callback URL for Digiflazz
Route::post('/digiflazz/callback', [DigiflazzController::class, 'callback'])->name('digiflazz.callback');

// Callback URL for LinkQu
Route::post('/linkqu/callback', [LinkQuController::class, 'callback'])->name('linkqu.callback');

// Callback URL for Midtrans
Route::post('/midtrans/callback', [MidtransController::class, 'callback'])->name('midtrans.callback');
