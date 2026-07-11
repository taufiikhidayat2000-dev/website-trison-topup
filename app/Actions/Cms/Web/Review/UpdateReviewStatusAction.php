<?php

namespace App\Actions\Cms\Web\Review;

use App\Models\Review\Review;

class UpdateReviewStatusAction
{
    /**
     * Handle the action.
     */
    public function handle(Review $review, string $status): bool
    {
        return $review->update(['status' => $status]);
    }
}
