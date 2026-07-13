<?php

namespace App\Models\FlashSale;

use App\Enums\FlashSaleAfterEndActionEnum;
use App\Enums\FlashSaleStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class FlashSale extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'subtitle',
        'icon_type',
        'icon_emoji',
        'start_time',
        'end_time',
        'countdown_style',
        'auto_start',
        'auto_end',
        'after_end_action',
        'status',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'ended_at' => 'datetime',
            'auto_start' => 'boolean',
            'auto_end' => 'boolean',
            'status' => FlashSaleStatusEnum::class,
            'after_end_action' => FlashSaleAfterEndActionEnum::class,
        ];
    }

    public function products()
    {
        return $this->hasMany(FlashSaleProduct::class);
    }

    /**
     * Whether this sale can still be checked out at the flash price right now.
     * True while Active, and also while Ended if the admin chose to keep it
     * sellable ("Tetap Tampil") rather than reverting/hiding/marking sold out.
     */
    public function isPurchasable(): bool
    {
        return $this->status === FlashSaleStatusEnum::ACTIVE
            || ($this->status === FlashSaleStatusEnum::ENDED
                && $this->after_end_action === FlashSaleAfterEndActionEnum::KEEP_SHOWING);
    }

    #[Scope]
    public function purchasable(Builder $query)
    {
        return $query->where(fn ($q) => $q->where('status', FlashSaleStatusEnum::ACTIVE)
            ->orWhere(fn ($q) => $q->where('status', FlashSaleStatusEnum::ENDED)
                ->where('after_end_action', FlashSaleAfterEndActionEnum::KEEP_SHOWING)));
    }

    #[Scope]
    public function visibleOnHomepage(Builder $query)
    {
        return $query->where(fn ($q) => $q->where('status', FlashSaleStatusEnum::ACTIVE)
            ->orWhere(fn ($q) => $q->where('status', FlashSaleStatusEnum::ENDED)
                ->whereIn('after_end_action', [
                    FlashSaleAfterEndActionEnum::SOLD_OUT,
                    FlashSaleAfterEndActionEnum::KEEP_SHOWING,
                ])));
    }

    public function remainingSeconds(): int
    {
        return max(0, (int) now()->diffInSeconds($this->end_time, false));
    }

    /**
     * Single cached lookup shared by checkout price resolution and the
     * homepage: visibleOnHomepage() is the broader scope (also covers
     * Ended+sold_out/keep_showing for display), so callers that need the
     * stricter "can this still be bought" answer check isPurchasable()
     * on the result themselves rather than querying separately. One cache
     * key, one invalidation point (Cache::forget('flash_sale:active')).
     */
    public static function cachedVisible(): ?self
    {
        return Cache::remember('flash_sale:active', now()->addSeconds(60), function () {
            return self::query()
                ->visibleOnHomepage()
                ->with(['media', 'products.product.media', 'products.product.brand.media'])
                ->first();
        });
    }
}
