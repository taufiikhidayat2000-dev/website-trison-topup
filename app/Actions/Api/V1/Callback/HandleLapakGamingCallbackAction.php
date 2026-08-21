<?php

namespace App\Actions\Api\V1\Callback;

use App\Enums\DigiflazzStatusEnum;
use App\Mail\TopupSuccess;
use App\Models\Order\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HandleLapakGamingCallbackAction
{
    /**
     * @param  array  $payload  Decoded "Order Callback" body, see
     *                          https://documenter.getpostman.com/view/5668891/2s9XxvTujb
     *                          { code, data: { status, tid, reference_id, transactions: [...] } }
     */
    public function handle(array $payload): void
    {
        Log::info('LapakGaming Callback Received', ['payload' => $payload]);

        $data = $payload['data'] ?? [];
        $referenceId = $data['reference_id'] ?? null;

        if (! $referenceId) {
            throw new \Exception('Missing reference_id in LapakGaming callback');
        }

        // reference_id is the partner_reference_id we sent when creating the
        // order, which ProcessBalancePaymentAction/HandleLinkQuCallbackAction/
        // HandleMidtransCallbackAction all set to $order->reference.
        $order = Order::where('reference', $referenceId)->firstOrFail();

        $status = $data['status'] ?? null;
        $voucherCode = collect($data['transactions'] ?? [])
            ->pluck('voucher_code')
            ->filter()
            ->first();

        if ($status === 'SUCCESS') {
            $order->update([
                'sn' => $voucherCode,
                'topup_status' => DigiflazzStatusEnum::SUCCESS,
            ]);

            $message = getSetting('template_order_completed');
            $message = str_replace('{customer_name}', $order->name, $message);
            $message = str_replace('{order_id}', $order->reference, $message);
            $message = str_replace('{app_name}', config('app.name'), $message);
            $message = str_replace('{link}', route('transaction.show', [
                'order' => $order,
            ]), $message);
            $message = str_replace('{cs_link}', getSetting('cs'), $message);

            Mail::to($order->email)->send(new TopupSuccess($order));

            $order->notifications()->create([
                'provider' => 'email',
                'title' => 'Order Completed',
                'content' => $message,
                'error' => false,
            ]);
        } elseif ($status === 'REFUNDED') {
            $order->update(['topup_status' => DigiflazzStatusEnum::FAILED]);
        } else {
            $order->update(['topup_status' => DigiflazzStatusEnum::ON_PROGRESS]);
        }
    }
}
