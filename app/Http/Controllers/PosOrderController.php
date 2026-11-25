<?php

/*
 * Controller nhận request POS: quét SKU, thanh toán và xem lịch sử hóa đơn của thu ngân.
 * Route gọi controller từ routes/web.php, logic nghiệp vụ chính được chuyển sang PosOrderService.
 * Xem luồng chi tiết tại docs/02-luong-ban-hang-pos.md.
 */

namespace App\Http\Controllers;

use App\Exceptions\LoiBanHangException;
use App\Models\Order;
use App\Models\Product;
use App\Services\PosOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PosOrderController extends Controller
{
    /**
     * Hiển thị màn hình bán hàng POS.
     */
    public function posScreen()
    {
        return view('pos.index');
    }

    /**
     * Nhận mã SKU từ máy quét (AJAX), trả JSON gồm tên, giá, tồn kho.
     */
    public function scanBarcode(Request $request)
    {
        $sku = trim((string) $request->query('sku_code', ''));

        if ($sku === '') {
            return response()->json(['message' => 'Vui lòng nhập mã SKU.'], 422);
        }

        $product = Product::findBySku($sku);

        if (! $product || ! $product->is_active) {
            return response()->json(['message' => "Không tìm thấy sản phẩm có mã {$sku}."], 404);
        }

        $inventory = $product->inventory;

        return response()->json([
            'sku_code' => $product->sku_code,
            'name' => $product->name,
            'unit' => $product->unit,
            'selling_price' => (float) $product->selling_price,
            'current_stock' => $inventory ? (int) $inventory->current_stock : 0,
        ]);
    }

    /**
     * Kiểm tra dữ liệu đầu vào, gọi PosOrderService để thanh toán, trả JSON kết quả.
     */
    public function checkout(Request $request, PosOrderService $service)
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:CASH,VIETQR'],
            'customer_paid' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.sku_code' => ['required', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ], [
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'customer_paid.numeric' => 'Tiền khách đưa phải là số.',
            'customer_paid.min' => 'Tiền khách đưa không được âm.',
            'customer_paid.max' => 'Tiền khách đưa tối đa 1.000.000.000 đ.',
            'items.required' => 'Giỏ hàng đang trống.',
            'items.array' => 'Dữ liệu giỏ hàng không hợp lệ.',
            'items.min' => 'Giỏ hàng phải có ít nhất một sản phẩm.',
            'items.max' => 'Giỏ hàng tối đa 100 dòng.',
            'items.*.sku_code.required' => 'Vui lòng nhập mã SKU cho mỗi dòng.',
            'items.*.sku_code.string' => 'Mã SKU phải là chuỗi ký tự.',
            'items.*.sku_code.max' => 'Mã SKU tối đa 50 ký tự.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng cho mỗi dòng.',
            'items.*.quantity.integer' => 'Số lượng mỗi dòng phải là số nguyên.',
            'items.*.quantity.min' => 'Số lượng mỗi dòng tối thiểu là 1.',
            'items.*.quantity.max' => 'Số lượng mỗi dòng tối đa 9999.',
        ]);

        try {
            $order = $service->checkout(
                Auth::id(),
                $data['payment_method'],
                (float) ($data['customer_paid'] ?? 0),
                $data['items'],
            );
        } catch (LoiBanHangException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Lỗi hệ thống, vui lòng thử lại sau.'], 500);
        }

        return response()->json([
            'message' => 'Thanh toán thành công.',
            'order' => [
                'order_code' => $order->order_code,
                'total_amount' => (float) $order->total_amount,
                'customer_paid' => (float) $order->customer_paid,
                'change_amount' => (float) $order->change_amount,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at ? $order->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                'cashier' => Auth::user()->full_name,
            ],
        ]);
    }

    /**
     * Lịch sử hóa đơn của thu ngân đang đăng nhập.
     */
    public function myHistory()
    {
        $orders = Order::with('details.product')
            ->where('cashier_id', Auth::id())
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('pos.history', ['orders' => $orders]);
    }
}
