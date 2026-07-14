<?php

use App\Http\Controllers\Main\AfterLoginController;
use App\Http\Controllers\Main\BrandController;
use App\Http\Controllers\Main\CheckGameAccountController;
use App\Http\Controllers\Main\ContentController;
use App\Http\Controllers\Main\CreateDepositController;
use App\Http\Controllers\Main\HomeController;
use App\Http\Controllers\Main\PasswordController;
use App\Http\Controllers\Main\ProfileController;
use App\Http\Controllers\Main\ReviewController;
use App\Http\Controllers\Main\SearchController;
use App\Http\Controllers\Main\ShowDepositController;
use App\Http\Controllers\Main\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/privacy-policy', [ContentController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms', [ContentController::class, 'terms'])->name('terms');
Route::get('/brand/{brand}', [BrandController::class, 'show'])->name('product.show');

Route::get('/search/products', [SearchController::class, 'products'])
    ->name('search.products')
    ->middleware('throttle:30,1');

Route::post('/checkout', [TransactionController::class, 'store'])->name('checkout.store');
Route::get('/transaction/{order}', [TransactionController::class, 'show'])->name('transaction.show');
Route::get('/transaction', [TransactionController::class, 'check'])->name('transaction.check');
Route::put('/transaction/{order}', [TransactionController::class, 'update'])->name('transaction.update');
Route::post('/check-game-account', [CheckGameAccountController::class, 'check'])->name('check-game-account');
Route::post('/check-voucher', [TransactionController::class, 'checkVoucher'])->name('check-voucher');
Route::post('/transaction/{order}/review', [ReviewController::class, 'store'])->name('transaction.review.store');

Route::get('/profile', [ProfileController::class, 'index'])->name('main.profile.index')->middleware('auth');
Route::patch('/profile', [ProfileController::class, 'update'])->name('main.profile.update')->middleware('auth');
Route::patch('/password', [PasswordController::class, 'update'])->name('main.password.update')->middleware('auth');

Route::post('/deposits', CreateDepositController::class)->name('wallet.deposits.store')->middleware('auth');
Route::get('/deposits/{deposit}', ShowDepositController::class)->name('wallet.deposits.show')->middleware('auth');

// After login
Route::get('/after-login', AfterLoginController::class)->name('after-login')->middleware('auth');
