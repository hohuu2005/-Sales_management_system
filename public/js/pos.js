/*
 * JavaScript điều khiển giỏ hàng, quét SKU, kiểm tra số lượng và gửi thanh toán POS.
 * Trang pos/index.blade.php cung cấp DOM; script gọi PosOrderController qua GET /pos/scan và POST /pos/checkout.
 * Luồng giao diện và phím tắt được mô tả tại docs/02-luong-ban-hang-pos.md.
 */
(function () {
    'use strict';

    var app = document.getElementById('pos-app');
    if (!app) {
        return;
    }

    var scanUrl = app.getAttribute('data-scan-url');
    var searchUrl = app.getAttribute('data-search-url');
    var checkoutUrl = app.getAttribute('data-checkout-url');
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var skuInput = document.getElementById('sku-input');
    var cartBody = document.getElementById('cart-body');
    var messageBox = document.getElementById('pos-message');
    var totalEl = document.getElementById('total-amount');
    var paidInput = document.getElementById('customer-paid');
    var changeEl = document.getElementById('change-amount');
    var cashSuggestionsBox = document.getElementById('cash-suggestions');
    var cashBox = document.getElementById('cash-box');
    var qrBox = document.getElementById('qr-box');
    var qrAmountEl = document.getElementById('qr-amount');
    var checkoutBtn = document.getElementById('btn-checkout');
    var receipt = document.getElementById('receipt');
    var chipsBox = document.getElementById('cash-chips');
    var cartCount = document.getElementById('cart-count');

    // Mỗi phần tử: { sku_code, name, unit, price, stock, quantity }
    var cart = [];
    var submitting = false;
    var suggestions = [];
    var activeSuggestion = -1;
    var searchTimer = null;
    var latestSearchId = 0;

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

            // Ô số lượng: nút trừ, ô gõ tay, nút cộng
            var qtyCell = document.createElement('td');
            var qtyWrap = document.createElement('div');
            qtyWrap.className = 'qty';

            var minusBtn = document.createElement('button');
            minusBtn.type = 'button';
            minusBtn.textContent = '−';
            minusBtn.addEventListener('click', function () {
                changeQuantity(index, item.quantity - 1);
            });

            var qtyInput = document.createElement('input');
            qtyInput.type = 'number';
            qtyInput.min = '1';
            qtyInput.max = String(item.stock);
            qtyInput.value = String(item.quantity);
            qtyInput.className = 'input input-qty';
            qtyInput.addEventListener('change', function () {
                changeQuantity(index, qtyInput.value);
            });

            var plusBtn = document.createElement('button');
            plusBtn.type = 'button';
            plusBtn.textContent = '+';
            plusBtn.addEventListener('click', function () {
                changeQuantity(index, item.quantity + 1);
            });

            qtyWrap.appendChild(minusBtn);
            qtyWrap.appendChild(qtyInput);
            qtyWrap.appendChild(plusBtn);
            qtyCell.appendChild(qtyWrap);
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

        var pieces = 0;
        for (var i = 0; i < cart.length; i++) {
            pieces += cart[i].quantity;
        }
        cartCount.textContent = cart.length === 0 ? '(chưa có sản phẩm)' : '(' + cart.length + ' mặt hàng - ' + pieces + ' sản phẩm)';

        renderSummary();
    }

    // Gợi ý tiền vừa đủ, mốc làm tròn dễ đưa và tiền thối thuận tiện.
    function renderChips(total) {
        chipsBox.innerHTML = '';
        if (total <= 0) {
            cashSuggestionsBox.classList.add('hidden');
            return;
        }

        cashSuggestionsBox.classList.remove('hidden');
        var exactAmount = Math.ceil(total);
        var roundingSteps = [5000, 10000, 20000, 50000, 100000, 200000, 500000];
        var candidates = [exactAmount];

        roundingSteps.forEach(function (step) {
            var roundedAmount = Math.ceil(exactAmount / step) * step;
            if (roundedAmount > exactAmount) {
                candidates.push(roundedAmount);
            }
        });

        var nextHalfMillion = Math.ceil(exactAmount / 500000) * 500000;
        if (nextHalfMillion <= exactAmount) {
            nextHalfMillion += 500000;
        }
        candidates.push(nextHalfMillion);

        Array.from(new Set(candidates))
            .filter(function (value) {
                return value >= exactAmount;
            })
            .sort(function (a, b) {
                return a - b;
            })
            .slice(0, 8)
            .forEach(function (value) {
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.textContent = formatMoney(value);
                chip.addEventListener('click', function () {
                    paidInput.value = String(value);
                    clearMessage();
                    renderSummary();
                });
                chipsBox.appendChild(chip);
            });
    }

    // Tính lại tổng tiền, tiền thừa, hiện/ẩn khung QR.
    function renderSummary() {
        var total = totalAmount();
        totalEl.textContent = formatMoney(total);
        qrAmountEl.textContent = formatMoney(total);
        renderChips(total);

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

        clearMessage();

        var raw = String(rawValue).trim();
        if (!/^\d+$/.test(raw)) {
            showMessage('Số lượng phải là số nguyên từ 1 trở lên.');
            render();
            return;
        }

        var quantity = parseInt(raw, 10);
        if (!Number.isSafeInteger(quantity) || quantity < 1) {
            showMessage('Số lượng phải là số nguyên từ 1 trở lên.');
            render();
            return;
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

    var suggestionBox = document.getElementById('sku-suggestions');

    function closeSuggestions() {
        latestSearchId++;
        suggestions = [];
        activeSuggestion = -1;
        suggestionBox.innerHTML = '';
        suggestionBox.classList.add('hidden');
    }

    function renderSuggestions(items) {
        suggestions = items;
        activeSuggestion = -1;
        suggestionBox.innerHTML = '';

        if (items.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'sku-suggestion-empty';
            empty.textContent = 'Không có sản phẩm phù hợp.';
            suggestionBox.appendChild(empty);
            suggestionBox.classList.remove('hidden');
            return;
        }

        items.forEach(function (product, index) {
            var row = document.createElement('div');
            row.className = 'sku-suggestion';
            row.setAttribute('role', 'option');
            if (product.current_stock <= 0) {
                row.className += ' disabled';
                row.setAttribute('aria-disabled', 'true');
            }

            var top = document.createElement('div');
            top.className = 'sku-suggestion-top';

            var code = document.createElement('b');
            code.textContent = product.sku_code;
            top.appendChild(code);

            var stock = document.createElement('span');
            stock.textContent = product.current_stock <= 0 ? 'Hết hàng' : 'Tồn: ' + product.current_stock;
            top.appendChild(stock);

            var name = document.createElement('div');
            name.textContent = product.name + ' - ' + product.unit + ' - ' + formatMoney(product.selling_price);

            row.appendChild(top);
            row.appendChild(name);

            if (product.current_stock > 0) {
                row.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    addBySku(product);
                });
                row.addEventListener('mouseenter', function () {
                    setActiveSuggestion(index);
                });
            }

            suggestionBox.appendChild(row);
        });

        suggestionBox.classList.remove('hidden');
    }

    function setActiveSuggestion(index) {
        var rows = suggestionBox.querySelectorAll('.sku-suggestion');
        for (var i = 0; i < rows.length; i++) {
            rows[i].classList.remove('active');
        }
        if (index >= 0 && index < suggestions.length) {
            activeSuggestion = index;
            if (!rows[index].classList.contains('disabled')) {
                rows[index].classList.add('active');
            } else {
                activeSuggestion = -1;
            }
        } else {
            activeSuggestion = -1;
        }
    }

    function searchSuggestions() {
        var q = skuInput.value.trim();
        if (q.length < 2) {
            closeSuggestions();
            return;
        }

        var searchId = ++latestSearchId;
        fetch(searchUrl + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(readJson)
            .then(function (result) {
                if (searchId !== latestSearchId) {
                    return;
                }
                if (!result.ok) {
                    closeSuggestions();
                    return;
                }
                renderSuggestions(result.data.data || []);
            })
            .catch(function () {
                if (searchId === latestSearchId) {
                    closeSuggestions();
                }
            });
    }

    // Quét mã / gõ mã SKU: hỏi máy chủ thông tin sản phẩm rồi thêm vào giỏ.
    function addProductToCart(product) {
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
    }

    function addBySku(productOverride) {
        if (productOverride) {
            clearMessage();
            receipt.classList.add('hidden');
            addProductToCart(productOverride);
            skuInput.value = '';
            closeSuggestions();
            skuInput.focus();
            return;
        }

        var sku = skuInput.value.trim();
        if (sku === '') {
            return;
        }

        clearMessage();
        receipt.classList.add('hidden');
        closeSuggestions();

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

                addProductToCart(result.data);
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
        if (submitting) {
            return;
        }

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

        submitting = true;
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
                    var firstError = null;
                    if (result.data.errors) {
                        var errorKeys = Object.keys(result.data.errors);
                        if (errorKeys.length > 0) {
                            firstError = result.data.errors[errorKeys[0]][0];
                        }
                    }
                    showMessage(firstError || result.data.message || 'Thanh toán không thành công.');
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
                submitting = false;
                checkoutBtn.disabled = false;
                skuInput.focus();
            });
    }

    // ----- Gắn sự kiện -----
    document.getElementById('btn-scan').addEventListener('click', addBySku);

    skuInput.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (suggestions.length === 0) {
                return;
            }

            var next = activeSuggestion;
            if (event.key === 'ArrowDown') {
                next = next + 1;
            } else {
                next = next - 1;
            }

            while (next >= 0 && next < suggestions.length && suggestions[next].current_stock <= 0) {
                next += event.key === 'ArrowDown' ? 1 : -1;
            }

            if (next < 0) {
                next = -1;
            }
            if (next >= suggestions.length) {
                next = -1;
            }
            setActiveSuggestion(next);
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            if (activeSuggestion >= 0 && suggestions[activeSuggestion]) {
                addBySku(suggestions[activeSuggestion]);
            } else {
                clearTimeout(searchTimer);
                closeSuggestions();
                addBySku();
            }
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeSuggestions();
        }
    });

    skuInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        var q = skuInput.value.trim();
        if (q.length < 2) {
            closeSuggestions();
            return;
        }
        searchTimer = setTimeout(searchSuggestions, 250);
    });

    document.addEventListener('mousedown', function (event) {
        if (!event.target.closest('.sku-search-wrap')) {
            closeSuggestions();
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

    document.getElementById('btn-clear-cart').addEventListener('click', function () {
        cart = [];
        paidInput.value = '';
        clearMessage();
        render();
        skuInput.focus();
    });

    document.getElementById('btn-print').addEventListener('click', function () {
        window.print();
    });

    document.getElementById('btn-new-order').addEventListener('click', function () {
        receipt.classList.add('hidden');
        skuInput.focus();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'F9') {
            if (event.repeat) {
                return;
            }

            if (event.target && event.target.classList && event.target.classList.contains('input-qty')) {
                return;
            }

            event.preventDefault();
            checkout();
        }
    });

    render();
})();
