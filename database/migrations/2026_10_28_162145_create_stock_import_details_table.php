<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_import_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_import_id')->constrained('stock_imports')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('import_quantity');
            $table->decimal('import_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_import_details');
    }
};
