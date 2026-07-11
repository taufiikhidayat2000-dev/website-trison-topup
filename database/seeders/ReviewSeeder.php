<?php

namespace Database\Seeders;

use App\Models\Order\Order;
use App\Models\Review\Review;
use Illuminate\Database\Seeder;

/**
 * Generates 200 demo reviews attached to synthetic "DEMO" orders for local
 * development/showcase purposes.
 *
 * WARNING: Do NOT run this seeder against a production database — it
 * creates 200 fake orders (reference prefixed `TRX-DEMO-`) purely so each
 * review has something to attach to (reviews require a genuinely delivered
 * order). Running it on a live store would inject fake "successful"
 * transactions into the real order history / revenue reports.
 */
class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->error('ReviewSeeder only runs in local/testing environments to avoid polluting production order data.');

            return;
        }

        for ($i = 0; $i < 200; $i++) {
            /** @var Order $order */
            $order = Order::factory()->create();

            Review::factory()
                ->weightedRating()
                ->create([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'customer_name' => $order->name,
                    'game_name' => $order->brand?->name ?? 'Mobile Legends',
                    'product_name' => $order->product?->name ?? 'Diamond',
                    'created_at' => now()->subDays(fake()->numberBetween(0, 59))->subMinutes(fake()->numberBetween(0, 1440)),
                ]);
        }

        $this->command?->info('Seeded 200 demo reviews (orders prefixed TRX-DEMO-).');
    }
}
