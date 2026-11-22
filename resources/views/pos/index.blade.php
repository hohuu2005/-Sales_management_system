@extends('layouts.app')

@section('title', 'Bán hàng POS')

@section('content')
    {{-- Hai đường dẫn AJAX được truyền cho file public/js/pos.js qua thuộc tính data-* --}}
    <div id="pos-app"
         data-scan-url="{{ url('/pos/scan') }}"
         data-checkout-url="{{ url('/pos/checkout') }}">

        <div id="pos-message" class="alert alert-error hidden no-print"></div>

        {{-- Hóa đơn: chỉ hiện sau khi thanh toán thành công --}}
        <div id="receipt" class="card receipt hidden" style="margin-bottom:20px;">
            <div class="row-between">
                <h3>THANH TOÁN THÀNH CÔNG - HÓA ĐƠN <span id="receipt-code"></span></h3>
                <div class="no-print">
                    <button type="button" class="btn btn-outline" id="btn-print">In hóa đơn</button>
                    <button type="button" class="btn btn-green" id="btn-new-order">Hóa đơn mới</button>
                </div>
            </div>
            <div class="receipt-lines">
                <div>Thu ngân: <b id="receipt-cashier"></b> | Giờ tạo: <b id="receipt-time"></b> | Thanh toán: <b id="receipt-method"></b></div>
                <table>
                    <thead>
                        <tr><th>Mã SKU</th><th>Tên hàng hóa</th><th class="num">Số lượng</th><th class="num">Đơn giá</th><th class="num">Thành tiền</th></tr>
                    </thead>
                    <tbody id="receipt-body"></tbody>
                </table>
                <div class="summary-line"><span>Tổng cộng:</span><b id="receipt-total"></b></div>
                <div class="summary-line"><span>Khách đưa:</span><b id="receipt-paid"></b></div>
                <div class="summary-line"><span>Tiền thừa:</span><b id="receipt-change"></b></div>
            </div>
        </div>

        <div class="container no-print">
            {{-- Cột trái: quét mã và giỏ hàng --}}
            <div class="card col-main">
                <div class="pos-scan">
                    <input class="input" id="sku-input" type="text" autocomplete="off" autofocus
                           placeholder="Quét mã vạch hoặc nhập mã SKU rồi nhấn Enter (ví dụ: SP001)">
                    <button type="button" class="btn btn-green" id="btn-scan">+ Thêm</button>
                </div>

                <h3 class="mt">Danh sách hàng hóa trong giỏ</h3>
                <table>
                    <thead>
                        <tr>
                            <th>STT</th><th>Mã SKU</th><th>Tên hàng hóa</th><th>ĐVT</th>
                            <th class="num">Tồn kho</th><th>Số lượng</th>
                            <th class="num">Đơn giá</th><th class="num">Thành tiền</th><th></th>
                        </tr>
                    </thead>
                    <tbody id="cart-body">
                        <tr class="empty-row"><td colspan="9">Giỏ hàng đang trống. Hãy quét mã sản phẩm.</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Cột phải: thanh toán --}}
            <div class="card col-side">
                <h3 class="card-title">THÔNG TIN HÓA ĐƠN</h3>

                <div class="summary-line summary-total">
                    <span>Khách cần trả:</span><b id="total-amount">0 đ</b>
                </div>

                <label>Phương thức thanh toán</label>
                <div class="radio-row">
                    <label><input type="radio" name="payment_method" value="CASH" checked> Tiền mặt</label>
                    <label><input type="radio" name="payment_method" value="VIETQR"> Chuyển khoản QR</label>
                </div>

                <div id="cash-box">
                    <label for="customer-paid">Tiền khách đưa</label>
                    <input class="input input-money" id="customer-paid" type="number" min="0" step="1000" placeholder="0">
                    <div class="change-box">
                        <span>Tiền thừa trả khách:</span><b id="change-amount">0 đ</b>
                    </div>
                </div>

                <div id="qr-box" class="qr-box hidden">
                    <div class="qr-placeholder">MÃ QR (minh họa)</div>
                    <div>Số tiền chuyển khoản: <b id="qr-amount">0 đ</b></div>
                    <div class="muted">Kiểm tra ứng dụng ngân hàng đã nhận tiền rồi mới bấm Xác nhận.</div>
                </div>

                <button type="button" class="btn btn-green btn-big" id="btn-checkout">XÁC NHẬN THANH TOÁN (F9)</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/pos.js') }}"></script>
@endsection
