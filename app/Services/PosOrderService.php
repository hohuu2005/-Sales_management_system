<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class PosOrderService
{
    /**
     * Thanh toán một hóa đơn POS.
     *
     * Tất cả các bước nằm trong MỘT transaction: nếu bất kỳ bước nào lỗi thì rollBack,
     * không có chuyện đã lưu hóa đơn mà chưa trừ kho (hoặc ngược lại).
     *
     * @param  array<int, array{sku_code: string, quantity: int}>  $items
     */
    public function checkout(int $cashierId, string $paymentMethod, float $customerPaid, array $items): Order
    {
        // Gộp các dòng trùng mã SKU để kiểm tra tồn kho cho đúng tổng số lượng.
        $quantities = [];
        foreach ($items as $item) {
            $sku = trim((string) $item['sku_code']);
            $quantities[$sku] = ($quantities[$sku] ?? 0) + (int) $item['quantity'];
        }

        DB::beginTransaction();

        try {
            $lines = [];
            $totalAmount = 0;

            // Bước 1: kiểm tra và KHÓA tồn kho của từng mặt hàng.
            foreach ($quantities as $sku => $quantity) {
                $product = Product::findBySku($sku);

                if (! $product || ! $product->is_active) {
                    throw new Exception("Không tìm thấy sản phẩm có mã {$sku}.");
                }

                // lockForUpdate = SELECT ... FOR UPDATE: thu ngân khác phải chờ,
                // tránh hai người cùng bán món hàng cuối cùng.
                $inventory = Inventory::where('product_id', $product->id)->lockForUpdate()->first();

                if (! $inventory || ! $inventory->hasEnoughStock($quantity)) {
                    $remaining = $inventory ? $inventory->current_stock : 0;
                    throw new Exception("Sản phẩm {$product->name} không đủ số lượng trong kho (còn {$remaining}).");
                }

                // Giá lấy từ CSDL, không tin giá gửi lên từ trình duyệt.
                $unitPrice = (float) $product->selling_price;
                $subtotal = $unitPrice * $quantity;
                $totalAmount += $subtotal;

                $lines[] = [
                    'product' => $product,
                    'inventory' => $inventory,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }

            // Bước 2: kiểm tra tiền khách đưa.
            if ($paymentMethod === 'VIETQR') {
                // Chuyển khoản: khách trả đúng bằng tổng tiền, không có tiền thừa.
                $customerPaid = $totalAmount;
            } elseif ($customerPaid < $totalAmount) {
                throw new Exception('Tiền khách đưa chưa đủ.');
            }

            $changeAmount = $customerPaid - $totalAmount;

            // Bước 3: lưu hóa đơn.
            $order = Order::create([
                'order_code' => Order::generateOrderCode(),
                'cashier_id' => $cashierId,
                'total_amount' => $totalAmount,
                'customer_paid' => $customerPaid,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethod,
                'status' => 'COMPLETED',
            ]);

            // Bước 4: lưu từng dòng chi tiết và trừ tồn kho.
            foreach ($lines as $line) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);

                $line['inventory']->deductStock($line['quantity']);
            }

            DB::commit();

            return $order;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
