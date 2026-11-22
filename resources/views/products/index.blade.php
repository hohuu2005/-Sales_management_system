@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="card">
        <h3 class="card-title">{{ $title }}</h3>

        {{-- Ô tìm kiếm chỉ hiện ở màn hình Tra cứu sản phẩm --}}
        @if (! is_null($keyword))
            <form method="GET" action="{{ url('/products/search') }}" class="pos-scan mt">
                <input class="input" type="text" name="sku_code" value="{{ $keyword }}" autofocus
                       placeholder="Nhập mã SKU hoặc tên sản phẩm">
                <button type="submit" class="btn btn-blue">Tìm</button>
            </form>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Mã SKU</th>
                    <th>Tên hàng hóa</th>
                    <th>Danh mục</th>
                    <th>ĐVT</th>
                    <th class="num">Giá bán</th>
                    <th class="num">Tồn kho</th>
                    <th>Vị trí kệ</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td><b>{{ $product->sku_code }}</b></td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category ? $product->category->name : '' }}</td>
                        <td>{{ $product->unit }}</td>
                        <td class="num">{{ number_format((float) $product->selling_price, 0, ',', '.') }} đ</td>
                        <td class="num">
                            @if (! $product->inventory)
                                <span class="badge badge-red">0</span>
                            @elseif ($product->inventory->isLowStock())
                                <span class="badge badge-red">{{ $product->inventory->current_stock }} (sắp hết)</span>
                            @else
                                <span class="badge badge-green">{{ $product->inventory->current_stock }}</span>
                            @endif
                        </td>
                        <td>{{ $product->inventory ? $product->inventory->shelf_location : '' }}</td>
                        <td>
                            @if ($product->is_active)
                                <span class="badge badge-blue">Đang bán</span>
                            @else
                                <span class="badge badge-red">Ngừng bán</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="empty-row"><td colspan="8">Không tìm thấy sản phẩm nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
