<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockImportDetail extends Model
{
    protected $table = 'stock_import_details';
    
    public $timestamps = false;

    protected $fillable = [
        'stock_import_id',
        'product_id',
        'quantity',
        'import_price ',
        'subtotal',
    ];
    protected $casts = [
        'import_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];
    public function calculateSubtotal(): float
    {
        return (float) $this->quantity * (float) $this->import_price;
    }
    public function stockImport(): BelongsTo
    {
        return $this->belongsTo(StockImport::class, 'stock_import_id');
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');}
}
