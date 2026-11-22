@extends('layouts.app')

@section('title', 'Lịch sử nhập kho')

@section('content')
    <div class="card">
        <div class="row-between card-title">
            <h3>Lịch sử phiếu nhập kho (50 phiếu gần nhất)</h3>
            <a class="btn btn-green" href="{{ url('/stock-imports/create') }}">+ Tạo phiếu nhập mới</a>
        </div>

        <form method="GET" action="{{ url('/stock-imports') }}" class="row-between mt" style="justify-content:flex-start;">
            <span>Từ ngày</span>
            <input class="input" style="width:180px; margin-top:0;" type="date" name="start_date" value="{{ $start_date }}">
            <span>đến ngày</span>
            <input class="input" style="width:180px; margin-top:0;" type="date" name="end_date" value="{{ $end_date }}">
            <button type="submit" class="btn btn-blue">Lọc</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Thời gian</th>
                    <th>Thủ kho</th>
                    <th>Nhà cung cấp</th>
                    <th>Chi tiết nhập</th>
                    <th class="num">Tổng tiền nhập</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($imports as $import)
                    <tr>
                        <td><b>{{ $import->import_code }}</b></td>
                        <td>{{ $import->created_at ? $import->created_at->format('d/m/Y H:i') : '' }}</td>
                        <td>{{ $import->stockkeeper ? $import->stockkeeper->full_name : '' }}</td>
                        <td>{{ $import->supplier_name }}</td>
                        <td>
                            @foreach ($import->details as $detail)
                                <div>
                                    {{ $detail->product ? $detail->product->name : 'Sản phẩm #' . $detail->product_id }}:
                                    {{ $detail->import_quantity }} x {{ number_format((float) $detail->import_price, 0, ',', '.') }} đ
                                </div>
                            @endforeach
                        </td>
                        <td class="num"><b>{{ number_format((float) $import->total_cost, 0, ',', '.') }} đ</b></td>
                        <td>{{ $import->note }}</td>
                    </tr>
                @empty
                    <tr class="empty-row"><td colspan="7">Chưa có phiếu nhập nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
