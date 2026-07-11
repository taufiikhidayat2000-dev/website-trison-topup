<?php

namespace App\Actions\Main;

use App\Enums\DigiflazzStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Mail\PaymentFailed;
use App\Mail\PaymentSuccess;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\LapakGamingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Triyatna\Digiflazz\Digiflazz;

/**
 * Handles checkout when the customer pays with their wallet balance: debits
 * the balance atomically, marks the order paid, and dispatches fulfillment
 * immediately (mirroring what the LinkQu success callback does for gateway
 * payments). Never touches LinkQu/Midtrans — balance payments never go
 * through a gateway at all.
 */
class ProcessBalancePaymentAction
{
    public function __construct(
        public readonly BalanceService $balanceService,
    ) {}

    public function handle(Order $order, User $user): Payment
    {
        return DB::transaction(function () use ($order, $user) {
            if (! $user->is_active) {
                throw new \Exception('Akun Anda tidak aktif.');
            }

            $this->balanceService->debit(
                user: $user,
                amount: $order->total_amount,
                description: "Pembayaran order {$order->reference}",
                reference: $order,
                performedBy: $user,
            );

            $payment = Payment::create([
                'driver' => 'balance',
                'payable_type' => Order::class,
                'payable_id' => $order->id,
                'order_id' => uniqid().time(),
                'transaction_id' => null,
                'payment_type' => 'balance',
                'account_number' => 'BALANCE',
                'channel' => 'balance',
                'expired_at' => null,
                'paid_at' => now(),
                'amount' => $order->total_amount,
            ]);

            $order->update([
                'payment_status' => PaymentStatusEnum::SETTLEMENT,
            ]);

            $this->dispatchFulfillment($order, $user);

            return $payment;
        });
    }

    /**
     * Same fulfillment dispatch as HandleLinkQuCallbackAction::sendOrderNotification(),
     * duplicated (not shared) so the existing LinkQu callback flow is never touched.
     * Refunds automatically if the supplier reports an immediate/permanent failure.
     */
    protected function dispatchFulfillment(Order $order, User $user): void
    {
        try {
            $accountId = $order->submited['account_id'] ?? '';
            $serverId = $order->submited['server_id'] ?? '';
            $customer = $accountId.$serverId;

            if ($order->brand->provider === 'digiflazz') {
                $response = Digiflazz::createPrepaidTransaction(
                    productCode: $order->product->sku,
                    customerNo: $customer,
                    refId: $order->reference,
                );

                if ($response->isFailed()) {
                    throw new \Exception('Digiflazz: '.$response->get('message', 'Transaksi gagal'));
                }
            } elseif ($order->brand->provider === 'lapakgaming') {
                $result = app(LapakGamingService::class)->createOrder(
                    productCode: $order->product->sku,
                    userId: $accountId,
                    serverId: $serverId ?: null,
                    partnerReferenceId: $order->reference,
                );

                if (! $result['successful']) {
                    throw new \Exception('LapakGaming: '.$result['message']);
                }
            }

            $this->sendOrderNotification($order, true);
        } catch (\Exception $e) {
            Log::error('Balance payment fulfillment failed, refunding', [
                'order' => $order->reference,
                'error' => $e->getMessage(),
            ]);

            $order->update(['topup_status' => DigiflazzStatusEnum::FAILED]);

            $this->balanceService->credit(
                user: $user,
                amount: $order->total_amount,
                description: "Refund order {$order->reference} - fulfillment gagal",
                reference: $order,
            );

            $this->sendOrderNotification($order, false);
        }
    }

    protected function sendOrderNotification(Order $order, bool $isSuccess): void
    {
        $message = getSetting($isSuccess ? 'template_payment_confirmation' : 'template_payment_rejected');
        $message = str_replace('{customer_name}', $order->name, $message);
        $message = str_replace('{order_id}', $order->reference, $message);
        $message = str_replace('{app_name}', config('app.name'), $message);
        $message = str_replace('{link}', route('transaction.show', [
            'order' => $order,
        ]), $message);
        $message = str_replace('{cs_link}', getSetting('cs'), $message);

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
