<?php

use App\Http\Controllers\Api\V1\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1', 'as' => 'api.v1.'], function () {
    // Welcome Route
    Route::get('/', WelcomeController::class)->name('welcome');

    // Callback Routes
    require 'v1/callback.php';
});
