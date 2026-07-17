<?php

namespace App\Models\Reseller;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ResellerApplication extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'note',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
