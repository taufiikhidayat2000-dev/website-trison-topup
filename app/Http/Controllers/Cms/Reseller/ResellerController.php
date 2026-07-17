<?php

namespace App\Http\Controllers\Cms\Reseller;

use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Reseller\ResellerPriceUse;
use App\Models\User;
use App\Traits\WithGetFilterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ResellerController extends Controller
{
    use WithGetFilterData;

    protected string $resource = User::class;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('view'.$this->resource);

        $order = $request?->order ?? 'desc';
        $orderBy = $request?->orderBy ?? 'users.created_at';
        $paginate = $request?->paginate ?? 10;
        $searchBySpecific = $request?->searchBySpecific ?? '';
        $search = $request?->search ?? '';

        $model = User::query()
            ->role('reseller')
            ->withCount('orders')
            ->withSum(['orders as total_omzet' => function ($query) {
                $query->where('payment_status', PaymentStatusEnum::SETTLEMENT);
            }], 'total_amount')
            ->select('users.*');

        $model = $this->getDataWithFilter(
            model: $model,
            searchBy: [
                'users.name',
                'users.email',
                'users.phone',
            ],
            order: $order,
            orderBy: $orderBy,
            paginate: $paginate,
            searchBySpecific: $searchBySpecific,
            s: $search,
        );

        // Total discount given per reseller isn't a direct column on users, so
        // it's computed per row here rather than via a DB-level join - this
        // page is a low-traffic admin dashboard limited to one page (10-20
        // rows) at a time, so the extra query per row is a fine trade-off for
        // not having to hand-roll a polymorphic join.
        $model->getCollection()->transform(function (User $reseller) {
            $reseller->total_hemat = ResellerPriceUse::where('usable_type', Order::class)
                ->whereIn('usable_id', $reseller->orders()->pluck('id'))
                ->sum('discount_amount');

            return $reseller;
        });

        return inertia('cms/reseller/Index', [
            'data' => $model,
            'order' => $order,
            'orderBy' => $orderBy,
            'paginate' => $paginate,
            'searchBySpecific' => $searchBySpecific,
            'search' => $search,
        ]);
    }
}
