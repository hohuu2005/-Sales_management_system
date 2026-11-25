@extends('layouts.app')

@section('title', 'Xem lại hóa đơn')

@section('content')
    <div id="receipt" class="card receipt">
        <div class="row-between">
            <h3>BẢN IN LẠI - HÓA ĐƠN {{ $order->order_code }}</h3>
            <div class="no-print">
                <button type="button" class="btn btn-outline" onclick="window.print()">In</button>
                <a class="btn btn-green" href="{{ url('/pos/history') }}?start_date={{ urlencode($start_date) }}&end_date={{ urlencode($end_date) }}">Quay lại lịch sử</a>
            </div>
        </div>
        <div class="receipt-lines">
            <div>Thu ngân: <b>{{ $order->cashier ? $order->cashier->full_name : '' }}</b> | Giờ tạo: <b>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}</b> | Thanh toán: <b>{{ $order->payment_method === 'CASH' ? 'Tiền mặt' : 'Chuyển khoản QR' }}</b></div>
            <table>
                <thead>
                    <tr><th>Mã SKU</th><th>Tên hàng hóa</th><th class="num">Số lượng</th><th class="num">Đơn giá</th><th class="num">Thành tiền</th></tr>
                </thead>
                <tbody>
                    @foreach ($order->details as $detail)
                        <tr>
                            <td>{{ $detail->product ? $detail->product->sku_code : '' }}</td>
                            <td>{{ $detail->product ? $detail->product->name : 'Sản phẩm #' . $detail->product_id }}</td>
                            <td class="num">{{ $detail->quantity }}</td>
                            <td class="num">{{ number_format((float) $detail->unit_price, 0, ',', '.') }} đ</td>
                            <td class="num">{{ number_format((float) $detail->subtotal, 0, ',', '.') }} đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="summary-line"><span>Tổng cộng:</span><b>{{ number_format((float) $order->total_amount, 0, ',', '.') }} đ</b></div>
            <div class="summary-line"><span>Khách đưa:</span><b>{{ number_format((float) $order->customer_paid, 0, ',', '.') }} đ</b></div>
            <div class="summary-line"><span>Tiền thừa:</span><b>{{ number_format((float) $order->change_amount, 0, ',', '.') }} đ</b></div>
        </div>
    </div>
@endsection
