<?php

namespace App\Actions\Cms\PPOB\PPOBProductCategory;

use App\Models\PPOB\PPOBProductCategory;

class DeletePPOBProductCategoryAction
{
    /**
     * Handle the action.
     */
    public function handle(PPOBProductCategory $productCategory): ?bool
    {
        return $productCategory->delete();
    }
}
