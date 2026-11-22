<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockImport;
use App\Models\StockImportDetail;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class StockImportService
{
    /**
     * Lập một phiếu nhập kho.
     *
     * Trong MỘT transaction: tạo phiếu nhập, tạo từng dòng chi tiết, cộng tồn kho.
     * Lỗi ở bước nào cũng rollBack để phiếu nhập và tồn kho không bị lệch nhau.
     *
     * @param  array<int, array{product_id: int, import_quantity: int, import_price: float}>  $items
     */
    public function createImport(int $stockkeeperId, string $supplierName, ?string $note, array $items): StockImport
    {
        DB::beginTransaction();

        try {
            // Bước 1: tính tổng tiền của đợt nhập.
            $totalCost = 0;
            foreach ($items as $item) {
                $totalCost += (int) $item['import_quantity'] * (float) $item['import_price'];
            }

            // Bước 2: tạo phiếu nhập.
            $stockImport = StockImport::create([
                'import_code' => StockImport::generateImportCode(),
                'stockkeeper_id' => $stockkeeperId,
                'supplier_name' => $supplierName,
                'total_cost' => $totalCost,
                'note' => $note,
            ]);

            // Bước 3: tạo từng dòng chi tiết và cộng tồn kho.
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);

                if (! $product) {
                    throw new Exception('Có sản phẩm không tồn tại trong hệ thống.');
                }

                $quantity = (int) $item['import_quantity'];
                $price = (float) $item['import_price'];

                StockImportDetail::create([
                    'stock_import_id' => $stockImport->id,
                    'product_id' => $product->id,
                    'import_quantity' => $quantity,
                    'import_price' => $price,
                    'subtotal' => $quantity * $price,
                ]);

                // Khóa dòng tồn kho rồi mới cộng, để không bị ghi đè khi POS đang bán cùng lúc.
                $inventory = Inventory::where('product_id', $product->id)->lockForUpdate()->first();

                if (! $inventory) {
                    $inventory = Inventory::create([
                        'product_id' => $product->id,
                        'current_stock' => 0,
                    ]);
                }

                $inventory->addStock($quantity);

                // cost_price trong bảng products là "giá vốn nhập gần nhất".
                $product->update(['cost_price' => $price]);
            }

            DB::commit();

            return $stockImport;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
