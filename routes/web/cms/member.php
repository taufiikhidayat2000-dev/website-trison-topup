<?php

use App\Http\Controllers\Cms\Member\AdjustMemberBalanceController;
use App\Http\Controllers\Cms\Member\MemberController;
use App\Http\Controllers\Cms\Member\ResetMemberPasswordController;
use App\Http\Controllers\Cms\Member\UpdateMemberStatusController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'members',
    'as' => 'members.',
], function () {
    Route::get('/', [MemberController::class, 'index'])->name('index');
    Route::get('/{member}', [MemberController::class, 'show'])->name('show');
    Route::post('/{member}/reset-password', ResetMemberPasswordController::class)->name('reset-password');
    Route::post('/{member}/balance', AdjustMemberBalanceController::class)->name('adjust-balance');
    Route::patch('/{member}/status', UpdateMemberStatusController::class)->name('update-status');
});
