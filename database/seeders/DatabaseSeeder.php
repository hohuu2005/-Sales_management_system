<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;
use App\Models\Product;
use App\Models\Inventory;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::create([
            'employee_code' => 'NV001',
            'username' => 'admin',
            'password_hash' => Hash::make('12345678'),
            'full_name' => 'Hồ Chí Hữu (Admin)',
            'email' => 'hochihuu2005@gmail.com',
            'phone' => '0395814661',
            'role' => 'ADMIN',
            'work_shift' => 'Hành chính',
            'is_active' => true,
        ]);

        User::created([
            'employee_code' => 'TK002',
            'username' => 'thukho',
            'password_hash' => Hash::make('12345678'),
            'full_name' => 'Nguyễn Ngọc Sơn (Thủ kho)',
            'email' => 'son.stock@sims.vn',
            'phone' => '0395814662',
            'role' => 'STOCKKEEPER',
            'work_shift' => 'Ca Sáng',
            'is_active' => true,
        ]);

        User::create([
            'employee_code' => 'BH003',
            'username' => 'banhang',
            'password_hash' => Hash::make('12345678'),
            'full_name' => 'Đào Văn Minh (sales POS)',
            'email' => 'minh.sales@sims.vn',
            'phone' => '0395814663',
            'role' => 'SALES',
            'work_shift' => 'Ca Chiều',
            'is_active' => true,
        ]);

        $catFashion = Category::create([
            'name' => 'Thời trang',
            'slug' => 'thoi-trang',
            'description' => 'Các sản phẩm thời trang, quần áo, giày dép, phụ kiện.',
            'created_by' => $admin->id,
        ]);

        $catDrink = Category::create([
            'name' => 'Đồ uống',
            'slug' => 'do-uong',
            'description' => 'Các loại đồ uống, nước giải khát, cà phê, trà, nước ép.',
            'created_by' => $admin->id,
        ]);

        $p1 = Product::create([
            'sku_code' => 'SP001',
            'category_id' => $catFashion->id,
            'name' => 'Áo thun nam',
            'unit' => 'Cái',
            'cost_price' => 150000,
            'selling_price' => 250000,
            'attributes_json' => ['size' => 'XL', 'color' => 'Đen'],
            'is_active' => true,
        ]);
        Inventory::create([
            'product_id' => $p1->id,
            
            'current_stock' => 100,
            'safe_stock' => 10,
            'shelf_location' => 'Kệ A1 - Tầng 1',

        ]);
        $p2 = Product::create([
            'sku_code' => 'SP002',
            'category_id' => $catDrink->id,
            'name' => 'Cà phê sữa đá',
            'unit' => 'Chai',
            'cost_price' => 10000,
            'selling_price' => 20000,
            'attributes_json' => ['exp_date' => '2024-12-31'],
            'is_active' => true,
        ]);
        Inventory::create([
            'product_id' => $p2->id,
            'current_stock' => 200,
            'safe_stock' => 10,
            'shelf_location' => 'Kệ B2 - Tầng 1',
        ]);
    }
}
