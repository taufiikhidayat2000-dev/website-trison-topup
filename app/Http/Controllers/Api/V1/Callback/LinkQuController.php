<?php

namespace App\Http\Controllers\Api\V1\Callback;

use App\Actions\Api\V1\Callback\HandleLinkQuCallbackAction;
use App\Actions\Api\V1\Callback\HandleLinkQuDepositCallbackAction;
use App\Http\Controllers\Controller;
use App\Models\Wallet\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LinkQuController extends Controller
{
    /**
     * A single LinkQu callback endpoint serves both order payments and
     * wallet deposits. Dispatch on whichever record the partner_reff
     * actually belongs to, without touching HandleLinkQuCallbackAction's
     * own logic at all.
     */
    public function callback(Request $request, HandleLinkQuCallbackAction $orderAction, HandleLinkQuDepositCallbackAction $depositAction)
    {
        try {
            $partnerReff = $request->input('partner_reff');
            $isDeposit = $partnerReff && Deposit::where('reference', $partnerReff)->exists();

            $isDeposit
                ? $depositAction->handle($request->all())
                : $orderAction->handle($request->all());
        } catch (\Exception $e) {
            Log::error('LinkQu Callback Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['response' => 'FAILED'], 400);
        }

        // LinkQu expects this exact acknowledgement shape.
        return response()->json(['response' => 'OK']);
    }
}
