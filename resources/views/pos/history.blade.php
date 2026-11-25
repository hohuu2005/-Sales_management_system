@extends('layouts.app')

@section('title', 'Lịch sử giao dịch')

@section('content')
    <div class="card">
        <h3 class="card-title">Lịch sử hóa đơn của {{ auth()->user()->full_name }}</h3>

        <form method="GET" action="{{ url('/pos/history') }}" class="history-filter">
            <span>Từ ngày</span>
            <input class="input" type="date" name="start_date" value="{{ $start_date }}">
            <span>Đến ngày</span>
            <input class="input" type="date" name="end_date" value="{{ $end_date }}">
            <button type="submit" class="btn btn-blue">Lọc</button>
            <a class="btn btn-outline" href="{{ url('/pos/history') }}">Bỏ lọc</a>
        </form>

        <div class="stats history-summary">
            <div class="stat green">
                <small>SỐ HÓA ĐƠN HOÀN TẤT</small>
                <div>{{ number_format($total_orders, 0, ',', '.') }}</div>
            </div>
            <div class="stat">
                <small>TỔNG TIỀN HOÀN TẤT</small>
                <div>{{ number_format($total_completed_amount, 0, ',', '.') }} đ</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Mã hóa đơn</th>
                    <th>Thời gian</th>
                    <th>Hàng hóa</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái</th>
                    <th class="num">Tổng tiền</th>
                    <th class="num">Khách đưa</th>
                    <th class="num">Tiền thừa</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><b>{{ $order->order_code }}</b></td>
                        <td>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}</td>
                        <td>
                            @foreach ($order->details as $detail)
                                <div>{{ $detail->product ? $detail->product->name : 'Sản phẩm #' . $detail->product_id }} x {{ $detail->quantity }}</div>
                            @endforeach
                        </td>
                        <td>
                            @if ($order->payment_method === 'CASH')
                                <span class="badge badge-green">Tiền mặt</span>
                            @else
                                <span class="badge badge-blue">Chuyển khoản QR</span>
                            @endif
                        </td>
                        <td>
                            @if ($order->status === 'COMPLETED')
                                <span class="badge badge-green">Hoàn tất</span>
                            @else
                                <span class="badge badge-red">Đã hủy</span>
                            @endif
                        </td>
                        <td class="num"><b>{{ number_format((float) $order->total_amount, 0, ',', '.') }} đ</b></td>
                        <td class="num">{{ number_format((float) $order->customer_paid, 0, ',', '.') }} đ</td>
                        <td class="num">{{ number_format((float) $order->change_amount, 0, ',', '.') }} đ</td>
                        <td>
                            <a class="btn btn-outline btn-small" href="{{ url('/pos/history/' . $order->id) }}?start_date={{ urlencode($start_date) }}&end_date={{ urlencode($end_date) }}">Xem / In lại</a>
                        </td>
                    </tr>
                @empty
                    <tr class="empty-row"><td colspan="9">Chưa có hóa đơn nào.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($orders->hasPages() || $orders->currentPage() > 1)
            <div class="history-pagination">
                @if (! $orders->onFirstPage())
                    <a class="btn btn-outline btn-small" href="{{ $orders->previousPageUrl() }}">Trang trước</a>
                @endif
                <span>Trang {{ $orders->currentPage() }}</span>
                @if ($orders->hasMorePages())
                    <a class="btn btn-outline btn-small" href="{{ $orders->nextPageUrl() }}">Trang sau</a>
                @endif
            </div>
        @endif
    </div>
@endsection
