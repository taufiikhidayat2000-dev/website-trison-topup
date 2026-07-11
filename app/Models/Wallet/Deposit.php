<?php

namespace App\Models\Wallet;

use App\Enums\DepositStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'user_id',
        'driver',
        'reference',
        'amount',
        'fee',
        'total_pay',
        'channel',
        'payment_type',
        'account_number',
        'account_code',
        'status',
        'linkqu_reference',
        'expired_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee' => 'integer',
        'total_pay' => 'integer',
        'status' => DepositStatusEnum::class,
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'reference';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
