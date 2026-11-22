@extends('layouts.app')

@section('title', 'Báo cáo doanh thu')

@section('content')
    <div class="card">
        <form method="GET" action="{{ url('/reports/revenue') }}" class="row-between" style="justify-content:flex-start;">
            <b>Kỳ thống kê:</b>
            <span>Từ ngày</span>
            <input class="input" style="width:180px; margin-top:0;" type="date" name="start_date" value="{{ $start_date }}">
            <span>đến ngày</span>
            <input class="input" style="width:180px; margin-top:0;" type="date" name="end_date" value="{{ $end_date }}">
            <button type="submit" class="btn btn-blue">Thống kê</button>
        </form>
    </div>

    <div class="stats">
        <div class="stat">
            <small>TỔNG DOANH THU TRONG KỲ</small>
            <div>{{ number_format($total_revenue, 0, ',', '.') }} đ</div>
        </div>
        <div class="stat green">
            <small>SỐ HÓA ĐƠN HOÀN TẤT</small>
            <div>{{ $total_orders }} hóa đơn</div>
        </div>
        <div class="stat red">
            <small>SẢN PHẨM CẦN NHẬP THÊM</small>
            <div>{{ $low_stock_count }} mặt hàng</div>
        </div>
    </div>

    <div class="container" style="margin-top:20px;">
        <div class="card col-main">
            <h3 class="card-title">Doanh thu theo ngày</h3>

            @if ($total_orders === 0)
                <p class="muted mt">Không có hóa đơn nào trong kỳ này.</p>
            @else
                {{-- Biểu đồ cột vẽ bằng CSS: chiều cao mỗi cột = phần trăm so với ngày cao nhất --}}
                <div class="bars">
                    @foreach ($days as $day)
                        <div class="bar-col">
                            @if ($day['revenue'] > 0)
                                <span>{{ number_format($day['revenue'] / 1000, 0, ',', '.') }}k</span>
                            @endif
                            <div class="bar {{ $day['is_max'] ? 'top' : '' }}" style="height: {{ $day['percent'] }}%;"></div>
                        </div>
                    @endforeach
                </div>
                <div class="bar-days">
                    @foreach ($days as $day)
                        <span>{{ $day['label'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card col-side">
            <h3 class="card-title">Theo phương thức thanh toán</h3>
            <div class="summary-line"><span>Tiền mặt:</span><b>{{ number_format($cash_total, 0, ',', '.') }} đ</b></div>
            <div class="summary-line"><span>Chuyển khoản QR:</span><b>{{ number_format($qr_total, 0, ',', '.') }} đ</b></div>
            <div class="summary-line summary-total"><span>Tổng cộng:</span><b>{{ number_format($total_revenue, 0, ',', '.') }} đ</b></div>

            <a class="btn btn-outline" style="margin-top:20px; width:100%;" href="{{ url('/reports/inventory') }}">Xem cảnh báo tồn kho</a>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3 class="card-title">Chi tiết từng ngày</h3>
        <table>
            <thead>
                <tr><th>Ngày</th><th class="num">Số hóa đơn</th><th class="num">Doanh thu</th></tr>
            </thead>
            <tbody>
                @foreach ($days as $day)
                    <tr>
                        <td>{{ $day['label'] }}</td>
                        <td class="num">{{ $day['orders'] }}</td>
                        <td class="num"><b>{{ number_format($day['revenue'], 0, ',', '.') }} đ</b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
