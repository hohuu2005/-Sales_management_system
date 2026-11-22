<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductService
{
    /**
     * Tạo sản phẩm và bản ghi tồn kho (current_stock = 0) trong cùng một transaction.
     * Nhờ vậy không bao giờ có sản phẩm mà thiếu dòng tồn kho.
     *
     * @param  array<string, mixed>  $data  sku_code, name, category_id, unit, cost_price, selling_price,
     *                                      attributes, safety_stock, shelf_location
     */
    public function createWithInventory(array $data): Product
    {
        DB::beginTransaction();

        try {
            $product = Product::create([
                'sku_code' => $data['sku_code'],
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'unit' => $data['unit'],
                'cost_price' => $data['cost_price'],
                'selling_price' => $data['selling_price'],
                'attributes_json' => empty($data['attributes']) ? null : $data['attributes'],
                'is_active' => true,
            ]);

            Inventory::create([
                'product_id' => $product->id,
                'current_stock' => 0,
                'safety_stock' => $data['safety_stock'] ?? 10,
                'shelf_location' => $data['shelf_location'] ?? null,
            ]);

            DB::commit();

            return $product;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Cập nhật thông tin sản phẩm cùng định mức tồn kho và vị trí kệ. Không thay đổi current_stock.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateWithInventory(Product $product, array $data): Product
    {
        DB::beginTransaction();

        try {
            $product->update([
                'sku_code' => $data['sku_code'],
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'unit' => $data['unit'],
                'cost_price' => $data['cost_price'],
                'selling_price' => $data['selling_price'],
                'attributes_json' => empty($data['attributes']) ? null : $data['attributes'],
                'is_active' => $data['is_active'] ?? $product->is_active,
            ]);

            $inventory = Inventory::where('product_id', $product->id)->first();

            if ($inventory) {
                $inventory->update([
                    'safety_stock' => $data['safety_stock'] ?? $inventory->safety_stock,
                    'shelf_location' => $data['shelf_location'] ?? null,
                ]);
            } else {
                Inventory::create([
                    'product_id' => $product->id,
                    'current_stock' => 0,
                    'safety_stock' => $data['safety_stock'] ?? 10,
                    'shelf_location' => $data['shelf_location'] ?? null,
                ]);
            }

            DB::commit();

            return $product;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
