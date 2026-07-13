<?php

namespace App\Actions\Main;

use App\Mail\OrderCreated;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Models\PPOB\PPOBProduct;
use App\Models\Voucher\Voucher;
use App\Models\Voucher\VoucherUse;
use App\Services\LinkQuService;
use App\Services\MidtransService;
use App\Services\VodaService;
use App\Traits\WithGenerateReference;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StoreTransactionAction
{
    use WithGenerateReference;

    public function __construct(
        public readonly LinkQuService $linkQuService,
        public readonly MidtransService $midtransService,
        public readonly VodaService $vodaService,
        public readonly ProcessBalancePaymentAction $processBalancePaymentAction,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(array $data): Order
    {
        $reference = $this->generateReference(
            model: new Order,
            prefix: 'TRX-'.now()->format('Ymd').'-',
        );

        // Get price from product
        $product = PPOBProduct::find($data['product_id']);

        $data['reference'] = $reference['code'];
        $data['ref_number'] = $reference['number'];
        $data['amount'] = (int) $product->sell_price;
        $data['user_id'] = auth()->id() ?? null;

        // Voucher Logic
        $voucher = null;
        $discountAmount = 0;
        if (isset($data['voucher_code']) && $data['voucher_code']) {
            $voucher = Voucher::where('code', $data['voucher_code'])->first();

            if ($voucher) {
                // Basic validation (should match checkVoucher logic)
                $now = now();
                $isValid = $voucher->status
                    && (! $voucher->start_date || $now->gte($voucher->start_date))
                    && (! $voucher->end_date || $now->lte($voucher->end_date))
                    && ($voucher->usage_limit === 0 || $voucher->used_count < $voucher->usage_limit)
                    && ($voucher->min_purchase_amount === 0 || $data['amount'] >= $voucher->min_purchase_amount);

                if ($isValid) {
                    if ($voucher->type === 'FIXED_AMOUNT') {
                        $discountAmount = $voucher->fixed_amount;
                    } elseif ($voucher->type === 'PERCENTAGE') {
                        $discountAmount = ($data['amount'] * $voucher->percentage) / 100;
                    }
                    $discountAmount = min($discountAmount, $data['amount']);
                }
            }
        }

        // Apply discount
        $data['discount_amount'] = $discountAmount;
        $amountAfterDiscount = $data['amount'] - $discountAmount;

        // Get fee if payment type is automatic
        if ($data['payment_type'] === 'automatic') {
            $fee = $data['payment_method'] === 'qris'
                ? $amountAfterDiscount * 0.007 // 0.7% fee for qris based on discounted amount
                : 4000; // Flat 4000 fee for bank transfer

            $data['fee'] = (int) round($fee);
            $data['total_amount'] = $amountAfterDiscount + $data['fee'];
        } else {
            $data['fee'] = 0;
            $data['total_amount'] = $amountAfterDiscount;
        }

        // Fail fast for balance payment before creating the order. This is
        // only a preliminary check for a clean error message - the
        // authoritative, race-safe check happens inside BalanceService::debit()
        // (via lockForUpdate) when the payment is actually processed below.
        if ($data['payment_type'] === 'balance') {
            $user = auth()->user();

            if (! $user) {
                throw new \Exception('Anda harus login untuk membayar menggunakan saldo.');
            }

            if ($user->balance < $data['total_amount']) {
                throw new \Exception('Saldo tidak mencukupi.');
            }
        }

        // If product provider is gift then add step to submited data
        if ($product->provider === 'gift') {
            $data['submited'] = [
                ...$data['submited'],
                'admin_account_ign' => '',
                'admin_add_friend' => false,
                'admin_add_friend_timestamp' => '',
                'user_confirm_friend' => false,
                'user_confirm_friend_timestamp' => '',
                'gift_send' => false,
                'dispute' => false,
                'done' => false,
            ];
        }

        // If product provider is manual_topup
        if ($product->provider === 'manual_topup') {
            $data['submited'] = [
                ...$data['submited'],
                'gift_send' => false,
                'dispute' => false,
                'done' => false,
            ];
        }

        $order = Order::create($data);

        // Record Voucher Usage
        if ($voucher && $discountAmount > 0) {
            VoucherUse::create([
                'voucher_id' => $voucher->id,
                'usable_type' => Order::class,
                'usable_id' => $order->id,
                'before_amount' => $data['amount'],
                'discount_amount' => $discountAmount,
                'after_amount' => $amountAfterDiscount,
            ]);

            $voucher->increment('used_count');
        }

        $isAutomatic = $data['payment_type'] === 'automatic';
        $isBalance = $data['payment_type'] === 'balance';

        if ($isBalance) {
            // Balance payments are debited, marked paid, and dispatched for
            // fulfillment synchronously here - they never touch LinkQu/Midtrans.
            $this->processBalancePaymentAction->handle($order, auth()->user());
        } else {
            // Create payment record. The order_id is always numeric-only so it stays
            // valid for either gateway, regardless of which one ends up being used.
            $orderId = $isAutomatic ? LinkQuService::generatePartnerReff() : uniqid().time();
            $payment = Payment::create([
                'driver' => $isAutomatic ? (getSetting('payment_gateway') ?: 'linkqu') : 'manual',
                'payable_type' => Order::class,
                'payable_id' => $order->id,
                'order_id' => $orderId,
                'transaction_id' => null,
                'payment_type' => $data['payment_method'] === 'qris' ? 'qris' : 'bank_transfer',
                // AUTO_GENERATED
                'account_number' => 'AUTO_GENERATED',
                'channel' => $data['payment_method'],
                'expired_at' => now()->addHours(24),
                'amount' => $order->total_amount,
            ]);

            // Create the gateway transaction if payment type is automatic, trying the
            // primary gateway first and automatically falling back to the other one.
            if ($isAutomatic) {
                $primaryGateway = getSetting('payment_gateway') ?: 'linkqu';
                $fallbackGateway = $primaryGateway === 'linkqu' ? 'midtrans' : 'linkqu';

                $gatewayResult = null;
                $usedGateway = null;
                $errors = [];

                foreach ([$primaryGateway, $fallbackGateway] as $gateway) {
                    try {
                        $gatewayResult = $this->createGatewayPayment($gateway, $payment, $order, $data['payment_method']);
                        $usedGateway = $gateway;
                        break;
                    } catch (\Exception $e) {
                        $errors[$gateway] = $e->getMessage();
                        Log::warning("Payment gateway [{$gateway}] failed, trying fallback", [
                            'order' => $order->reference,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Both gateways failed, nothing to fall back to anymore
                if (! $gatewayResult) {
                    throw new \Exception('Failed to create payment transaction: '.collect($errors)->map(fn ($message, $gateway) => "{$gateway}: {$message}")->implode(' | '));
                }

                // Update payment with the gateway that actually succeeded
                $payment->driver = $usedGateway;
                $payment->transaction_id = $gatewayResult['transaction_id'];
                $payment->account_number = $gatewayResult['account'];
                $payment->account_code = $gatewayResult['code'] ?? null;
                $payment->save();
            } else {
                // For manual payment, we can set the account number to the payment method for easier reconciliation
                $payment->channel = getSetting('manual_transfer_bank');
                $payment->account_number = getSetting('manual_transfer_account_number');
                $payment->account_code = getSetting('manual_transfer_account_name');
                $payment->save();
            }
        }

        // Send notification to user
        $message = getSetting('template_checkout');
        $message = str_replace('{app_name}', config('app.name'), $message);
        $message = str_replace('{order_id}', $order->reference, $message);
        $message = str_replace('{product}', $product->name, $message);
        $message = str_replace('{total}', numberToCurrency($order->total_amount), $message);
        $message = str_replace('{link}', route('transaction.show', [
            'order' => $order,
        ]), $message);
        $message = str_replace('{cs_link}', getSetting('cs'), $message);

        // // Send message via Voda
        // $isNotificationError = false;
        // try {
        //     $this->vodaService->sendMessage(
        //         phone: $order->phone,
        //         message: $message,
        //         linkPreview: true,
        //     );
        // } catch (\Exception $e) {
        //     Log::error('Failed to send Voda message: '.$e->getMessage());
        //     $isNotificationError = true;
        // }

        // // Create notification record
        // $order->notifications()->create([
        //     'provider' => 'voda',
        //     'title' => 'Order Created',
        //     'content' => $message,
        //     'error' => $isNotificationError,
        // ]);

        // Send message via email
        Mail::to($order->email)->send(
            new OrderCreated($order)
        );

        $order->notifications()->create([
            'provider' => 'email',
            'title' => 'Order Created',
            'content' => $message,
            'error' => false,
        ]);

        return $order;
    }

    /**
     * Create a payment transaction on the given gateway ('linkqu' or 'midtrans').
     *
     * @return array{successful: bool, transaction_id: ?string, account: ?string, code: ?string, message: string}
     *
     * @throws \Exception When the gateway rejects the request or the channel isn't supported.
     */
    protected function createGatewayPayment(string $gateway, Payment $payment, Order $order, string $paymentMethod): array
    {
        if ($gateway === 'midtrans') {
            $result = $paymentMethod === 'qris'
                ? $this->midtransService->createQris(
                    orderId: $payment->order_id,
                    amount: $payment->amount,
                )
                : $this->midtransService->createBankTransfer(
                    orderId: $payment->order_id,
                    bank: $paymentMethod,
                    amount: $payment->amount,
                );

            if (! $result['successful']) {
                throw new \Exception('Failed to create Midtrans transaction: '.$result['message']);
            }

            return $result;
        }

        // Default / fallback: LinkQu
        $customerId = (string) ($order->user_id ?? $payment->id);

        if ($paymentMethod === 'qris') {
            $result = $this->linkQuService->createQris(
                partnerReff: $payment->order_id,
                amount: $payment->amount,
                customerId: $customerId,
                customerName: $order->name,
                customerEmail: $order->email,
                customerPhone: $order->phone,
            );
        } elseif ($paymentMethod === 'ovo') {
            $result = $this->linkQuService->createOvoPush(
                partnerReff: $payment->order_id,
                amount: $payment->amount,
                customerId: $customerId,
                customerName: $order->name,
                customerEmail: $order->email,
                customerPhone: $order->phone,
            );
        } elseif ($paymentMethod === 'credit_card') {
            $result = $this->linkQuService->createCreditCard(
                partnerReff: $payment->order_id,
                amount: $payment->amount,
                customerId: $customerId,
                customerName: $order->name,
                customerEmail: $order->email,
                customerPhone: $order->phone,
            );
        } elseif (array_key_exists($paymentMethod, LinkQuService::EWALLETS)) {
            $result = $this->linkQuService->createEwallet(
                partnerReff: $payment->order_id,
                amount: $payment->amount,
                retailCode: LinkQuService::EWALLETS[$paymentMethod],
                customerId: $customerId,
                customerName: $order->name,
                customerEmail: $order->email,
                customerPhone: $order->phone,
            );
        } elseif (array_key_exists($paymentMethod, LinkQuService::RETAILS)) {
            $result = $this->linkQuService->createRetail(
                partnerReff: $payment->order_id,
                amount: $payment->amount,
                retailCode: LinkQuService::RETAILS[$paymentMethod],
                customerId: $customerId,
                customerName: $order->name,
                customerEmail: $order->email,
                customerPhone: $order->phone,
            );
        } else {
            $bankCode = LinkQuService::BANKS[$paymentMethod] ?? null;

            if (! $bankCode) {
                throw new \Exception('Invalid payment method. Valid methods are: '.implode(', ', [
                    ...array_keys(LinkQuService::BANKS),
                    ...array_keys(LinkQuService::EWALLETS),
                    ...array_keys(LinkQuService::RETAILS),
                    'qris', 'credit_card',
                ]));
            }

            $result = $this->linkQuService->createVirtualAccount(
                partnerReff: $payment->order_id,
                amount: $payment->amount,
                bankCode: $bankCode,
                customerId: $customerId,
                customerName: $order->name,
                customerEmail: $order->email,
                customerPhone: $order->phone,
            );
        }

        if (! $result['successful']) {
            throw new \Exception('Failed to create LinkQu transaction: '.$result['message']);
        }

        return $result;
    }
}
