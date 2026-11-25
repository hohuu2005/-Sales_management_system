<?php

/*
 * Model đại diện từng dòng hàng trong hóa đơn, gồm giá bán và giá vốn chốt lúc bán.
 * Được PosOrderService tạo sau khi khóa tồn kho và kiểm tra giỏ hàng.
 * Xem sự khác nhau của unit_price, unit_cost và cost_price tại docs/03-co-so-du-lieu.md.
 */

/*
 * Model đại diện từng dòng hàng trong hóa đơn, gồm giá bán và giá vốn chốt lúc bán.
 * Được PosOrderService tạo sau khi khóa tồn kho và kiểm tra giỏ hàng.
 * Xem sự khác nhau của unit_price, unit_cost và cost_price tại docs/03-co-so-du-lieu.md.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    protected $table = 'order_details';

    public $timestamps = false;
    
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'subtotal',
    ];
    protected $casts = [
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];
    public function calculateSubtotal(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
    public function order(): BelongsTo
    {

        return $this->belongsTo(Order::class, 'order_id');

    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
