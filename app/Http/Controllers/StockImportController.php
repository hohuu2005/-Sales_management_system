<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockImport;
use App\Services\StockImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class StockImportController extends Controller
{
    /**
     * Hiển thị form lập phiếu nhập kèm danh sách sản phẩm.
     */
    public function create()
    {
        $products = Product::with('inventory')
            ->where('is_active', true)
            ->orderBy('sku_code')
            ->get();

        return view('stock_imports.create', ['products' => $products]);
    }

    /**
     * Kiểm tra đầu vào, gọi StockImportService, chuyển hướng kèm thông báo.
     */
    public function storeImport(Request $request, StockImportService $service)
    {
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.import_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.import_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'supplier_name.required' => 'Vui lòng nhập tên nhà cung cấp.',
            'items.required' => 'Chưa có sản phẩm nào để nhập kho.',
            'items.*.import_quantity.integer' => 'Số lượng nhập phải là số nguyên.',
            'items.*.import_quantity.min' => 'Số lượng nhập không được âm.',
            'items.*.import_price.numeric' => 'Đơn giá nhập phải là số.',
            'items.*.import_price.min' => 'Đơn giá nhập không được âm.',
        ]);

        // Form gửi lên mọi sản phẩm; chỉ giữ lại những dòng có số lượng nhập > 0.
        // Khóa của mảng items chính là product_id.
        $items = [];
        foreach ($request->input('items', []) as $productId => $row) {
            $quantity = (int) ($row['import_quantity'] ?? 0);

            if ($quantity > 0) {
                $items[] = [
                    'product_id' => (int) $productId,
                    'import_quantity' => $quantity,
                    'import_price' => (float) ($row['import_price'] ?? 0),
                ];
            }
        }

        if (count($items) === 0) {
            return back()
                ->withErrors(['items' => 'Hãy nhập số lượng lớn hơn 0 cho ít nhất một sản phẩm.'])
                ->withInput();
        }

        try {
            $stockImport = $service->createImport(
                Auth::id(),
                $data['supplier_name'],
                $data['note'] ?? null,
                $items,
            );
        } catch (Throwable $e) {
            return back()
                ->withErrors(['items' => 'Không lưu được phiếu nhập: ' . $e->getMessage()])
                ->withInput();
        }

        return redirect('/stock-imports')
            ->with('success', "Tạo phiếu nhập kho thành công. Mã phiếu: {$stockImport->import_code}");
    }

    /**
     * Lịch sử phiếu nhập, lọc theo khoảng ngày.
     */
    public function importHistory(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $query = StockImport::with(['stockkeeper', 'details.product'])->orderByDesc('id');

        if (! empty($data['start_date'])) {
            $query->whereDate('created_at', '>=', $data['start_date']);
        }

        if (! empty($data['end_date'])) {
            $query->whereDate('created_at', '<=', $data['end_date']);
        }

        return view('stock_imports.index', [
            'imports' => $query->limit(50)->get(),
            'start_date' => $data['start_date'] ?? '',
            'end_date' => $data['end_date'] ?? '',
        ]);
    }
}
