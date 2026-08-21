<?php

use App\Http\Controllers\Api\V1\Callback\DigiflazzController;
use App\Http\Controllers\Api\V1\Callback\LapakGamingController;
use App\Http\Controllers\Api\V1\Callback\LinkQuController;
use App\Http\Controllers\Api\V1\Callback\MidtransController;
use Illuminate\Support\Facades\Route;

// Callback URL for Digiflazz
Route::post('/digiflazz/callback', [DigiflazzController::class, 'callback'])->name('digiflazz.callback');

// Callback URL for LinkQu
Route::post('/linkqu/callback', [LinkQuController::class, 'callback'])->name('linkqu.callback');

// Callback URL for Midtrans
Route::post('/midtrans/callback', [MidtransController::class, 'callback'])->name('midtrans.callback');

// Callback URL for LapakGaming (order status updates)
Route::post('/lapakgaming/callback', [LapakGamingController::class, 'callback'])->name('lapakgaming.callback');

// Product Update Callback URL for LapakGaming (price/stock changes)
Route::post('/lapakgaming/product-callback', [LapakGamingController::class, 'productCallback'])->name('lapakgaming.product-callback');
