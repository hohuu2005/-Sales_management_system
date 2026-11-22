@extends('layouts.app')

@section('title', 'Lập phiếu nhập kho')

@section('content')
    <form method="POST" action="{{ url('/stock-imports') }}">
        @csrf

        <div class="container">
            {{-- Cột trái: danh sách sản phẩm, nhập số lượng cho những mặt hàng cần nhập --}}
            <div class="card col-main">
                <h3 class="card-title">Chi tiết mặt hàng nhập kho</h3>
                <p class="muted mt">Điền số lượng nhập cho những sản phẩm cần nhập. Dòng nào để trống hoặc bằng 0 sẽ được bỏ qua.</p>

                <table>
                    <thead>
                        <tr>
                            <th>Mã SKU</th>
                            <th>Tên hàng hóa</th>
                            <th>ĐVT</th>
                            <th class="num">Tồn hiện tại</th>
                            <th>Số lượng nhập</th>
                            <th>Đơn giá nhập (đ)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td><b>{{ $product->sku_code }}</b></td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->unit }}</td>
                                <td class="num">{{ $product->inventory ? $product->inventory->current_stock : 0 }}</td>
                                <td>
                                    <input class="input input-qty" type="number" min="0" step="1"
                                           name="items[{{ $product->id }}][import_quantity]"
                                           value="{{ old('items.' . $product->id . '.import_quantity') }}"
                                           placeholder="0">
                                </td>
                                <td>
                                    <input class="input input-qty" style="width:140px;" type="number" min="0" step="1"
                                           name="items[{{ $product->id }}][import_price]"
                                           value="{{ old('items.' . $product->id . '.import_price', (int) $product->cost_price) }}">
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="6">Chưa có sản phẩm nào đang kinh doanh.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Cột phải: thông tin phiếu nhập --}}
            <div class="card col-side">
                <h3 class="card-title">THÔNG TIN PHIẾU NHẬP</h3>

                <label>Người lập phiếu</label>
                <input class="input" type="text" value="{{ auth()->user()->full_name }} ({{ auth()->user()->employee_code }})" disabled>

                <label for="supplier_name">Nhà cung cấp *</label>
                <input class="input" id="supplier_name" name="supplier_name" type="text" maxlength="150"
                       value="{{ old('supplier_name') }}" required>

                <label for="note">Ghi chú</label>
                <input class="input" id="note" name="note" type="text" value="{{ old('note') }}">

                <button type="submit" class="btn btn-green btn-big">LƯU PHIẾU NHẬP</button>
            </div>
        </div>
    </form>
@endsection
