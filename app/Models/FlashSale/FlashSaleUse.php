<?php

namespace App\Models\FlashSale;

use Illuminate\Database\Eloquent\Model;

class FlashSaleUse extends Model
{
    protected $fillable = [
        'flash_sale_id',
        'flash_sale_product_id',
        'usable_type',
        'usable_id',
        'original_price',
        'flash_price',
        'discount_amount',
    ];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function flashSaleProduct()
    {
        return $this->belongsTo(FlashSaleProduct::class);
    }

    public function usable()
    {
        return $this->morphTo();
    }
}
