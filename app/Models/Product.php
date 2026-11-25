<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'sku_code', 'category_id', 'name', 'unit',
        'cost_price', 'selling_price', 'image_url', 'attributes_json', 'is_active'
    ];

    // THÊM ĐOẠN NÀY ĐỂ ÉP KIỂU JSON
    protected $casts = [
        'cost_price'      => 'decimal:2',
        'selling_price'   => 'decimal:2',
        'attributes_json' => 'array',
        'is_active'       => 'boolean',
    ];

    public static function findBySku(string $sku_code): ?self
    {
        return self::whereRaw('LOWER(sku_code) = LOWER(?)', [trim($sku_code)])->first();
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'product_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}