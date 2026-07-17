<?php

namespace App\Http\Controllers\Cms\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Reseller\ResellerApplication;
use App\Traits\WithGetFilterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ResellerApplicationController extends Controller
{
    use WithGetFilterData;

    protected string $resource = ResellerApplication::class;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('view'.$this->resource);

        $order = $request?->order ?? 'desc';
        $orderBy = $request?->orderBy ?? 'reseller_applications.created_at';
        $paginate = $request?->paginate ?? 10;
        $searchBySpecific = $request?->searchBySpecific ?? '';
        $search = $request?->search ?? '';
        $status = $request?->status ?? '';

        $model = ResellerApplication::query()
            ->with('user:id,name,email', 'reviewedBy:id,name')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->select('reseller_applications.*');

        $model = $this->getDataWithFilter(
            model: $model,
            searchBy: [
                'reseller_applications.business_name',
            ],
            order: $order,
            orderBy: $orderBy,
            paginate: $paginate,
            searchBySpecific: $searchBySpecific,
            s: $search,
        );

        return inertia('cms/reseller/applications/Index', [
            'data' => $model,
            'order' => $order,
            'orderBy' => $orderBy,
            'paginate' => $paginate,
            'searchBySpecific' => $searchBySpecific,
            'search' => $search,
            'status' => $status,
        ]);
    }
}
