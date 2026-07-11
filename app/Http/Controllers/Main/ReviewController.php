<?php

namespace App\Http\Controllers\Main;

use App\Actions\Main\SubmitReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Main\StoreReviewRequest;
use App\Models\Order\Order;

class ReviewController extends Controller
{
    /**
     * Store a newly created review for the given order.
     */
    public function store(StoreReviewRequest $request, Order $order, SubmitReviewAction $action)
    {
        try {
            $action->handle($order, $request->validated());
        } catch (\Exception $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return back()->with('success', 'Terima kasih atas ulasan Anda!');
    }
}
