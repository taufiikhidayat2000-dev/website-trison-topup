<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    /**
     * Tampilkan halaman pengaturan tampilan.
     *
     * Sebelumnya berupa Closure di routes/web/settings.php. Dipindah ke
     * controller agar route dapat di-cache (`php artisan route:cache`
     * / `php artisan optimize`) tanpa error "Uses Closure".
     * Perilaku identik dengan sebelumnya.
     */
    public function __invoke(): Response
    {
        return Inertia::render('settings/Appearance');
    }
}
