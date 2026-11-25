<?php

/*
 * Model đại diện bảng hóa đơn orders và quan hệ với thu ngân cùng chi tiết hàng hóa.
 * Được PosOrderService tạo trong luồng thanh toán và dùng tại lịch sử POS.
 * Xem cấu trúc bảng tại docs/03-co-so-du-lieu.md.
 */

/*
 * Model đại diện bảng hóa đơn orders và quan hệ với thu ngân cùng chi tiết hàng hóa.
 * Được PosOrderService tạo trong luồng thanh toán và dùng tại lịch sử POS.
 * Xem cấu trúc bảng tại docs/03-co-so-du-lieu.md.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    // Bảng orders chỉ có created_at, không có updated_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'order_code',
        'cashier_id',
        'total_amount',
        'customer_paid',
        'change_amount',
        'payment_method',
        'status',
        'note',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'customer_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function calculateChange(float $paid): float
    {
        return max(0, $paid - (float) $this->total_amount);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }
}
