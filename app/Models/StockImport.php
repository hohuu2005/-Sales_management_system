<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockImport extends Model
{
    protected $table = 'stock_imports';

    // Bảng stock_imports chỉ có created_at, không có updated_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'import_code',
        'stockkeeper_id',
        'supplier_name',
        'total_cost',
        'note',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public static function generateImportCode(): string
    {
        return 'IMP-' . date('YmdHis') . '-' . rand(100, 999);
    }

    public function stockkeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stockkeeper_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(StockImportDetail::class, 'stock_import_id');
    }
}
