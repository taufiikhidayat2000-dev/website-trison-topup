<?php

namespace App\Models\Order;

use App\Enums\DigiflazzStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment\Payment;
use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBProduct;
use App\Models\Review\Review;
use App\Models\User;
use App\Models\Voucher\VoucherUse;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Order extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected static function newFactory()
    {
        return OrderFactory::new();
    }

    protected $fillable = [
        'user_id',
        'p_p_o_b_brand_id',
        'p_p_o_b_product_id',
        'reference',
        'ref_number',
        'name',
        'email',
        'phone',
        'note',
        'submited',
        'amount',
        'fee',
        'discount_amount',
        'total_amount',
        'payment_status',
        'topup_status',
        'archive_at',
        'sn',
    ];

    protected $casts = [
        'submited' => 'array',
        'archive_at' => 'datetime',
        'payment_status' => PaymentStatusEnum::class,
        'topup_status' => DigiflazzStatusEnum::class,
    ];

    public function getRouteKeyName()
    {
        return 'reference';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(PPOBProduct::class, 'p_p_o_b_product_id');
    }

    public function brand()
    {
        return $this->belongsTo(PPOBBrand::class, 'p_p_o_b_brand_id');
    }

    public function payment()
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function notifications()
    {
        return $this->hasMany(OrderNotification::class);
    }

    public function voucherUse()
    {
        return $this->morphOne(VoucherUse::class, 'usable');
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Whether the order has actually been delivered to the customer, and is
     * therefore eligible for a review. Delivery is tracked differently per
     * provider: digiflazz/lapakgaming update `topup_status`, while gift and
     * manual_topup orders track completion inside the `submited` JSON blob
     * (see DeliveryProgressCard.vue for the matching frontend logic).
     */
    public function isDelivered(): bool
    {
        if ($this->payment_status !== PaymentStatusEnum::SETTLEMENT) {
            return false;
        }

        if (in_array($this->product?->provider, ['digiflazz', 'lapakgaming'])) {
            return $this->topup_status === DigiflazzStatusEnum::SUCCESS;
        }

        // gift / manual_topup
        return (bool) ($this->submited['gift_send'] ?? false) || (bool) ($this->submited['done'] ?? false);
    }

    public function isEligibleForReview(): bool
    {
        return $this->isDelivered() && ! $this->review()->exists();
    }

    #[Scope]
    public function onlyArchive(Builder $query)
    {
        return $query->whereNotNull('archive_at');
    }

    #[Scope]
    public function withoutArchive(Builder $query)
    {
        return $query->whereNull('archive_at');
    }
}
