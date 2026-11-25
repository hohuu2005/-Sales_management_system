<?php

/*
 * Bài kiểm thử chức năng thanh toán POS bằng HTTP, transaction, tồn kho và phân quyền.
 * Chạy từ Laravel Test runner và mô phỏng dữ liệu trực tiếp qua model.
 * Cách chạy và danh sách ca kiểm thử nằm tại docs/06-huong-dan-kiem-thu.md.
 */

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Services\PosOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ThanhToanPosTest extends TestCase
{
    use RefreshDatabase;

    private function createSalesUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'employee_code' => 'NV-TEST',
            'username' => 'sales-test',
            'password_hash' => Hash::make('12345678'),
            'full_name' => 'Nhân viên POS',
            'email' => 'sales-test@sims.vn',
            'phone' => null,
            'role' => 'SALES',
            'work_shift' => 'Ca test',
            'is_active' => true,
        ], $overrides));
    }

    private function createProduct(array $productOverrides = [], array $inventoryOverrides = []): array
    {
        $category = Category::create([
            'name' => 'Danh mục test',
            'slug' => 'danh-muc-test',
            'description' => null,
            'created_by' => $this->createSalesUser([
                'employee_code' => 'NV-CAT',
                'username' => 'category-test',
                'email' => 'category-test@sims.vn',
            ])->id,
        ]);

        $product = Product::create(array_merge([
            'sku_code' => 'SP001',
            'category_id' => $category->id,
            'name' => 'Sản phẩm test',
            'unit' => 'Cái',
            'cost_price' => 80000,
            'selling_price' => 100000,
            'image_url' => null,
            'attributes_json' => null,
            'is_active' => true,
        ], $productOverrides));

        $inventory = Inventory::create(array_merge([
            'product_id' => $product->id,
            'current_stock' => 10,
            'safety_stock' => 2,
            'shelf_location' => 'A1',
        ], $inventoryOverrides));

        return [$product, $inventory];
    }

    public function test_thanh_toan_tien_mat_thanh_cong_va_tru_kho(): void
    {
        $sales = $this->createSalesUser();
        [$product, $inventory] = $this->createProduct();

        $response = $this->actingAs($sales)->postJson('/pos/checkout', [
            'payment_method' => 'CASH',
            'customer_paid' => 250000,
            'items' => [['sku_code' => 'SP001', 'quantity' => 2]],
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Thanh toán thành công.')
            ->assertJsonPath('order.total_amount', 200000);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'current_stock' => 8,
        ]);

        $detail = OrderDetail::firstOrFail();
        $this->assertEquals('100000.00', $detail->unit_price);
        $this->assertEquals('80000.00', $detail->unit_cost);
    }

    public function test_gop_cac_dong_trung_sku(): void
    {
        $sales = $this->createSalesUser();
        [$product] = $this->createProduct([], ['current_stock' => 10]);

        $response = $this->actingAs($sales)->postJson('/pos/checkout', [
            'payment_method' => 'CASH',
            'customer_paid' => 500000,
            'items' => [
                ['sku_code' => 'SP001', 'quantity' => 2],
                ['sku_code' => 'SP001', 'quantity' => 3],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'current_stock' => 5,
        ]);
        $this->assertEquals(1, OrderDetail::count());
        $this->assertEquals(5, OrderDetail::firstOrFail()->quantity);
    }

    public function test_tien_khach_dua_thieu_tra_ve_422(): void
    {
        $sales = $this->createSalesUser();
        [$product] = $this->createProduct([], ['current_stock' => 10]);

        $response = $this->actingAs($sales)->postJson('/pos/checkout', [
            'payment_method' => 'CASH',
            'customer_paid' => 50000,
            'items' => [['sku_code' => 'SP001', 'quantity' => 1]],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Tiền khách đưa chưa đủ.');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'current_stock' => 10]);
    }

    public function test_so_luong_vuot_ton_kho(): void
    {
        $sales = $this->createSalesUser();
        [$product] = $this->createProduct([], ['current_stock' => 2]);

        $response = $this->actingAs($sales)->postJson('/pos/checkout', [
            'payment_method' => 'CASH',
            'customer_paid' => 300000,
            'items' => [['sku_code' => 'SP001', 'quantity' => 3]],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Sản phẩm Sản phẩm test không đủ số lượng trong kho (còn 2).']);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'current_stock' => 2]);
    }

    public function test_san_pham_ngung_ban(): void
    {
        $sales = $this->createSalesUser();
        [$product] = $this->createProduct(['is_active' => false]);

        $response = $this->actingAs($sales)->postJson('/pos/checkout', [
            'payment_method' => 'CASH',
            'customer_paid' => 100000,
            'items' => [['sku_code' => 'SP001', 'quantity' => 1]],
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Sản phẩm Sản phẩm test đã ngừng bán.']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_bo_qua_don_gia_do_trinh_duyet_gui(): void
    {
        $sales = $this->createSalesUser();
        [$product] = $this->createProduct();

        $response = $this->actingAs($sales)->postJson('/pos/checkout', [
            'payment_method' => 'CASH',
            'customer_paid' => 100000,
            'items' => [['sku_code' => 'SP001', 'quantity' => 1, 'unit_price' => 1]],
        ]);

        $response->assertOk()->assertJsonPath('order.total_amount', 100000);
        $this->assertEquals('100000.00', (string) Order::firstOrFail()->total_amount);
        $this->assertEquals('100000.00', (string) OrderDetail::firstOrFail()->unit_price);
    }

    public function test_tai_khoan_khong_phai_sales_bi_chan_403(): void
    {
        $admin = $this->createSalesUser([
            'employee_code' => 'NV-ADMIN',
            'username' => 'admin-test',
            'email' => 'admin-test@sims.vn',
            'role' => 'ADMIN',
        ]);

        $response = $this->actingAs($admin)->postJson('/pos/checkout', [
            'payment_method' => 'CASH',
            'customer_paid' => 100000,
            'items' => [['sku_code' => 'SP001', 'quantity' => 1]],
        ]);

        $response->assertForbidden();
    }

    public function test_loi_bat_ngo_hoan_tac_va_khong_lo_chi_tiet(): void
    {
        $sales = $this->createSalesUser();
        [$product] = $this->createProduct();

        DB::statement("CREATE TRIGGER fail_order_detail BEFORE INSERT ON order_details BEGIN SELECT RAISE(ABORT, 'forced technical detail failure'); END;");

        $response = $this->actingAs($sales)->postJson('/pos/checkout', [
            'payment_method' => 'CASH',
            'customer_paid' => 100000,
            'items' => [['sku_code' => 'SP001', 'quantity' => 1]],
        ]);

        $response->assertStatus(500)
            ->assertJsonPath('message', 'Lỗi hệ thống, vui lòng thử lại sau.')
            ->assertJsonMissing(['forced technical detail failure']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'current_stock' => 10]);
    }

    public function test_ma_hoa_don_trung_se_thu_lai(): void
    {
        $sales = $this->createSalesUser();
        [$product] = $this->createProduct();

        Order::create([
            'order_code' => 'ORD-COLLISION-0001',
            'cashier_id' => $sales->id,
            'total_amount' => 1,
            'customer_paid' => 1,
            'change_amount' => 0,
            'payment_method' => 'CASH',
            'status' => 'COMPLETED',
        ]);

        $service = $this->getMockBuilder(PosOrderService::class)
            ->onlyMethods(['taoMaHoaDonUngVien'])
            ->getMock();
        $service->expects($this->exactly(2))
            ->method('taoMaHoaDonUngVien')
            ->willReturnOnConsecutiveCalls('ORD-COLLISION-0001', 'ORD-UNIQUE-0001');

        $this->app->instance(PosOrderService::class, $service);

        $response = $this->actingAs($sales)->postJson('/pos/checkout', [
            'payment_method' => 'CASH',
            'customer_paid' => 100000,
            'items' => [['sku_code' => 'SP001', 'quantity' => 1]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', ['order_code' => 'ORD-UNIQUE-0001']);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'current_stock' => 9]);
    }
}
