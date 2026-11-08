<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Inventory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        
        $admin = User::create([
            'employee_code' => 'NV001',
            'username'      => 'admin',
            'password_hash' => Hash::make('12345678'),
            'full_name'     => 'Hồ Chí Hữu (Admin)',
            'email'         => 'huu.admin@sims.vn',
            'phone'         => '0901000001',
            'role'          => 'ADMIN',
            'work_shift'    => 'Hành chính',
            'is_active'     => true,
        ]);

        User::create([
            'employee_code' => 'TK002',
            'username'      => 'thukho',
            'password_hash' => Hash::make('12345678'),
            'full_name'     => 'Nguyễn Ngọc Sơn (Thủ kho)',
            'email'         => 'son.stock@sims.vn',
            'phone'         => '0901000002',
            'role'          => 'STOCKKEEPER',
            'work_shift'    => 'Ca Sáng',
            'is_active'     => true,
        ]);

        User::create([
            'employee_code' => 'BH003',
            'username'      => 'banhang',
            'password_hash' => Hash::make('12345678'),
            'full_name'     => 'Đào Văn Minh (Sales POS)',
            'email'         => 'minh.sales@sims.vn',
            'phone'         => '0901000003',
            'role'          => 'SALES',
            'work_shift'    => 'Ca Chiều',
            'is_active'     => true,
        ]);

        
        $catFashion = Category::create([
            'name'        => 'Thời trang',
            'slug'        => 'thoi-trang',
            'description' => 'Quần áo, phụ kiện thời trang',
            'created_by'  => $admin->id,
        ]);

        $catDrink = Category::create([
            'name'        => 'Đồ uống',
            'slug'        => 'do-uong',
            'description' => 'Nước giải khát đóng chai, lon',
            'created_by'  => $admin->id,
        ]);

        
        $p1 = Product::create([
            'sku_code'        => 'SP001',
            'category_id'     => $catFashion->id,
            'name'            => 'Áo Polo Nam Basic',
            'unit'            => 'Cái',
            'cost_price'      => 120000,
            'selling_price'   => 199000,
            'attributes_json' => ['size' => 'XL', 'color' => 'Đen'],
            'is_active'       => true,
        ]);
        Inventory::create([
            'product_id'     => $p1->id,
            'current_stock'  => 50,
            'safety_stock'   => 10,
            'shelf_location' => 'Kệ A1 - Tầng 1',
        ]);

        $p2 = Product::create([
            'sku_code'        => 'SP002',
            'category_id'     => $catDrink->id,
            'name'            => 'Nước Khoáng Lavie 500ml',
            'unit'            => 'Chai',
            'cost_price'      => 3500,
            'selling_price'   => 6000,
            'attributes_json' => ['exp_date' => '2027-05-01'],
            'is_active'       => true,
        ]);
        Inventory::create([
            'product_id'     => $p2->id,
            'current_stock'  => 8, // Nhỏ hơn safety_stock (10) để demo cảnh báo sắp hết hàng
            'safety_stock'   => 10,
            'shelf_location' => 'Kệ B2 - Tầng 1',
        ]);

        $p3 = Product::create([
            'sku_code'        => 'SP003',
            'category_id'     => $catFashion->id,
            'name'            => 'Quần Jeans Nam Slimfit',
            'unit'            => 'Cái',
            'cost_price'      => 220000,
            'selling_price'   => 350000,
            'attributes_json' => ['size' => '31', 'color' => 'Xanh đậm'],
            'is_active'       => true,
        ]);
        Inventory::create([
            'product_id'     => $p3->id,
            'current_stock'  => 25,
            'safety_stock'   => 10,
            'shelf_location' => 'Kệ A2 - Tầng 1',
        ]);
    }
}