<?php

namespace Database\Factories;

use App\Enums\DigiflazzStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Models\PPOB\PPOBProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Produces synthetic, clearly-marked "DEMO" orders for local seeding/demo
 * purposes only (e.g. ReviewSeeder). Never run against a production database
 * — these are not real transactions and would pollute the order history.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        // Real orders always have an associated Payment (see StoreTransactionAction) —
        // some code paths (e.g. TransactionController::show) assume it exists.
        return $this->afterCreating(function (Order $order) {
            Payment::create([
                'driver' => 'manual',
                'payable_type' => Order::class,
                'payable_id' => $order->id,
                'order_id' => uniqid().time(),
                'payment_type' => 'bank_transfer',
                'account_number' => 'DEMO',
                'channel' => 'demo',
                'expired_at' => $order->created_at,
                'paid_at' => $order->created_at,
                'amount' => $order->total_amount,
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = PPOBProduct::inRandomOrder()->first();
        $amount = $product?->sell_price ?? fake()->numberBetween(10000, 500000);

        return [
            'p_p_o_b_brand_id' => $product?->p_p_o_b_brand_id,
            'p_p_o_b_product_id' => $product?->id,
            'reference' => 'TRX-DEMO-'.fake()->unique()->numerify('########'),
            'ref_number' => fake()->unique()->numberBetween(1, 999999),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '08'.fake()->numerify('##########'),
            'submited' => [
                'account_id' => (string) fake()->numberBetween(100000000, 999999999),
                'server_id' => (string) fake()->numberBetween(1000, 9999),
                'gift_send' => false,
                'dispute' => false,
                'done' => true,
            ],
            'amount' => $amount,
            'fee' => 0,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'payment_status' => PaymentStatusEnum::SETTLEMENT,
            'topup_status' => DigiflazzStatusEnum::SUCCESS,
        ];
    }
}
