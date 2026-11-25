<?php

// Migration mới bổ sung giá vốn chốt tại thời điểm bán để truy vết lợi nhuận từng hóa đơn.
// Không sửa migration cũ; PosOrderService ghi cột unit_cost khi tạo order_details.
// Xem ý nghĩa dữ liệu tại docs/03-co-so-du-lieu.md.

// Migration mới bổ sung giá vốn chốt tại thời điểm bán để truy vết lợi nhuận từng hóa đơn.
// Không sửa migration cũ; PosOrderService ghi cột unit_cost khi tạo order_details.
// Xem ý nghĩa dữ liệu tại docs/03-co-so-du-lieu.md.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->default(0)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
