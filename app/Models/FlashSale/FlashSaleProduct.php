<?php

namespace App\Models\FlashSale;

use App\Models\PPOB\PPOBProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FlashSaleProduct extends Model
{
    protected $fillable = [
        'flash_sale_id',
        'p_p_o_b_product_id',
        'pricing_type',
        'discount_percent',
        'original_price',
        'flash_price',
        'flash_stock',
        'sold',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'original_price' => 'integer',
            'flash_price' => 'integer',
            'flash_stock' => 'integer',
            'sold' => 'integer',
        ];
    }

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product()
    {
        return $this->belongsTo(PPOBProduct::class, 'p_p_o_b_product_id');
    }

    public function getRemainingStockAttribute(): int
    {
        return max(0, $this->flash_stock - $this->sold);
    }

    public function getProgressPercentAttribute(): int
    {
        return $this->flash_stock > 0 ? (int) round(($this->sold / $this->flash_stock) * 100) : 0;
    }

    public function isSoldOut(): bool
    {
        return $this->remaining_stock <= 0;
    }

    /**
     * Row-locked so concurrent settlements can't oversell the last unit of stock.
     */
    public function decrementStock(int $qty = 1): void
    {
        DB::transaction(function () use ($qty) {
            $row = self::query()->lockForUpdate()->find($this->id);

            if (! $row) {
                return;
            }

            $row->sold += $qty;

            if ($row->sold >= $row->flash_stock) {
                $row->status = 'sold_out';
            }

            $row->save();
        });
    }
}
