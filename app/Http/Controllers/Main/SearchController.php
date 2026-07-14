<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\PPOB\PPOBBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    /**
     * Realtime game/product search used by the header autocomplete.
     */
    public function products(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
        ]);

        $query = trim($validated['q']);

        $brands = PPOBBrand::query()
            ->with('category', 'media')
            ->where('status', true)
            ->search($query)
            ->orderByRaw('name = ? desc', [$query])
            ->orderByRaw('name like ? desc', ["{$query}%"])
            ->orderBy('order')
            ->limit(8)
            ->get();

        if ($brands->isEmpty()) {
            $brands = $this->fuzzySearch($query);
        }

        return response()->json(
            $brands->map(fn (PPOBBrand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'thumbnail' => $brand->getFirstMediaUrl('image'),
                'subtitle' => $brand->category?->name ?: str($brand->description ?? '')->limit(60)->toString(),
                'url' => route('product.show', $brand->slug),
            ])->values()
        );
    }

    /**
     * Typo-tolerant fallback so queries like "mobel" still surface "Mobile Legends".
     */
    private function fuzzySearch(string $query): Collection
    {
        $needle = mb_strtolower($query);

        $candidates = Cache::remember('search:active-brands', now()->addMinutes(5), fn () => PPOBBrand::query()
            ->with('category', 'media')
            ->where('status', true)
            ->orderBy('order')
            ->get());

        return $candidates
            ->map(function (PPOBBrand $brand) use ($needle) {
                similar_text($needle, mb_strtolower($brand->name), $percent);

                return ['brand' => $brand, 'score' => $percent];
            })
            ->filter(fn ($match) => $match['score'] >= 45)
            ->sortByDesc('score')
            ->take(8)
            ->pluck('brand');
    }
}
