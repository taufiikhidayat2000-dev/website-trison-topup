<?php

namespace App\Http\Controllers\Cms\PPOB;

use App\Actions\Cms\PPOB\PPOBProductCategory\DeletePPOBProductCategoryAction;
use App\Actions\Cms\PPOB\PPOBProductCategory\StorePPOBProductCategoryAction;
use App\Actions\Cms\PPOB\PPOBProductCategory\UpdatePPOBProductCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\PPOB\PPOBProductCategory\StorePPOBProductCategoryRequest;
use App\Http\Requests\Cms\PPOB\PPOBProductCategory\UpdatePPOBProductCategoryRequest;
use App\Models\PPOB\PPOBProductCategory;
use App\Traits\WithGetFilterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PPOBProductCategoryController extends Controller
{
    use WithGetFilterData;

    protected string $resource = PPOBProductCategory::class;

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
            model: PPOBProductCategory::withCount('products')->with('media'),
            searchBy: [
                'name',
                'description',
            ],
            order: $order,
            orderBy: $orderBy,
            paginate: $paginate,
            searchBySpecific: $searchBySpecific,
            s: $search,
        );

        $model->map(function ($item) {
            $item->image = $item->getFirstMediaUrl('image');

            return $item;
        });

        return inertia('cms/ppob/ppob-product-category/Index', [
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create'.$this->resource);

        return inertia('cms/ppob/ppob-product-category/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePPOBProductCategoryRequest $request, StorePPOBProductCategoryAction $action)
    {
        Gate::authorize('create'.$this->resource);

        $action->handle($request->validated());

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(PPOBProductCategory $productCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PPOBProductCategory $productCategory)
    {
        Gate::authorize('update'.$this->resource);

        $productCategory->image = $productCategory->getFirstMediaUrl('image');

        return inertia('cms/ppob/ppob-product-category/Edit', [
            'productCategory' => $productCategory,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePPOBProductCategoryRequest $request, PPOBProductCategory $productCategory, UpdatePPOBProductCategoryAction $action)
    {
        Gate::authorize('update'.$this->resource);

        $action->handle($productCategory, $request->validated());

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PPOBProductCategory $productCategory, DeletePPOBProductCategoryAction $action)
    {
        Gate::authorize('delete'.$this->resource);

        $action->handle($productCategory);

        return back();
    }
}
