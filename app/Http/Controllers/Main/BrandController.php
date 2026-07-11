<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\PPOB\PPOBBrand;
use App\Models\Review\Review;
use App\Models\Web\Faq;
use Illuminate\Support\Facades\Cache;
use Inertia\Response;

class BrandController extends Controller
{
    public function show(PPOBBrand $brand): Response
    {
        $brand->load(['products.media', 'products.productCategory.media', 'category']);

        $brand->image = $brand->getFirstMediaUrl('image');
        $brand->banner = $brand->getFirstMediaUrl('banner');
        $brand->default_product_image = $brand->getFirstMediaUrl('default_product_image');

        $brand->products->each(function ($product) use ($brand) {
            $product->image = $product->getFirstMediaUrl('image')
                ?: $product->productCategory?->getFirstMediaUrl('image')
                ?: $brand->default_product_image;
            $product->makeHidden('media');
        });

        $brand->makeHidden('media');

        $settingTitle = getSetting('title');
        $settingFavicon = getSetting('favicon') ?: '/favicon.svg';

        $reviewStats = Cache::remember("reviews:statistic:{$brand->id}", now()->addMinutes(5), function () use ($brand) {
            $total = Review::published()->where('game_name', $brand->name)->count();

            return [
                'average' => $total > 0 ? round((float) Review::published()->where('game_name', $brand->name)->avg('rating'), 2) : 0,
                'total' => $total,
            ];
        });

        $reviews = Cache::remember("reviews:carousel:{$brand->id}", now()->addMinutes(5), function () use ($brand) {
            return Review::published()
                ->where('game_name', $brand->name)
                ->latest()
                ->limit(10)
                ->get(['id', 'customer_name', 'game_name', 'rating', 'review', 'admin_reply', 'created_at']);
        });

        return inertia()->render('main/BrandDetail', [
            'brand' => $brand,
            'faqs' => Faq::where('status', true)->orderBy('order', 'asc')->get(),
            'reviewStats' => $reviewStats,
            'reviews' => $reviews,
        ])->withViewData([
            'meta' => [
                'title' => "{$brand->name} - Top Up Murah & Cepat | {$settingTitle}",
                'description' => "Top up {$brand->name} termurah dan terpercaya di {$settingTitle}. Proses instan, tersedia berbagai metode pembayaran.",
                'keywords' => "top up {$brand->name}, beli {$brand->name}, harga {$brand->name}, {$brand->name} murah, {$settingTitle}, topup game",
                'author' => $settingTitle,
                'application_name' => $settingTitle,
                'url' => route('product.show', $brand->slug),
                'image' => $brand->image ?: (config('app.url').$settingFavicon),
            ],
        ]);
    }
}
