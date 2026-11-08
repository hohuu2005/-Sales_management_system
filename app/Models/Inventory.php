<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Exception;


class Inventory extends Model
{
    protected $table = 'inventories';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'current_stock',
        'safety_stock', 
        'shelf_location',
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');

    }
    public function hasEnoughStock(int $req_qty ): bool
    {
        return $this->current_stock >= $req_qty;
    }
    public function deductStock(int $qty): void
    {
        if (!$this->hasEnoughStock($qty)) {
            throw new Exception("San pham khong du ton kho!");

        }
        $this->decrement('current_stock', $qty);

    }
    public function addStock(int $qty): void
    {
        $this->increment('current_stock', $qty);
    }
    //
}
