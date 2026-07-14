<?php

namespace App\Models\Order;

use App\Enums\DigiflazzStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\FlashSale\FlashSale;
use App\Models\FlashSale\FlashSaleUse;
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
use Illuminate\Support\Facades\Crypt;
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
        'flash_sale_id',
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

    public function flashSaleUse()
    {
        return $this->morphOne(FlashSaleUse::class, 'usable');
    }

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
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

    /**
     * Decrypt manual-checkout password fields for admin display. Password
     * values are encrypted at rest (see StoreTransactionRequest) so the
     * game login credentials aren't stored in plaintext; only CMS order
     * views should call this, never the customer-facing transaction pages.
     *
     * Detects ciphertext by its own shape (self-describing) rather than by
     * cross-referencing the brand's *current* manual_fields definition -
     * that definition can be edited/renamed after orders already exist, in
     * which case matching by field type would leave old ciphertext
     * undecryptable or misidentify a plain field as encrypted.
     */
    public function decryptedSubmited(): array
    {
        $submited = $this->submited ?? [];

        foreach ($submited as $key => $value) {
            if (! is_string($value) || ! static::looksEncrypted($value)) {
                continue;
            }

            try {
                $submited[$key] = Crypt::decryptString($value);
            } catch (\Exception) {
                // Leave the raw value as-is if it can't be decrypted.
            }
        }

        return $submited;
    }

    /**
     * Whether a string looks like a Laravel Crypt-encrypted payload
     * (base64-encoded JSON with iv/value/mac), without actually decrypting
     * it. Shared by decryptedSubmited() and TransactionController::maskData()
     * so both sides agree on what counts as "sensitive" independently of any
     * mutable field configuration.
     */
    public static function looksEncrypted(string $value): bool
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']);
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
