<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StockImportDetail;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class ProductController extends Controller
{
    /**
     * (ADMIN) Danh sách hàng hóa kèm tồn kho. Lọc theo danh mục (?category_id=), tìm theo mã hoặc tên (?q=),
     * mở form sửa bằng ?edit=<id>.
     */
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');

        return view('products.index', [
            'products' => $this->listProducts($keyword, $categoryId ? (int) $categoryId : null, false),
            'categories' => Category::withCount('products')->orderBy('name')->get(),
            'categoryId' => $categoryId ? (int) $categoryId : null,
            'editing' => $request->query('edit') ? Product::with('inventory')->find($request->query('edit')) : null,
            'title' => 'Hàng hóa & Danh mục',
            'keyword' => $keyword,
            'manage' => true,
        ]);
    }

    /**
     * (Mọi vai trò) Tra cứu sản phẩm đang bán theo mã SKU hoặc tên. Chỉ xem.
     */
    public function search(Request $request)
    {
        $keyword = trim((string) $request->query('sku_code', ''));

        return view('products.index', [
            'products' => $this->listProducts($keyword, null, true),
            'categories' => collect(),
            'categoryId' => null,
            'editing' => null,
            'title' => 'Tra cứu sản phẩm',
            'keyword' => $keyword,
            'manage' => false,
        ]);
    }

    /**
     * Thêm sản phẩm mới và khởi tạo tồn kho bằng 0 (ProductService, có transaction).
     */
    public function store(Request $request, ProductService $service)
    {
        $data = $request->validate($this->productRules(null), $this->messages());
        $data['attributes'] = $this->collectAttributes($data);

        try {
            $product = $service->createWithInventory($data);
        } catch (Throwable $e) {
            return back()->withErrors(['product' => 'Không lưu được sản phẩm: ' . $e->getMessage()])->withInput();
        }

        return $this->redirectWithPriceWarning($data, "Đã thêm sản phẩm {$product->sku_code} - {$product->name}.");
    }

    /**
     * Cập nhật thông tin sản phẩm, định mức tồn kho, vị trí kệ, trạng thái đang bán.
     */
    public function update(Request $request, ProductService $service, $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->validate($this->productRules($product->id), $this->messages());
        $data['attributes'] = $this->collectAttributes($data);
        $data['is_active'] = $request->boolean('is_active');

        try {
            $service->updateWithInventory($product, $data);
        } catch (Throwable $e) {
            return back()->withErrors(['product' => 'Không lưu được sản phẩm: ' . $e->getMessage()])->withInput();
        }

        return $this->redirectWithPriceWarning($data, "Đã cập nhật sản phẩm {$product->sku_code}.");
    }

    /**
     * Xóa sản phẩm. Nếu sản phẩm đã có trong hóa đơn hoặc phiếu nhập thì chỉ chuyển sang Ngừng bán
     * (is_active = false) để giữ nguyên lịch sử chứng từ.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $label = "{$product->sku_code} - {$product->name}";

        $result = $this->removeProduct($product);

        if ($result === 'deactivated') {
            return redirect('/products')->with('success', "Sản phẩm {$label} đã có trong chứng từ nên được chuyển sang Ngừng bán.");
        }

        return redirect('/products')->with('success', "Đã xóa sản phẩm {$label}.");
    }

    // ---------------------------------------------------------------------
    // Các hàm xử lý bên dưới không phụ thuộc vào Request nên dễ kiểm thử riêng.
    // ---------------------------------------------------------------------

    protected function listProducts(string $keyword, ?int $categoryId, bool $onlyActive)
    {
        $query = Product::with(['category', 'inventory'])->orderBy('sku_code');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('sku_code', $keyword)
                    ->orWhere('name', 'like', '%' . $keyword . '%');
            });
        }

        return $query->get();
    }

    protected function productRules(?int $ignoreId): array
    {
        return [
            'sku_code' => ['required', 'string', 'max:50', Rule::unique('products', 'sku_code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'unit' => ['required', 'string', 'max:30'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'gt:0'],
            'safety_stock' => ['required', 'integer', 'min:0'],
            'shelf_location' => ['nullable', 'string', 'max:50'],
            'attr_size' => ['nullable', 'string', 'max:30'],
            'attr_color' => ['nullable', 'string', 'max:30'],
            'attr_exp_date' => ['nullable', 'date', 'after:today'],
        ];
    }

    protected function messages(): array
    {
        return [
            'sku_code.required' => 'Vui lòng nhập mã SKU.',
            'sku_code.unique' => 'Mã SKU đã tồn tại trên hệ thống.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục không tồn tại.',
            'unit.required' => 'Vui lòng nhập đơn vị tính.',
            'cost_price.required' => 'Vui lòng nhập giá vốn.',
            'cost_price.min' => 'Giá vốn không được âm.',
            'selling_price.required' => 'Vui lòng nhập giá bán.',
            'selling_price.gt' => 'Giá bán phải lớn hơn 0.',
            'safety_stock.required' => 'Vui lòng nhập định mức tồn kho tối thiểu.',
            'safety_stock.min' => 'Định mức tồn kho không được âm.',
            'attr_exp_date.after' => 'Hạn sử dụng phải sau ngày hôm nay.',
        ];
    }

    /**
     * Gom các thuộc tính động (kích cỡ, màu sắc, hạn dùng) thành mảng để lưu vào cột attributes_json.
     * Thuộc tính nào để trống thì không lưu.
     */
    protected function collectAttributes(array $data): array
    {
        $attributes = [];

        if (! empty($data['attr_size'])) {
            $attributes['size'] = $data['attr_size'];
        }

        if (! empty($data['attr_color'])) {
            $attributes['color'] = $data['attr_color'];
        }

        if (! empty($data['attr_exp_date'])) {
            $attributes['exp_date'] = $data['attr_exp_date'];
        }

        return $attributes;
    }

    /**
     * Trả về 'deactivated' nếu sản phẩm đã có chứng từ (chỉ ngừng bán), 'deleted' nếu xóa hẳn được.
     */
    protected function removeProduct(Product $product): string
    {
        $hasHistory = OrderDetail::where('product_id', $product->id)->exists()
            || StockImportDetail::where('product_id', $product->id)->exists();

        if ($hasHistory) {
            $product->update(['is_active' => false]);

            return 'deactivated';
        }

        // Dòng tồn kho tự xóa theo nhờ khóa ngoại ON DELETE CASCADE của bảng inventories.
        $product->delete();

        return 'deleted';
    }

    /**
     * Lưu xong thì quay về danh sách. Nếu giá bán thấp hơn giá vốn thì kèm cảnh báo để Admin kiểm tra lại.
     */
    protected function redirectWithPriceWarning(array $data, string $message)
    {
        $redirect = redirect('/products')->with('success', $message);

        if ((float) $data['selling_price'] < (float) $data['cost_price']) {
            $redirect->with('warning', 'Cảnh báo: giá bán đang thấp hơn giá vốn. Hãy kiểm tra lại.');
        }

        return $redirect;
    }
}
