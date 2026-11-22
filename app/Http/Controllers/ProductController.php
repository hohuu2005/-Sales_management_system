<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * (ADMIN) Hiển thị danh sách sản phẩm kèm tồn kho.
     */
    public function index()
    {
        $products = Product::with(['category', 'inventory'])
            ->orderBy('sku_code')
            ->get();

        return view('products.index', [
            'products' => $products,
            'title' => 'Danh sách hàng hóa & tồn kho',
            'keyword' => null,
        ]);
    }

    /**
     * (Mọi vai trò) Tra cứu sản phẩm theo mã SKU hoặc tên.
     */
    public function search(Request $request)
    {
        $keyword = trim((string) $request->query('sku_code', ''));

        $query = Product::with(['category', 'inventory'])
            ->where('is_active', true)
            ->orderBy('sku_code');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('sku_code', $keyword)
                    ->orWhere('name', 'like', '%' . $keyword . '%');
            });
        }

        return view('products.index', [
            'products' => $query->get(),
            'title' => 'Tra cứu sản phẩm',
            'keyword' => $keyword,
        ]);
    }
}
