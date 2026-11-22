@extends('layouts.app')

@section('title', 'Cảnh báo tồn kho')

@section('content')
    <div class="card">
        <div class="row-between card-title">
            <h3>Báo cáo tồn kho ({{ $low_stock_count }} mặt hàng cần nhập thêm)</h3>
            @if (auth()->user()->role === 'ADMIN')
                <a class="btn btn-outline" href="{{ url('/reports/revenue') }}">Quay lại Báo cáo doanh thu</a>
            @endif
        </div>

        <p class="muted mt">Sản phẩm có tồn kho nhỏ hơn hoặc bằng định mức tối thiểu được xếp lên đầu danh sách.</p>

        <div class="tabs">
            <a class="{{ $filter_type === 'all' ? 'active' : '' }}" href="{{ url('/reports/inventory') }}">Tất cả sản phẩm</a>
            <a class="{{ $filter_type === 'low' ? 'active' : '' }}" href="{{ url('/reports/inventory') }}?filter_type=low">Chỉ sản phẩm cần nhập thêm</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Mã SKU</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th class="num">Tồn hiện tại</th>
                    <th class="num">Định mức tối thiểu</th>
                    <th>Vị trí kệ</th>
                    <th>Trạng thái</th>
                    @if (auth()->user()->role === 'STOCKKEEPER')
                        <th>Thao tác</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="{{ $row->isLowStock() ? 'row-low' : '' }}">
                        <td><b>{{ $row->product->sku_code }}</b></td>
                        <td>{{ $row->product->name }}</td>
                        <td>{{ $row->product->category ? $row->product->category->name : '' }}</td>
                        <td class="num"><b>{{ $row->current_stock }} {{ $row->product->unit }}</b></td>
                        <td class="num">{{ $row->safety_stock }} {{ $row->product->unit }}</td>
                        <td>{{ $row->shelf_location }}</td>
                        <td>
                            @if ($row->isLowStock())
                                <span class="badge badge-red">Cần nhập thêm</span>
                            @else
                                <span class="badge badge-green">Đủ hàng</span>
                            @endif
                        </td>
                        @if (auth()->user()->role === 'STOCKKEEPER')
                            <td>
                                @if ($row->isLowStock())
                                    <a class="btn btn-blue btn-small" href="{{ url('/stock-imports/create') }}">+ Nhập bổ sung</a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr class="empty-row"><td colspan="8">Không có sản phẩm nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
