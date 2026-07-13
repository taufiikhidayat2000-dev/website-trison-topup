<?php

namespace App\Actions\Api\V1\Callback;

use App\Actions\FlashSale\SettleFlashSaleStockAction;
use App\Enums\PaymentStatusEnum;
use App\Mail\PaymentFailed;
use App\Mail\PaymentSuccess;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Services\LapakGamingService;
use App\Services\LinkQuService;
use App\Services\VodaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Triyatna\Digiflazz\Digiflazz;

class HandleLinkQuCallbackAction
{
    public function __construct(
        public readonly LinkQuService $linkQuService,
        public readonly VodaService $vodaService,
        public readonly SettleFlashSaleStockAction $settleFlashSaleStockAction,
    ) {}

    public function handle(array $payload)
    {
        Log::info('LinkQu Callback Received', [
            'request' => $payload,
        ]);

        // VA & Retail callbacks carry a `va_number`, QRIS/Ewallet/Credit Card ones don't.
        $isValid = isset($payload['va_number'])
            ? $this->linkQuService->validateAccountCallbackSignature($payload)
            : $this->linkQuService->validateGenericCallbackSignature($payload);

        if (! $isValid) {
            throw new \Exception('Invalid signature key', 403);
        }

        $payment = Payment::where('driver', 'linkqu')
            ->where('order_id', $payload['partner_reff'] ?? null)
            ->first();

        if (! $payment) {
            throw new \Exception('Transaction not found', 404);
        }

        if ($payment->paid_at) {
            // Already processed (LinkQu sends both a "pay" and "settle" callback).
            return $payment;
        }

        $this->handlePaymentStatus($payload['status'] ?? null, $payment);

        return $payment;
    }

    /**
     * Public so ReconcileLinkQuPayments can reuse the same capture/deny logic
     * when settling a payment found via status polling instead of a webhook.
     */
    public function handlePaymentStatus(?string $status, Payment $payment)
    {
        switch ($status) {
            case 'SUCCESS':
                $payment->update([
                    'paid_at' => now(),
                ]);
                $this->capture($payment);
                break;
            case 'FAILED':
                $payment->update([
                    'expired_at' => now(),
                ]);
                $this->deny($payment);
                break;
                // PENDING: nothing to do yet, wait for the next callback.
        }
    }

    protected function capture(Payment $payment)
    {
        if ($payment->payable_type === Order::class) {
            $this->handleAfterOrderPaid($payment->payable);
        }
    }

    protected function handleAfterOrderPaid(Order $order)
    {
        $order->update([
            'payment_status' => PaymentStatusEnum::SETTLEMENT,
        ]);

        $this->settleFlashSaleStockAction->handle($order);

        $this->sendOrderNotification($order, true);
    }

    protected function deny(Payment $payment)
    {
        if ($payment->payable_type === Order::class) {
            $order = $payment->payable;
            $order->update([
                'payment_status' => PaymentStatusEnum::DENY,
            ]);

            $this->sendOrderNotification($order, false);
        }
    }

    protected function sendOrderNotification(Order $order, bool $isSuccess)
    {
        if ($isSuccess) {
            $accountId = $order->submited['account_id'] ?? '';
            $serverId = $order->submited['server_id'] ?? '';
            $customer = $accountId.$serverId;

            // If the provider is Digiflazz, create transaction to Digiflazz
            if ($order->brand->provider === 'digiflazz') {
                Digiflazz::createPrepaidTransaction(
                    productCode: $order->product->sku,
                    customerNo: $customer,
                    refId: $order->reference,
                );
            } elseif ($order->brand->provider === 'lapakgaming') {
                app(LapakGamingService::class)->createOrder(
                    productCode: $order->product->sku,
                    userId: $accountId,
                    serverId: $serverId ?: null,
                    partnerReferenceId: $order->reference,
                );
            }

            $message = getSetting('template_payment_confirmation');
            $message = str_replace('{customer_name}', $order->name, $message);
            $message = str_replace('{order_id}', $order->reference, $message);
            $message = str_replace('{app_name}', config('app.name'), $message);
            $message = str_replace('{link}', route('transaction.show', [
                'order' => $order,
            ]), $message);
            $message = str_replace('{cs_link}', getSetting('cs'), $message);
        } else {
            $message = getSetting('template_payment_rejected');
            $message = str_replace('{customer_name}', $order->name, $message);
            $message = str_replace('{order_id}', $order->reference, $message);
            $message = str_replace('{app_name}', config('app.name'), $message);
            $message = str_replace('{link}', route('transaction.show', [
                'order' => $order,
            ]), $message);
            $message = str_replace('{cs_link}', getSetting('cs'), $message);
        }

        Mail::to($order->email)->send(
            $isSuccess ? new PaymentSuccess($order) : new PaymentFailed($order)
        );

        $order->notifications()->create([
            'provider' => 'email',
            'title' => 'Payment '.($isSuccess ? 'Confirmed' : 'Rejected'),
            'content' => $message,
            'error' => false,
        ]);
    }
}
