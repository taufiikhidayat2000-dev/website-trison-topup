<?php

namespace App\Models\Review;

use App\Models\Order\Order;
use App\Models\User;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return ReviewFactory::new();
    }

    protected $fillable = [
        'order_id',
        'user_id',
        'customer_name',
        'game_name',
        'product_name',
        'rating',
        'review',
        'status',
        'admin_reply',
        'admin_replied_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'admin_replied_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
