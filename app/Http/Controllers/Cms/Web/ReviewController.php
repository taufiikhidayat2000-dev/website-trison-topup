<?php

namespace App\Http\Controllers\Cms\Web;

use App\Actions\Cms\Web\Review\DeleteReviewAction;
use App\Actions\Cms\Web\Review\ReplyToReviewAction;
use App\Actions\Cms\Web\Review\UpdateReviewStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Web\Review\ReplyReviewRequest;
use App\Http\Requests\Cms\Web\Review\UpdateReviewStatusRequest;
use App\Models\Review\Review;
use App\Traits\WithGetFilterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReviewController extends Controller
{
    use WithGetFilterData;

    protected string $resource = Review::class;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('view'.$this->resource);

        $order = $request?->order ?? 'desc';
        $orderBy = $request?->orderBy ?? 'created_at';
        $paginate = $request?->paginate ?? 10;
        $searchBySpecific = $request?->searchBySpecific ?? '';
        $search = $request?->search ?? '';

        $model = $this->getDataWithFilter(
            model: Review::with('order:id,reference')
                ->when($request->filter_rating, fn ($q) => $q->where('rating', $request->filter_rating))
                ->when($request->filter_game, fn ($q) => $q->where('game_name', $request->filter_game))
                ->when($request->filter_status, fn ($q) => $q->where('status', $request->filter_status))
                ->when($request->filter_date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->filter_date_from))
                ->when($request->filter_date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->filter_date_to)),
            searchBy: [
                'customer_name',
                'review',
                'game_name',
                'product_name',
            ],
            order: $order,
            orderBy: $orderBy,
            paginate: $paginate,
            searchBySpecific: $searchBySpecific,
            s: $search,
        );

        return inertia('cms/web/review/Index', [
            'data' => $model,
            'order' => $order,
            'orderBy' => $orderBy,
            'paginate' => $paginate,
            'searchBySpecific' => $searchBySpecific,
            'search' => $search,
            'resource' => $this->resource,
            'games' => Review::query()->distinct()->orderBy('game_name')->pluck('game_name'),
            'filter_rating' => $request->filter_rating ?? null,
            'filter_game' => $request->filter_game ?? null,
            'filter_status' => $request->filter_status ?? null,
            'filter_date_from' => $request->filter_date_from ?? null,
            'filter_date_to' => $request->filter_date_to ?? null,
            'stats' => $this->statistics(),
        ]);
    }

    /**
     * Compute the moderation-facing statistics (includes hidden/pending reviews,
     * unlike the public homepage widget which only counts published ones).
     */
    protected function statistics(): array
    {
        $total = Review::count();
        $average = round((float) Review::avg('rating'), 2);

        $distribution = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = Review::where('rating', $star)->count();
            $distribution[$star] = $total > 0 ? round($count / $total * 100, 1) : 0;
        }

        $chart = collect(range(29, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);

            return [
                'date' => $date->format('Y-m-d'),
                'count' => Review::whereDate('created_at', $date->toDateString())->count(),
            ];
        })->values();

        return [
            'total' => $total,
            'average' => $average,
            'today' => Review::whereDate('created_at', now()->toDateString())->count(),
            'this_month' => Review::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
            'distribution' => $distribution,
            'chart' => $chart,
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(Review $review)
    {
        Gate::authorize('show'.$this->resource);

        return inertia('cms/web/review/Show', [
            'review' => $review->load('order:id,reference'),
        ]);
    }

    /**
     * Update the review's moderation status (publish/hide/pending).
     */
    public function updateStatus(UpdateReviewStatusRequest $request, Review $review, UpdateReviewStatusAction $action)
    {
        Gate::authorize('update'.$this->resource);

        $action->handle($review, $request->validated('status'));

        return back()->with('success', 'Review status updated successfully.');
    }

    /**
     * Reply to the specified review as an admin.
     */
    public function reply(ReplyReviewRequest $request, Review $review, ReplyToReviewAction $action)
    {
        Gate::authorize('update'.$this->resource);

        $action->handle($review, $request->validated('admin_reply'));

        return back()->with('success', 'Reply sent successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Review $review, DeleteReviewAction $action)
    {
        Gate::authorize('delete'.$this->resource);

        $action->handle($review);

        return back()->with('success', 'Review deleted successfully.');
    }

    /**
     * Export the (filtered) reviews list as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('export'.$this->resource);

        $reviews = Review::with('order:id,reference')
            ->when($request->filter_rating, fn ($q) => $q->where('rating', $request->filter_rating))
            ->when($request->filter_game, fn ($q) => $q->where('game_name', $request->filter_game))
            ->when($request->filter_status, fn ($q) => $q->where('status', $request->filter_status))
            ->when($request->filter_date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->filter_date_from))
            ->when($request->filter_date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->filter_date_to))
            ->orderByDesc('created_at')
            ->get();

        $filename = 'reviews-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($reviews) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Invoice', 'Customer', 'Game', 'Product', 'Rating', 'Review', 'Status', 'Admin Reply', 'Date']);

            foreach ($reviews as $review) {
                fputcsv($handle, [
                    $review->id,
                    $review->order?->reference,
                    $review->customer_name,
                    $review->game_name,
                    $review->product_name,
                    $review->rating,
                    $review->review,
                    $review->status,
                    $review->admin_reply,
                    $review->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
