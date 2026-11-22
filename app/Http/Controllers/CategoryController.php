<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Danh sách danh mục kèm số sản phẩm. Thêm ?edit=<id> vào địa chỉ để mở form sửa.
     */
    public function index(Request $request)
    {
        $editing = $request->query('edit') ? Category::find($request->query('edit')) : null;

        return view('categories.index', [
            'categories' => Category::withCount('products')->orderBy('name')->get(),
            'editing' => $editing,
        ]);
    }

    /**
     * Thêm danh mục, tự sinh slug từ tên và ghi lại người tạo.
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->categoryRules(null), $this->messages());

        $category = $this->createCategory($data, (int) Auth::id());

        return redirect('/categories')->with('success', "Đã thêm danh mục {$category->name}.");
    }

    /**
     * Sửa tên và mô tả danh mục.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $data = $request->validate($this->categoryRules($category->id), $this->messages());

        $this->updateCategory($category, $data);

        return redirect('/categories')->with('success', "Đã cập nhật danh mục {$category->name}.");
    }

    /**
     * Xóa danh mục; chặn nếu còn sản phẩm thuộc danh mục.
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $name = $category->name;

        $productCount = $this->deleteCategory($category);

        if ($productCount > 0) {
            return back()->withErrors([
                'category' => "Không thể xóa danh mục \"{$name}\" vì đang có {$productCount} sản phẩm. Hãy chuyển các sản phẩm sang danh mục khác trước.",
            ]);
        }

        return redirect('/categories')->with('success', "Đã xóa danh mục {$name}.");
    }

    // ---------------------------------------------------------------------
    // Các hàm xử lý bên dưới không phụ thuộc vào Request nên dễ kiểm thử riêng.
    // ---------------------------------------------------------------------

    protected function categoryRules(?int $ignoreId): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.unique' => 'Tên danh mục đã tồn tại.',
            'name.max' => 'Tên danh mục tối đa 100 ký tự.',
        ];
    }

    /**
     * Sinh slug không trùng: "Thời trang" -> "thoi-trang"; nếu đã có thì thêm -2, -3...
     */
    protected function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'danh-muc';
        $slug = $base;
        $n = 2;

        while (Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $n;
            $n++;
        }

        return $slug;
    }

    protected function createCategory(array $data, int $createdBy): Category
    {
        return Category::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'created_by' => $createdBy,
        ]);
    }

    protected function updateCategory(Category $category, array $data): Category
    {
        $category->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $category->id),
            'description' => $data['description'] ?? null,
        ]);

        return $category;
    }

    /**
     * Trả về số sản phẩm đang thuộc danh mục. Bằng 0 nghĩa là đã xóa; lớn hơn 0 nghĩa là bị chặn.
     */
    protected function deleteCategory(Category $category): int
    {
        $count = $category->products()->count();

        if ($count === 0) {
            $category->delete();
        }

        return $count;
    }
}
