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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique()->index();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_method', ['CASH', 'VIETQR']);
            $table->enum('status', ['COMPLETED', 'CANCELLED'])->default('COMPLETED');
            $table->string('note', 255)->nullable();
            $table->timestamp('completed_at')->useCurrent()->index();
            $table->index(['completed_at', 'status']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
