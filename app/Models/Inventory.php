<?php

/*
 * Model quản lý tồn kho hiện tại, tồn tối thiểu và vị trí kệ của từng sản phẩm.
 * PosOrderService khóa Inventory để trừ kho an toàn trong luồng POS.
 * Xem quy tắc tồn kho tại docs/04-quy-tac-nghiep-vu.md.
 */

namespace App\Models;

use App\Exceptions\LoiBanHangException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $table = 'inventories';

    // Cột updated_at do MySQL tự cập nhật (useCurrentOnUpdate), Eloquent không cần ghi.
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'current_stock',
        'safety_stock',
        'shelf_location',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'safety_stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function hasEnoughStock(int $req_qty): bool
    {
        return $this->current_stock >= $req_qty;
    }

    public function deductStock(int $qty): void
    {
        if (! $this->hasEnoughStock($qty)) {
            throw new LoiBanHangException('Sản phẩm không đủ tồn kho!');
        }

        $this->decrement('current_stock', $qty);
    }

    public function addStock(int $qty): void
    {
        $this->increment('current_stock', $qty);
    }

    /**
     * Tồn kho đang ở mức cần cảnh báo nhập hàng (current_stock <= safety_stock).
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->safety_stock;
    }
}
