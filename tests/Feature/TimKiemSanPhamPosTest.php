<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TimKiemSanPhamPosTest extends TestCase
{
    use RefreshDatabase;

    private function createSalesUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'employee_code' => 'NV-SEARCH',
            'username' => 'thu-ngan-search',
            'password_hash' => Hash::make('12345678'),
            'full_name' => 'Thu ngan Search',
            'email' => 'thu-ngan-search@sims.vn',
            'phone' => null,
            'role' => 'SALES',
            'work_shift' => 'Ca test',
            'is_active' => true,
        ], $overrides));
    }

    private function createProduct(string $sku, string $name, int $stock = 10, bool $active = true): Product
    {
        $creator = $this->createSalesUser([
            'employee_code' => 'NV-CAT-' . substr($sku, -3),
            'username' => 'cat-' . strtolower($sku),
            'email' => strtolower($sku) . '@sims.vn',
        ]);

        $category = Category::create([
            'name' => 'Danh muc ' . $sku,
            'slug' => 'danh-muc-' . strtolower($sku),
            'description' => null,
            'created_by' => $creator->id,
        ]);

        $product = Product::create([
            'sku_code' => $sku,
            'category_id' => $category->id,
            'name' => $name,
            'unit' => 'Cai',
            'cost_price' => 40000,
            'selling_price' => 100000,
            'image_url' => null,
            'attributes_json' => null,
            'is_active' => $active,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'current_stock' => $stock,
            'safety_stock' => 10,
            'shelf_location' => 'A1',
        ]);

        return $product;
    }

    public function test_tim_theo_mot_phan_sku(): void
    {
        $sales = $this->createSalesUser();
        $this->createProduct('SP-ALPHA', 'Ao Alpha');
        $this->actingAs($sales)->getJson('/pos/search?q=alp')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.sku_code', 'SP-ALPHA');
    }

    public function test_tim_theo_mot_phan_ten_khong_phan_biet_hoa_thuong(): void
    {
        $sales = $this->createSalesUser();
        $this->createProduct('SP-NAME', 'Lavie Nuoc Khoang');
        $this->actingAs($sales)->getJson('/pos/search?q=LAVIE')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_q_mot_ky_tu_tra_mang_rong(): void
    {
        $sales = $this->createSalesUser();
        $this->actingAs($sales)->getJson('/pos/search?q=A')->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_q_ky_tu_dac_biet_khong_tra_moi_san_pham(): void
    {
        $sales = $this->createSalesUser();
        $this->createProduct('SP-TEST', 'San pham test');
        $this->actingAs($sales)->getJson('/pos/search?q=%25')->assertOk()->assertExactJson(['data' => []]);
        $this->actingAs($sales)->getJson('/pos/search?q=_')->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_san_pham_ngung_ban_khong_xuat_hien(): void
    {
        $sales = $this->createSalesUser();
        $this->createProduct('SP-OFF', 'San pham ngung ban', 10, false);
        $this->actingAs($sales)->getJson('/pos/search?q=ngung')->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_tim_kiem_toi_da_10_ket_qua(): void
    {
        $sales = $this->createSalesUser();
        for ($i = 1; $i <= 12; $i++) {
            $this->createProduct('SP-LIMIT-' . $i, 'San pham limit ' . $i);
        }
        $this->actingAs($sales)->getJson('/pos/search?q=limit')->assertOk()->assertJsonCount(10, 'data');
    }

    public function test_json_khong_chua_cost_price(): void
    {
        $sales = $this->createSalesUser();
        $this->createProduct('SP-SECURE', 'San pham bao mat');
        $response = $this->actingAs($sales)->getJson('/pos/search?q=bao mat');
        $response->assertOk();
        $this->assertArrayNotHasKey('cost_price', $response->json('data.0'));
    }

    public function test_tai_khoan_khong_phai_sales_bi_chan_403(): void
    {
        $admin = $this->createSalesUser([
            'employee_code' => 'NV-ADMIN-SEARCH',
            'username' => 'admin-search',
            'email' => 'admin-search@sims.vn',
            'role' => 'ADMIN',
        ]);
        $this->actingAs($admin)->getJson('/pos/search?q=test')->assertForbidden();
    }

    public function test_quet_sku_viet_thuong_van_thanh_toan_thanh_cong(): void
    {
        $sales = $this->createSalesUser();
        $product = $this->createProduct('Mixed-CASE-001', 'San pham hoa thuong');
        $response = $this->actingAs($sales)->postJson('/pos/checkout', [
            'payment_method' => 'CASH',
            'customer_paid' => 100000,
            'items' => [['sku_code' => 'mixed-case-001', 'quantity' => 1]],
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'current_stock' => 9]);
    }
}
