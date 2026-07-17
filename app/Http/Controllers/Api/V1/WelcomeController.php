<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class WelcomeController extends Controller
{
    /**
     * Endpoint sederhana penanda API v1 aktif.
     *
     * Sebelumnya berupa Closure di routes/api/v1.php. Dipindah ke
     * controller agar route dapat di-cache tanpa error "Uses Closure".
     * Respons identik dengan sebelumnya.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'V1 API',
        ]);
    }
}
