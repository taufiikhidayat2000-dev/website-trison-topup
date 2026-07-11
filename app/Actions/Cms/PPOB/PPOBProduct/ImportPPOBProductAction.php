<?php

namespace App\Actions\Cms\PPOB\PPOBProduct;

use App\Imports\PPOBProductImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ImportPPOBProductAction
{
    /**
     * Handle the action.
     *
     * @param  array<int, UploadedFile>|null  $images  Locally uploaded product
     *                                                 photos, matched to a
     *                                                 row by SKU filename
     *                                                 (e.g. "ML86.jpg" -> SKU
     *                                                 "ML86"). One file can
     *                                                 cover several SKUs at
     *                                                 once by joining them
     *                                                 with "+" in the
     *                                                 filename, e.g.
     *                                                 "ML86+ML172+ML257.jpg".
     * @return array{created: int, updated: int, errors: array<int, string>}
     */
    public function handle(UploadedFile $file, ?array $images = null): array
    {
        $imagesBySku = [];
        foreach ($images ?? [] as $image) {
            $filenameStem = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

            foreach (explode('+', $filenameStem) as $sku) {
                $sku = trim($sku);

                if ($sku !== '') {
                    $imagesBySku[$sku] = $image;
                }
            }
        }

        $import = new PPOBProductImport($imagesBySku);

        Excel::import($import, $file);

        return [
            'created' => $import->created,
            'updated' => $import->updated,
            'errors' => $import->errors,
        ];
    }
}
