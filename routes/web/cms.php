<?php

use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\HomeRedirectController;
use Illuminate\Support\Facades\Route;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;

Route::group([
    'prefix' => 'cms',
    'as' => 'cms.',
    'middleware' => ['auth', 'verified'],
], function () {
    // Auto redirect to dashboard
    Route::get('/', HomeRedirectController::class)->name('home');

    // Dashboard Route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Web Routes
    require 'cms/web.php';

    // Marketing Routes
    require 'cms/marketing.php';

    // PPOB Routes
    require 'cms/ppob.php';

    // Order Routes
    require 'cms/order.php';

    // Setting Routes
    require 'cms/setting.php';

    // Reports Routes
    require 'cms/reports.php';

    // Member Routes
    require 'cms/member.php';

    // Reseller Routes
    require 'cms/reseller.php';

    // Deposit Routes
    require 'cms/deposit.php';

    // Management Routes
    require 'cms/management.php';

    // Logs
    Route::get('logs', [LogViewerController::class, 'index'])->name('logs')->middleware('auth', 'role:superadmin');
});
