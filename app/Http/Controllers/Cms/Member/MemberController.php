<?php

namespace App\Http\Controllers\Cms\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\WithGetFilterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MemberController extends Controller
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
            ->role('user')
            ->withCount('orders')
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

        return inertia('cms/member/Index', [
            'data' => $model,
            'order' => $order,
            'orderBy' => $orderBy,
            'paginate' => $paginate,
            'searchBySpecific' => $searchBySpecific,
            'search' => $search,
            'resource' => $this->resource,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $member)
    {
        Gate::authorize('show'.$this->resource);

        $mutations = $member->balanceMutations()
            ->with('performedBy:id,name')
            ->latest()
            ->paginate(10, ['*'], 'mutations_page')
            ->withQueryString();

        $orders = $member->orders()
            ->with('brand:id,name', 'product:id,name')
            ->latest()
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();

        $deposits = $member->deposits()
            ->latest()
            ->paginate(10, ['*'], 'deposits_page')
            ->withQueryString();

        return inertia('cms/member/Show', [
            'member' => $member->loadCount('orders'),
            'mutations' => $mutations,
            'orders' => $orders,
            'deposits' => $deposits,
        ]);
    }
}
