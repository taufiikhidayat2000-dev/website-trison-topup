<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\PPOB\PPOBBrand;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = collect([
            ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0', 'lastmod' => null],
            ['loc' => route('terms'), 'changefreq' => 'monthly', 'priority' => '0.3', 'lastmod' => null],
            ['loc' => route('privacy-policy'), 'changefreq' => 'monthly', 'priority' => '0.3', 'lastmod' => null],
        ]);

        $brands = PPOBBrand::query()
            ->where('status', true)
            ->get(['slug', 'updated_at']);

        foreach ($brands as $brand) {
            $urls->push([
                'loc' => route('product.show', $brand->slug),
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'lastmod' => $brand->updated_at?->toAtomString(),
            ]);
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
