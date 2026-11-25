<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CauSqlMauTaiLieuTest extends TestCase
{
    use RefreshDatabase;

    public function test_cau_sql_mau_trong_tai_lieu_chay_duoc(): void
    {
        $cashier = User::create([
            'employee_code' => 'NV-SQL',
            'username' => 'thu-ngan-sql',
            'password_hash' => Hash::make('12345678'),
            'full_name' => 'Thu ngan SQL',
            'email' => 'thu-ngan-sql@sims.vn',
            'phone' => null,
            'role' => 'SALES',
            'work_shift' => 'Ca test',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Danh muc SQL',
            'slug' => 'danh-muc-sql',
            'description' => null,
            'created_by' => $cashier->id,
        ]);

        $product = Product::create([
            'sku_code' => 'SQL001',
            'category_id' => $category->id,
            'name' => 'San pham SQL',
            'unit' => 'Cai',
            'cost_price' => 40000,
            'selling_price' => 100000,
            'image_url' => null,
            'attributes_json' => null,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'current_stock' => 100,
            'safety_stock' => 10,
            'shelf_location' => 'SQL-1',
        ]);

        $orderOne = $this->createCompletedOrder($cashier, 'ORD-SQL-001', 200000, '2026-11-20 10:00:00');
        OrderDetail::create([
            'order_id' => $orderOne->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100000,
            'unit_cost' => 40000,
            'subtotal' => 200000,
        ]);

        $orderTwo = $this->createCompletedOrder($cashier, 'ORD-SQL-002', 100000, '2026-11-21 10:00:00');
        OrderDetail::create([
            'order_id' => $orderTwo->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100000,
            'unit_cost' => 50000,
            'subtotal' => 100000,
        ]);

        $cancelled = $this->createCompletedOrder($cashier, 'ORD-SQL-CANCEL', 999999, '2026-11-21 11:00:00');
        $cancelled->update(['status' => 'CANCELLED']);

        $revenue = DB::select(
            "SELECT DATE(created_at) AS ngay, COUNT(*) AS so_hoa_don, SUM(total_amount) AS doanh_thu
             FROM orders
             WHERE status = 'COMPLETED'
               AND DATE(created_at) BETWEEN '2026-11-20' AND '2026-11-25'
             GROUP BY DATE(created_at)
             ORDER BY ngay"
        );

        $this->assertCount(2, $revenue);
        $this->assertEquals('2026-11-20', $revenue[0]->ngay);
        $this->assertEquals(1, (int) $revenue[0]->so_hoa_don);
        $this->assertEquals(200000, (float) $revenue[0]->doanh_thu);
        $this->assertEquals('2026-11-21', $revenue[1]->ngay);
        $this->assertEquals(1, (int) $revenue[1]->so_hoa_don);
        $this->assertEquals(100000, (float) $revenue[1]->doanh_thu);

        $profit = DB::select(
            "SELECT DATE(o.created_at) AS ngay,
                    SUM(d.quantity * d.unit_price) AS doanh_thu,
                    SUM(d.quantity * d.unit_cost) AS gia_von,
                    SUM(d.quantity * (d.unit_price - d.unit_cost)) AS loi_nhuan_gop
             FROM orders o
             JOIN order_details d ON d.order_id = o.id
             WHERE o.status = 'COMPLETED'
               AND o.created_at >= '2026-11-20'
               AND o.created_at < '2026-11-26'
             GROUP BY DATE(o.created_at)
             ORDER BY ngay"
        );

        $this->assertCount(2, $profit);
        $this->assertEquals(200000, (float) $profit[0]->doanh_thu);
        $this->assertEquals(80000, (float) $profit[0]->gia_von);
        $this->assertEquals(120000, (float) $profit[0]->loi_nhuan_gop);
        $this->assertEquals(100000, (float) $profit[1]->doanh_thu);
        $this->assertEquals(50000, (float) $profit[1]->gia_von);
        $this->assertEquals(50000, (float) $profit[1]->loi_nhuan_gop);
    }

    private function createCompletedOrder(User $cashier, string $code, float $total, string $createdAt): Order
    {
        $order = Order::create([
            'order_code' => $code,
            'cashier_id' => $cashier->id,
            'total_amount' => $total,
            'customer_paid' => $total,
            'change_amount' => 0,
            'payment_method' => 'CASH',
            'status' => 'COMPLETED',
        ]);

        DB::table('orders')->where('id', $order->id)->update(['created_at' => $createdAt]);

        return $order->fresh();
    }
}
