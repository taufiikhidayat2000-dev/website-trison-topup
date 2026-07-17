<?php

namespace App\Http\Controllers\Main;

use App\Actions\Main\SubmitResellerApplicationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Main\StoreResellerApplicationRequest;
use Illuminate\Http\Request;

class ResellerController extends Controller
{
    /**
     * Display the reseller application/status page.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return inertia('main/Reseller', [
            'isReseller' => $user->hasRole('reseller'),
            'latestApplication' => $user->resellerApplications()->latest()->first(),
            'resellerDiscountPercent' => (float) (getSetting('reseller_discount_percent') ?? 2),
        ]);
    }

    /**
     * Submit a new reseller application.
     */
    public function store(StoreResellerApplicationRequest $request, SubmitResellerApplicationAction $action)
    {
        $action->handle($request->user(), $request->validated());

        return back()->with('success', 'Pengajuan reseller berhasil dikirim. Silakan tunggu konfirmasi admin.');
    }
}
