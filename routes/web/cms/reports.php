<?php

use App\Http\Controllers\Cms\Reports\SalesReportController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'reports',
    'as' => 'reports.',
    'middleware' => 'role:superadmin',
], function () {
    Route::get('sales', [SalesReportController::class, 'index'])->name('sales.index');
    Route::get('sales/export', [SalesReportController::class, 'export'])->name('sales.export');
});
