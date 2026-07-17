<?php

namespace App\Models\Reseller;

use Illuminate\Database\Eloquent\Model;

class ResellerPriceUse extends Model
{
    protected $fillable = [
        'usable_type',
        'usable_id',
        'original_price',
        'reseller_price',
        'discount_amount',
        'discount_percent_snapshot',
    ];

    protected $casts = [
        'original_price' => 'integer',
        'reseller_price' => 'integer',
        'discount_amount' => 'integer',
        'discount_percent_snapshot' => 'float',
    ];

    public function usable()
    {
        return $this->morphTo();
    }
}
