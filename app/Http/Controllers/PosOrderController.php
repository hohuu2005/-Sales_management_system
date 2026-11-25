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
use Carbon\Carbon;
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
     * Tìm sản phẩm POS theo mã SKU hoặc tên.
     */
    public function timSanPham(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2 || mb_strlen($q) > 50) {
            return response()->json(['data' => []]);
        }

        $escaped = str_replace(['!', '\\', '%', '_'], ['!!', '!!', '!%', '!_'], $q);
        $lower = mb_strtolower($escaped, 'UTF-8');
        $pattern = '%' . $lower . '%';

        $products = Product::with('inventory')
            ->where('is_active', true)
            ->where(function ($query) use ($pattern) {
                $query->whereRaw("LOWER(sku_code) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern]);
            })
            ->orderByRaw("CASE WHEN LOWER(sku_code) = ? THEN 0 WHEN LOWER(sku_code) LIKE ? THEN 1 ELSE 2 END", [mb_strtolower(trim($q), 'UTF-8'), mb_strtolower(trim($q), 'UTF-8') . '%'])
            ->orderBy('name')
            ->limit(10)
            ->get();

        $data = $products->map(function (Product $product) {
            return [
                'sku_code' => $product->sku_code,
                'name' => $product->name,
                'unit' => $product->unit,
                'selling_price' => (float) $product->selling_price,
                'current_stock' => $product->inventory ? (int) $product->inventory->current_stock : 0,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * Lịch sử hóa đơn của thu ngân đang đăng nhập.
     */
    public function myHistory(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ], [
            'start_date.date_format' => 'Ngày bắt đầu phải đúng định dạng YYYY-MM-DD.',
            'end_date.date_format' => 'Ngày kết thúc phải đúng định dạng YYYY-MM-DD.',
            'end_date.after_or_equal' => 'Ngày kết thúc không được trước ngày bắt đầu.',
        ]);

        $startDate = ! empty($data['start_date']) ? Carbon::createFromFormat('Y-m-d', $data['start_date'])->startOfDay() : null;
        $endDate = ! empty($data['end_date']) ? Carbon::createFromFormat('Y-m-d', $data['end_date'])->endOfDay() : null;

        if ($startDate && $endDate && $startDate->diffInDays($endDate) > 366) {
            return back()->withErrors(['end_date' => 'Khoảng lọc tối đa 366 ngày.'])->withInput();
        }

        $baseQuery = Order::with('details.product')->where('cashier_id', Auth::id());

        if ($startDate) {
            $baseQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $baseQuery->where('created_at', '<=', $endDate);
        }

        $summary = (clone $baseQuery)
            ->where('status', 'COMPLETED')
            ->selectRaw('COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS total_amount')
            ->first();

        $orders = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate(20)
            ->withQueryString();

        return view('pos.history', [
            'orders' => $orders,
            'start_date' => $data['start_date'] ?? '',
            'end_date' => $data['end_date'] ?? '',
            'total_orders' => (int) ($summary->order_count ?? 0),
            'total_completed_amount' => (float) ($summary->total_amount ?? 0),
        ]);
    }

    /**
     * Xem chi tiết một hóa đơn của thu ngân đang đăng nhập để xem hoặc in lại.
     */
    public function chiTietHoaDon(Request $request, int $order)
    {
        $orderModel = Order::with(['cashier', 'details.product'])
            ->where('cashier_id', Auth::id())
            ->find($order);

        if (! $orderModel) {
            abort(404);
        }

        return view('pos.chi-tiet-hoa-don', [
            'order' => $orderModel,
            'start_date' => $request->query('start_date', ''),
            'end_date' => $request->query('end_date', ''),
        ]);
    }
}
