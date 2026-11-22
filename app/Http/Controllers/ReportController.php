<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Order;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * (ADMIN) Báo cáo doanh thu theo khoảng ngày. Mặc định là 7 ngày gần nhất.
     */
    public function revenueReport(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], [
            'end_date.after_or_equal' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
        ]);

        $end = ! empty($data['end_date']) ? date('Y-m-d', strtotime($data['end_date'])) : date('Y-m-d');
        $start = ! empty($data['start_date']) ? date('Y-m-d', strtotime($data['start_date'])) : date('Y-m-d', strtotime($end . ' -6 days'));

        return view('reports.revenue', $this->buildRevenue($start, $end) + [
            'start_date' => $start,
            'end_date' => $end,
            'low_stock_count' => $this->buildInventory('low')->count(),
        ]);
    }

    /**
     * (ADMIN, THỦ KHO) Báo cáo tồn kho. filter_type=low chỉ hiện sản phẩm chạm định mức tối thiểu.
     */
    public function inventoryReport(Request $request)
    {
        $filter = $request->query('filter_type') === 'low' ? 'low' : 'all';

        return view('reports.inventory', [
            'rows' => $this->buildInventory($filter),
            'filter_type' => $filter,
            'low_stock_count' => $this->buildInventory('low')->count(),
        ]);
    }

    // ---------------------------------------------------------------------
    // Các hàm xử lý bên dưới không phụ thuộc vào Request nên dễ kiểm thử riêng.
    // ---------------------------------------------------------------------

    /**
     * Tính doanh thu từ các hóa đơn COMPLETED trong khoảng [start, end] (định dạng Y-m-d).
     * Trả về tổng doanh thu, số hóa đơn, tổng theo phương thức thanh toán và doanh thu từng ngày.
     */
    protected function buildRevenue(string $start, string $end): array
    {
        $orders = Order::where('status', 'COMPLETED')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->orderBy('created_at')
            ->get(['id', 'total_amount', 'payment_method', 'created_at']);

        // Tạo sẵn mọi ngày trong khoảng (tối đa 62 ngày) để ngày không có hóa đơn vẫn hiện số 0.
        $days = [];
        $period = new DatePeriod(new DateTime($start), new DateInterval('P1D'), (new DateTime($end))->modify('+1 day'));
        foreach ($period as $day) {
            if (count($days) >= 62) {
                break;
            }
            $days[$day->format('Y-m-d')] = ['label' => $day->format('d/m'), 'revenue' => 0.0, 'orders' => 0];
        }

        $totalRevenue = 0.0;
        $cashTotal = 0.0;
        $qrTotal = 0.0;

        foreach ($orders as $order) {
            $amount = (float) $order->total_amount;
            $totalRevenue += $amount;

            if ($order->payment_method === 'CASH') {
                $cashTotal += $amount;
            } else {
                $qrTotal += $amount;
            }

            $key = $order->created_at ? $order->created_at->format('Y-m-d') : null;
            if ($key !== null && isset($days[$key])) {
                $days[$key]['revenue'] += $amount;
                $days[$key]['orders']++;
            }
        }

        // Chiều cao cột biểu đồ tính theo phần trăm so với ngày cao nhất.
        $max = 0.0;
        foreach ($days as $d) {
            $max = max($max, $d['revenue']);
        }
        foreach ($days as $key => $d) {
            $days[$key]['percent'] = $max > 0 ? (int) round($d['revenue'] * 100 / $max) : 0;
            $days[$key]['is_max'] = $max > 0 && $d['revenue'] === $max;
        }

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $orders->count(),
            'cash_total' => $cashTotal,
            'qr_total' => $qrTotal,
            'days' => array_values($days),
        ];
    }

    /**
     * Danh sách tồn kho của sản phẩm đang bán; sản phẩm chạm định mức (current_stock <= safety_stock) xếp trước.
     */
    protected function buildInventory(string $filter)
    {
        $query = Inventory::with('product.category')
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            });

        if ($filter === 'low') {
            $query->whereColumn('current_stock', '<=', 'safety_stock');
        }

        return $query->get()
            ->sortBy(function ($inventory) {
                return [$inventory->isLowStock() ? 0 : 1, $inventory->current_stock];
            })
            ->values();
    }
}
