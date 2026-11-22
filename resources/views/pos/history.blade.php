@extends('layouts.app')

@section('title', 'Lịch sử giao dịch')

@section('content')
    <div class="card">
        <h3 class="card-title">Lịch sử hóa đơn của {{ auth()->user()->full_name }} (50 hóa đơn gần nhất)</h3>

        <table>
            <thead>
                <tr>
                    <th>Mã hóa đơn</th>
                    <th>Thời gian</th>
                    <th>Hàng hóa</th>
                    <th>Thanh toán</th>
                    <th class="num">Tổng tiền</th>
                    <th class="num">Khách đưa</th>
                    <th class="num">Tiền thừa</th>
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
                        <td class="num"><b>{{ number_format((float) $order->total_amount, 0, ',', '.') }} đ</b></td>
                        <td class="num">{{ number_format((float) $order->customer_paid, 0, ',', '.') }} đ</td>
                        <td class="num">{{ number_format((float) $order->change_amount, 0, ',', '.') }} đ</td>
                    </tr>
                @empty
                    <tr class="empty-row"><td colspan="7">Chưa có hóa đơn nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
