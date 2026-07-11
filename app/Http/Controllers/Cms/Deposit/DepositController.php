<?php

namespace App\Http\Controllers\Cms\Deposit;

use App\Http\Controllers\Controller;
use App\Models\Wallet\Deposit;
use App\Traits\WithGetFilterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DepositController extends Controller
{
    use WithGetFilterData;

    protected string $resource = Deposit::class;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('view'.$this->resource);

        $order = $request?->order ?? 'desc';
        $orderBy = $request?->orderBy ?? 'deposits.created_at';
        $paginate = $request?->paginate ?? 10;
        $searchBySpecific = $request?->searchBySpecific ?? '';
        $search = $request?->search ?? '';
        $status = $request?->status ?? '';

        $model = Deposit::query()
            ->with('user:id,name,email')
            ->select('deposits.*');

        if ($status) {
            $model->where('status', $status);
        }

        $model = $this->getDataWithFilter(
            model: $model,
            searchBy: [
                'deposits.reference',
                'deposits.linkqu_reference',
            ],
            order: $order,
            orderBy: $orderBy,
            paginate: $paginate,
            searchBySpecific: $searchBySpecific,
            s: $search,
        );

        return inertia('cms/deposit/Index', [
            'data' => $model,
            'order' => $order,
            'orderBy' => $orderBy,
            'paginate' => $paginate,
            'searchBySpecific' => $searchBySpecific,
            'search' => $search,
            'status' => $status,
            'resource' => $this->resource,
        ]);
    }
}
