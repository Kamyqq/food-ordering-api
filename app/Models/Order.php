<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['client_mail', 'client_phone', 'client_address', 'total_price', 'status', 'stripe_payment_id'];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    protected $casts = [
        'status' => OrderStatus::class,
    ];
}
