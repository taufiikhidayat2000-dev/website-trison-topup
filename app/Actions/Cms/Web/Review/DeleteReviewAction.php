<?php

namespace App\Actions\Cms\Web\Review;

use App\Models\Review\Review;

class DeleteReviewAction
{
    /**
     * Handle the action.
     */
    public function handle(Review $review): ?bool
    {
        return $review->delete();
    }
}
