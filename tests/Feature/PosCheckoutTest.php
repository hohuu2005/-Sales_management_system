<?php

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

class PosCheckoutTest extends TestCase
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

    public function test_checkout_cash_success_deducts_stock_and_saves_prices(): void
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

    public function test_duplicate_sku_lines_are_merged_and_total_stock_is_deducted(): void
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

    public function test_cash_customer_paid_too_little_returns_422_without_changes(): void
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

    public function test_quantity_over_stock_returns_422_without_changes(): void
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

    public function test_inactive_product_returns_422_with_clear_message(): void
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

    public function test_client_unit_price_is_ignored(): void
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

    public function test_non_sales_user_receives_403(): void
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

    public function test_unexpected_detail_error_rolls_back_and_hides_details(): void
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

    public function test_order_code_collision_retries_and_still_creates_order(): void
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
            ->onlyMethods(['generateOrderCodeCandidate'])
            ->getMock();
        $service->expects($this->exactly(2))
            ->method('generateOrderCodeCandidate')
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
