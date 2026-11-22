/*
 * Màn hình bán hàng POS.
 * Giỏ hàng được giữ tạm trong trình duyệt (biến cart). Chỉ khi bấm "Xác nhận thanh toán"
 * thì mới gửi lên máy chủ (POST /pos/checkout) để lưu hóa đơn và trừ tồn kho.
 */
(function () {
    'use strict';

    var app = document.getElementById('pos-app');
    if (!app) {
        return;
    }

    var scanUrl = app.getAttribute('data-scan-url');
    var checkoutUrl = app.getAttribute('data-checkout-url');
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var skuInput = document.getElementById('sku-input');
    var cartBody = document.getElementById('cart-body');
    var messageBox = document.getElementById('pos-message');
    var totalEl = document.getElementById('total-amount');
    var paidInput = document.getElementById('customer-paid');
    var changeEl = document.getElementById('change-amount');
    var cashBox = document.getElementById('cash-box');
    var qrBox = document.getElementById('qr-box');
    var qrAmountEl = document.getElementById('qr-amount');
    var checkoutBtn = document.getElementById('btn-checkout');
    var receipt = document.getElementById('receipt');

    // Mỗi phần tử: { sku_code, name, unit, price, stock, quantity }
    var cart = [];

    function formatMoney(value) {
        return Math.round(value).toLocaleString('vi-VN') + ' đ';
    }

    function showMessage(text) {
        messageBox.textContent = text;
        messageBox.classList.remove('hidden');
    }

    function clearMessage() {
        messageBox.textContent = '';
        messageBox.classList.add('hidden');
    }

    function paymentMethod() {
        return document.querySelector('input[name="payment_method"]:checked').value;
    }

    function totalAmount() {
        var total = 0;
        for (var i = 0; i < cart.length; i++) {
            total += cart[i].price * cart[i].quantity;
        }
        return total;
    }

    function cell(text, className) {
        var td = document.createElement('td');
        td.textContent = text;
        if (className) {
            td.className = className;
        }
        return td;
    }

    // Vẽ lại bảng giỏ hàng và phần tổng tiền.
    function render() {
        cartBody.innerHTML = '';

        if (cart.length === 0) {
            var emptyRow = document.createElement('tr');
            emptyRow.className = 'empty-row';
            var emptyCell = cell('Giỏ hàng đang trống. Hãy quét mã sản phẩm.');
            emptyCell.colSpan = 9;
            emptyRow.appendChild(emptyCell);
            cartBody.appendChild(emptyRow);
        }

        cart.forEach(function (item, index) {
            var tr = document.createElement('tr');
            tr.appendChild(cell(String(index + 1)));
            tr.appendChild(cell(item.sku_code));
            tr.appendChild(cell(item.name));
            tr.appendChild(cell(item.unit));
            tr.appendChild(cell(String(item.stock), 'num'));

            var qtyCell = document.createElement('td');
            var qtyInput = document.createElement('input');
            qtyInput.type = 'number';
            qtyInput.min = '1';
            qtyInput.max = String(item.stock);
            qtyInput.value = String(item.quantity);
            qtyInput.className = 'input input-qty';
            qtyInput.addEventListener('change', function () {
                changeQuantity(index, qtyInput.value);
            });
            qtyCell.appendChild(qtyInput);
            tr.appendChild(qtyCell);

            tr.appendChild(cell(formatMoney(item.price), 'num'));
            tr.appendChild(cell(formatMoney(item.price * item.quantity), 'num'));

            var removeCell = document.createElement('td');
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-red btn-small';
            removeBtn.textContent = 'Xóa';
            removeBtn.addEventListener('click', function () {
                cart.splice(index, 1);
                clearMessage();
                render();
                skuInput.focus();
            });
            removeCell.appendChild(removeBtn);
            tr.appendChild(removeCell);

            cartBody.appendChild(tr);
        });

        renderSummary();
    }

    // Tính lại tổng tiền, tiền thừa, hiện/ẩn khung QR.
    function renderSummary() {
        var total = totalAmount();
        totalEl.textContent = formatMoney(total);
        qrAmountEl.textContent = formatMoney(total);

        if (paymentMethod() === 'VIETQR') {
            cashBox.classList.add('hidden');
            qrBox.classList.remove('hidden');
            return;
        }

        cashBox.classList.remove('hidden');
        qrBox.classList.add('hidden');

        var paid = parseFloat(paidInput.value) || 0;
        if (paid >= total) {
            changeEl.textContent = formatMoney(paid - total);
        } else {
            changeEl.textContent = 'Chưa đủ';
        }
    }

    // Nhân viên gõ tay số lượng trong giỏ.
    function changeQuantity(index, rawValue) {
        var item = cart[index];
        var quantity = parseInt(rawValue, 10);

        clearMessage();

        if (isNaN(quantity) || quantity < 1) {
            quantity = 1;
        }

        if (quantity > item.stock) {
            // Vượt tồn kho: báo lỗi và lùi về mức tồn kho tối đa cho phép.
            quantity = item.stock;
            showMessage('Sản phẩm không đủ số lượng trong kho, không thể cập nhật số lượng. Tồn kho hiện tại: ' + item.stock + '.');
        }

        item.quantity = quantity;
        render();
    }

    // Đọc JSON trả về; nếu máy chủ không trả JSON thì tạo thông báo lỗi chung.
    function readJson(response) {
        return response.json().then(function (data) {
            return { ok: response.ok, status: response.status, data: data };
        }).catch(function () {
            return { ok: false, status: response.status, data: { message: 'Lỗi máy chủ (mã ' + response.status + ').' } };
        });
    }

    // Quét mã / gõ mã SKU: hỏi máy chủ thông tin sản phẩm rồi thêm vào giỏ.
    function addBySku() {
        var sku = skuInput.value.trim();
        if (sku === '') {
            return;
        }

        clearMessage();
        receipt.classList.add('hidden');

        fetch(scanUrl + '?sku_code=' + encodeURIComponent(sku), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(readJson)
            .then(function (result) {
                if (!result.ok) {
                    showMessage(result.data.message || 'Không tìm thấy sản phẩm.');
                    return;
                }

                var product = result.data;
                var existing = null;
                for (var i = 0; i < cart.length; i++) {
                    if (cart[i].sku_code === product.sku_code) {
                        existing = cart[i];
                    }
                }

                var wanted = existing ? existing.quantity + 1 : 1;
                if (wanted > product.current_stock) {
                    showMessage('Sản phẩm không đủ số lượng trong kho, không thể thêm vào giỏ. Tồn kho hiện tại: ' + product.current_stock + '.');
                    if (existing) {
                        existing.stock = product.current_stock;
                    }
                    render();
                    return;
                }

                if (existing) {
                    // Quét lại cùng mã: cộng dồn số lượng +1.
                    existing.quantity = wanted;
                    existing.stock = product.current_stock;
                    existing.price = product.selling_price;
                } else {
                    cart.push({
                        sku_code: product.sku_code,
                        name: product.name,
                        unit: product.unit,
                        price: product.selling_price,
                        stock: product.current_stock,
                        quantity: 1
                    });
                }

                render();
            })
            .catch(function () {
                showMessage('Không kết nối được máy chủ.');
            })
            .then(function () {
                skuInput.value = '';
                skuInput.focus();
            });
    }

    function showReceipt(order, lines) {
        document.getElementById('receipt-code').textContent = order.order_code;
        document.getElementById('receipt-cashier').textContent = order.cashier;
        document.getElementById('receipt-time').textContent = order.created_at;
        document.getElementById('receipt-method').textContent = order.payment_method === 'CASH' ? 'Tiền mặt' : 'Chuyển khoản QR';
        document.getElementById('receipt-total').textContent = formatMoney(order.total_amount);
        document.getElementById('receipt-paid').textContent = formatMoney(order.customer_paid);
        document.getElementById('receipt-change').textContent = formatMoney(order.change_amount);

        var body = document.getElementById('receipt-body');
        body.innerHTML = '';
        lines.forEach(function (item) {
            var tr = document.createElement('tr');
            tr.appendChild(cell(item.sku_code));
            tr.appendChild(cell(item.name));
            tr.appendChild(cell(String(item.quantity), 'num'));
            tr.appendChild(cell(formatMoney(item.price), 'num'));
            tr.appendChild(cell(formatMoney(item.price * item.quantity), 'num'));
            body.appendChild(tr);
        });

        receipt.classList.remove('hidden');
    }

    // Gửi giỏ hàng lên máy chủ để lưu hóa đơn và trừ tồn kho.
    function checkout() {
        clearMessage();

        if (cart.length === 0) {
            showMessage('Giỏ hàng đang trống.');
            return;
        }

        var method = paymentMethod();
        var total = totalAmount();
        var paid = method === 'VIETQR' ? total : (parseFloat(paidInput.value) || 0);

        if (method === 'CASH' && paid < total) {
            showMessage('Tiền khách đưa chưa đủ.');
            paidInput.focus();
            return;
        }

        var items = cart.map(function (item) {
            return { sku_code: item.sku_code, quantity: item.quantity };
        });

        checkoutBtn.disabled = true;

        fetch(checkoutUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({ payment_method: method, customer_paid: paid, items: items })
        })
            .then(readJson)
            .then(function (result) {
                if (!result.ok) {
                    // Lỗi (ví dụ hết hàng): báo lỗi và GIỮ NGUYÊN giỏ hàng.
                    showMessage(result.data.message || 'Thanh toán không thành công.');
                    return;
                }

                showReceipt(result.data.order, cart.slice());

                // Làm mới màn hình để đón khách tiếp theo.
                cart = [];
                paidInput.value = '';
                render();
            })
            .catch(function () {
                showMessage('Không kết nối được máy chủ.');
            })
            .then(function () {
                checkoutBtn.disabled = false;
                skuInput.focus();
            });
    }

    // ----- Gắn sự kiện -----
    document.getElementById('btn-scan').addEventListener('click', addBySku);

    skuInput.addEventListener('keydown', function (event) {
        // Máy quét mã vạch tự gửi phím Enter sau khi điền mã.
        if (event.key === 'Enter') {
            event.preventDefault();
            addBySku();
        }
    });

    paidInput.addEventListener('input', function () {
        clearMessage();
        renderSummary();
    });

    var radios = document.querySelectorAll('input[name="payment_method"]');
    for (var r = 0; r < radios.length; r++) {
        radios[r].addEventListener('change', renderSummary);
    }

    checkoutBtn.addEventListener('click', checkout);

    document.getElementById('btn-print').addEventListener('click', function () {
        window.print();
    });

    document.getElementById('btn-new-order').addEventListener('click', function () {
        receipt.classList.add('hidden');
        skuInput.focus();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'F9') {
            event.preventDefault();
            checkout();
        }
    });

    render();
})();
