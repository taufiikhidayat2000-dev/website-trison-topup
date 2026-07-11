<?php

namespace App\Http\Controllers\Api\V1\Callback;

use App\Actions\Api\V1\Callback\HandleMidtransCallbackAction;
use App\Actions\Api\V1\Callback\HandleMidtransDepositCallbackAction;
use App\Http\Controllers\Controller;
use App\Models\Wallet\Deposit;
use App\Traits\WithReturnResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransController extends Controller
{
    use WithReturnResponse;

    /**
     * A single Midtrans callback endpoint serves both order payments and
     * wallet deposits. Dispatch on whichever record the order_id actually
     * belongs to, without touching HandleMidtransCallbackAction's own logic
     * at all.
     */
    public function callback(Request $request, HandleMidtransCallbackAction $orderAction, HandleMidtransDepositCallbackAction $depositAction)
    {
        try {
            $reference = explode('-', $request->input('order_id', ''))[0];
            $isDeposit = $reference && Deposit::where('reference', $reference)->exists();

            $isDeposit
                ? $depositAction->handle($request->all())
                : $orderAction->handle($request->all());
        } catch (\Exception $e) {
            Log::error('Midtrans Callback Error', [
                'error' => $e->getMessage(),
            ]);

            return $this->responseWithError($e->getMessage(), 400);
        }

        return $this->responseWithSuccess('Callback received');
    }
}
