<?php

namespace App\Actions\Main;

use App\Models\Order\Order;
use App\Models\Review\Review;

class SubmitReviewAction
{
    /**
     * Handle the action.
     *
     * @throws \Exception When the order isn't eligible for a review.
     */
    public function handle(Order $order, array $data): Review
    {
        if (! $order->isEligibleForReview()) {
            throw new \Exception('Pesanan ini belum bisa diberi ulasan.');
        }

        return Review::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'customer_name' => $order->name,
            'game_name' => $order->brand?->name ?? '-',
            'product_name' => $order->product?->name ?? '-',
            'rating' => $data['rating'],
            'review' => $data['review'],
        ]);
    }
}
