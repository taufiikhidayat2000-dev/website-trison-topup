<?php

namespace App\Imports;

use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;
use App\Models\PPOB\PPOBProduct;
use App\Models\PPOB\PPOBProductCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PPOBProductImport implements ToCollection, WithHeadingRow
{
    protected const VALID_PROVIDERS = ['digiflazz', 'lapakgaming', 'gift', 'manual_topup'];

    public int $created = 0;

    public int $updated = 0;

    /**
     * @var array<int, string>
     */
    public array $errors = [];

    /**
     * Locally uploaded image files, keyed by SKU (matched from filename).
     *
     * @param  array<string, UploadedFile>  $imagesBySku
     */
    public function __construct(
        protected array $imagesBySku = [],
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            // +2: 1 for zero-based index, 1 for the heading row itself
            $rowNumber = $index + 2;

            $categoryName = trim((string) ($row['category'] ?? ''));
            $brandName = trim((string) ($row['brand'] ?? ''));
            $productCategoryName = trim((string) ($row['product_category'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $sku = trim((string) ($row['sku'] ?? ''));
            $provider = trim((string) ($row['provider'] ?? ''));
            $buyPrice = $row['buy_price'] ?? null;
            $sellPrice = $row['sell_price'] ?? null;

            // Skip fully blank rows silently (common at the end of a spreadsheet)
            if ($categoryName === '' && $brandName === '' && $name === '' && $sku === '') {
                continue;
            }

            if ($name === '' || $sku === '') {
                $this->errors[] = "Baris {$rowNumber}: Name dan SKU wajib diisi.";

                continue;
            }

            if (! in_array($provider, self::VALID_PROVIDERS, true)) {
                $this->errors[] = "Baris {$rowNumber}: Provider '{$provider}' tidak valid. Gunakan: ".implode(', ', self::VALID_PROVIDERS).'.';

                continue;
            }

            if (! is_numeric($buyPrice) || ! is_numeric($sellPrice)) {
                $this->errors[] = "Baris {$rowNumber}: Buy Price dan Sell Price harus berupa angka.";

                continue;
            }

            $category = PPOBCategory::where('name', $categoryName)->first();
            if (! $category) {
                $this->errors[] = "Baris {$rowNumber}: Category '{$categoryName}' tidak ditemukan.";

                continue;
            }

            $brand = PPOBBrand::where('p_p_o_b_category_id', $category->id)->where('name', $brandName)->first();
            if (! $brand) {
                $this->errors[] = "Baris {$rowNumber}: Brand '{$brandName}' tidak ditemukan di category '{$categoryName}'.";

                continue;
            }

            $productCategory = null;
            if ($productCategoryName !== '') {
                $productCategory = PPOBProductCategory::where('name', $productCategoryName)->first();
                if (! $productCategory) {
                    $this->errors[] = "Baris {$rowNumber}: Product Category '{$productCategoryName}' tidak ditemukan, dilewati (produk tetap diimport).";
                }
            }

            $product = PPOBProduct::updateOrCreate(
                ['sku' => $sku],
                [
                    'p_p_o_b_brand_id' => $brand->id,
                    'p_p_o_b_product_category_id' => $productCategory?->id,
                    'provider' => $provider,
                    'name' => $name,
                    'description' => trim((string) ($row['description'] ?? '')) ?: null,
                    'buy_price' => (int) $buyPrice,
                    'sell_price' => (int) $sellPrice,
                    'delay' => (bool) ($row['delay'] ?? false),
                    'status' => (bool) ($row['status'] ?? true),
                ],
            );

            if ($product->wasRecentlyCreated) {
                $this->created++;
            } else {
                $this->updated++;
            }

            $this->attachImage($product, $sku, $rowNumber, trim((string) ($row['image_url'] ?? '')));
        }
    }

    /**
     * Prefer a locally uploaded image matched by SKU filename; fall back to
     * the "Image URL" column if no local file matches. Replaces (does not
     * accumulate) so re-importing the same SKU swaps the picture instead of
     * piling up copies.
     */
    protected function attachImage(PPOBProduct $product, string $sku, int $rowNumber, string $imageUrl): void
    {
        $localImage = $this->imagesBySku[$sku] ?? null;

        if ($localImage) {
            try {
                $product->clearMediaCollection('image');
                // preservingOriginal(): the same uploaded file can be shared
                // across several SKUs (filename "A+B+C.jpg"), so the source
                // must not be deleted after the first product uses it.
                $product->addMedia($localImage)->preservingOriginal()->toMediaCollection('image');
            } catch (\Exception $e) {
                $this->errors[] = "Baris {$rowNumber}: Produk tersimpan, tapi gagal memasang gambar lokal ({$e->getMessage()}).";
            }

            return;
        }

        if ($imageUrl !== '') {
            try {
                $product->clearMediaCollection('image');
                $product->addMediaFromUrl($imageUrl)->toMediaCollection('image');
            } catch (\Exception $e) {
                $this->errors[] = "Baris {$rowNumber}: Produk tersimpan, tapi gagal mengambil gambar dari URL ({$e->getMessage()}).";
            }
        }
    }
}
