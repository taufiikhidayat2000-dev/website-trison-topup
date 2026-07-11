<?php

namespace App\Actions\Cms\Web\Review;

use App\Models\Review\Review;

class ReplyToReviewAction
{
    /**
     * Handle the action.
     */
    public function handle(Review $review, string $reply): bool
    {
        return $review->update([
            'admin_reply' => $reply,
            'admin_replied_at' => now(),
        ]);
    }
}
