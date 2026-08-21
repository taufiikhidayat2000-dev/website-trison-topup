<?php

namespace App\Http\Controllers\Api\V1\Callback;

use App\Actions\Api\V1\Callback\HandleLapakGamingCallbackAction;
use App\Actions\Api\V1\Callback\HandleLapakGamingProductCallbackAction;
use App\Http\Controllers\Controller;
use App\Traits\WithReturnResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LapakGamingController extends Controller
{
    use WithReturnResponse;

    /**
     * LapakGaming's docs only require HTTP 200 back (no signature to
     * verify, unlike Digiflazz's X-Hub-Signature) - they rely on the same
     * IP whitelist as the outbound API calls. A non-200 response gets
     * retried up to 3 times. Product callbacks are NOT retried on failure.
     */
    public function callback(Request $request, HandleLapakGamingCallbackAction $action)
    {
        try {
            $action->handle($request->all());
        } catch (\Exception $e) {
            Log::error('LapakGaming Callback Error', [
                'error' => $e->getMessage(),
            ]);

            return $this->responseWithError($e->getMessage(), 400);
        }

        return $this->responseWithSuccess('Callback received');
    }

    public function productCallback(Request $request, HandleLapakGamingProductCallbackAction $action)
    {
        try {
            $action->handle($request->all());
        } catch (\Exception $e) {
            Log::error('LapakGaming Product Callback Error', [
                'error' => $e->getMessage(),
            ]);

            return $this->responseWithError($e->getMessage(), 400);
        }

        return $this->responseWithSuccess('Callback received');
    }
}
