<?php

namespace App\Actions\Cms\PPOB\PPOBProductCategory;

use App\Models\PPOB\PPOBProductCategory;
use App\Traits\WithMediaCollection;
use Illuminate\Http\UploadedFile;

class UpdatePPOBProductCategoryAction
{
    use WithMediaCollection;

    /**
     * Handle the action.
     */
    public function handle(PPOBProductCategory $productCategory, array $data): bool
    {
        if (($data['image'] ?? null) instanceof UploadedFile) {
            $this->saveMedia(
                model: $productCategory,
                file: $data['image'],
                collection: 'image',
            );
        }

        return $productCategory->update($data);
    }
}
