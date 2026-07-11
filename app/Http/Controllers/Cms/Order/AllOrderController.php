<?php

namespace App\Http\Controllers\Cms\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Traits\WithGetFilterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AllOrderController extends Controller
{
    use WithGetFilterData;

    protected string $resource = Order::class;

    /**
     * Maps the unified "type" filter to the underlying product providers
     * that make up each of the 3 existing order pages.
     */
    protected const TYPE_PROVIDERS = [
        'topup' => ['digiflazz', 'lapakgaming'],
        'gift' => ['gift'],
        'manual' => ['manual_topup'],
    ];

    /**
     * Display a unified listing of Topup, Gift, and Manual Topup orders.
     *
     * All 3 order "types" live in the same `orders` table (differentiated
     * only by product->provider), so this is a single query with an
     * optional provider filter - no union/collection-merge needed.
     */
    public function __invoke(Request $request)
    {
        Gate::authorize('view'.$this->resource);

        $order = $request?->order ?? 'desc';
        $orderBy = $request?->orderBy ?? 'created_at';
        $paginate = $request?->paginate ?? 10;
        $searchBySpecific = $request?->searchBySpecific ?? '';
        $search = $request?->search ?? '';
        $type = $request?->type ?? 'all';
        $paymentStatusFilter = $request?->payment_status ?? [];
        $dateFrom = $request?->date_from ?? '';
        $dateTo = $request?->date_to ?? '';

        $query = Order::with('brand', 'product', 'payment.media', 'user:id,name,email')
            ->withoutArchive();

        if (isset(self::TYPE_PROVIDERS[$type])) {
            $query->whereHas('product', fn ($q) => $q->whereIn('provider', self::TYPE_PROVIDERS[$type]));
        }

        // Apply payment status filter (same semantics as the 3 existing pages)
        if (! empty($paymentStatusFilter)) {
            $query->where(function ($q) use ($paymentStatusFilter) {
                foreach ($paymentStatusFilter as $status) {
                    if ($status === 'pending') {
                        $q->orWhere('payment_status', 0);
                    } elseif ($status === 'settlement') {
                        $q->orWhere('payment_status', 2);
                    } elseif ($status === 'failed') {
                        $q->orWhereIn('payment_status', [-1, -2]);
                    }
                }
            });
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $model = $this->getDataWithFilter(
            model: $query,
            searchBy: [
                'reference',
                'name',
                'email',
                'phone',
            ],
            order: $order,
            orderBy: $orderBy,
            paginate: $paginate,
            searchBySpecific: $searchBySpecific,
            s: $search,
        );

        // Normalize: attach a unified order_type + hydrate manual payment proof image
        $model->map(function ($item) {
            $item->order_type = match (true) {
                in_array($item->product?->provider, ['digiflazz', 'lapakgaming'], true) => 'topup',
                $item->product?->provider === 'gift' => 'gift',
                $item->product?->provider === 'manual_topup' => 'manual',
                default => 'unknown',
            };

            if ($item->payment?->driver === 'manual') {
                $item->payment->image = $item->payment->getFirstMediaUrl('image');
            }
            $item->payment?->makeHidden('media');

            return $item;
        });

        return inertia('cms/order/all-order/Index', [
            'data' => $model,
            'order' => $order,
            'orderBy' => $orderBy,
            'paginate' => $paginate,
            'searchBySpecific' => $searchBySpecific,
            'search' => $search,
            'resource' => $this->resource,
            'type' => $type,
            'paymentStatusFilter' => $paymentStatusFilter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
