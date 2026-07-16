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
            $item->order_type = $this->resolveOrderType($item->product?->provider);

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
            // Wrapped in a Closure so it's skipped entirely (never queried) on
            // partial reloads that don't request it, e.g. the `only: ['data']`
            // reload triggered by the new-order poll - Inertia strips
            // non-requested keys from the props array before invoking any
            // Closure-valued props.
            'lastOrderId' => fn () => (int) (Order::query()->withoutArchive()->max('id') ?? 0),
        ]);
    }

    /**
     * Lightweight polling endpoint used by the All Orders page to detect
     * newly-created orders (any type/status, ignoring the page's current
     * filters) so the admin can be alerted with a sound/desktop notification
     * without a full page reload.
     */
    public function pollNew(Request $request)
    {
        Gate::authorize('view'.$this->resource);

        $afterId = (int) $request->query('after_id', 0);

        $orders = Order::with('product:id,name,provider')
            ->withoutArchive()
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(50)
            ->get(['id', 'reference', 'name', 'p_p_o_b_product_id', 'total_amount', 'payment_status', 'created_at']);

        $orders->each(function ($order) {
            $order->order_type = $this->resolveOrderType($order->product?->provider);
            $order->makeHidden(['product', 'p_p_o_b_product_id']);
        });

        return response()->json([
            'orders' => $orders,
            'last_id' => $orders->max('id') ?? $afterId,
        ]);
    }

    /**
     * Classify an order's unified type from its product's provider, using
     * TYPE_PROVIDERS as the single source of truth (shared by the type
     * filter, the listing, and the poll endpoint).
     */
    private function resolveOrderType(?string $provider): string
    {
        foreach (self::TYPE_PROVIDERS as $type => $providers) {
            if (in_array($provider, $providers, true)) {
                return $type;
            }
        }

        return 'unknown';
    }
}
