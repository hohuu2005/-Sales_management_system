# Bảng tra cứu lỗi POS

**Mục đích tài liệu**: Giúp tìm nhanh thông báo, mã HTTP, nguyên nhân và vị trí code.

**Ai nên đọc**: Người phát triển, người kiểm thử và người hỗ trợ thu ngân.

**Cập nhật lần cuối**: 25/11/2026.

**File liên quan**: `PosOrderController.php`, `PosOrderService.php`, `Inventory.php`, `public/js/pos.js`.

| Thông báo hiển thị | Mã HTTP | Nguyên nhân | Nơi phát sinh | Cách xử lý |
|---|---|---|---|---|
| `Vui lòng nhập mã SKU.` | 422 | SKU trong request quét rỗng | `PosOrderController::scanBarcode` | Nhập hoặc quét lại mã |
| `Không tìm thấy sản phẩm có mã <sku>.` | 404 | Không có SKU hoặc sản phẩm không còn đang bán | `PosOrderController::scanBarcode` | Kiểm tra SKU hoặc trạng thái sản phẩm |
| `Giỏ hàng đang trống.` | 422 | `items` rỗng hoặc validation mảng không đạt | `PosOrderController::checkout` | Thêm ít nhất một sản phẩm |
| `Phương thức thanh toán không hợp lệ.` | 422 | payment_method khác CASH/VIETQR | `PosOrderController::checkout` | Chọn CASH hoặc VIETQR |
| `Không tìm thấy sản phẩm có mã <sku>.` | 422 | SKU trong checkout không tồn tại | `PosOrderService::checkout` ném `LoiBanHangException` | Kiểm tra lại giỏ hoặc quét SKU hợp lệ |
| `Sản phẩm <tên> đã ngừng bán.` | 422 | Product `is_active=false` | `PosOrderService::checkout` | Thay sản phẩm khác |
| `Sản phẩm <tên> không đủ số lượng trong kho (còn <n>).` | 422 | Quantity yêu cầu vượt tồn hiện tại | `PosOrderService::checkout` | Giảm số lượng hoặc nhập kho |
| `Sản phẩm không đủ tồn kho!` | 422 | `deductStock` phát hiện tồn không đủ khi trừ | `Inventory::deductStock` ném `LoiBanHangException` | Kiểm tra lại tồn trước thanh toán |
| `Tiền khách đưa chưa đủ.` | 422 | customer_paid nhỏ hơn tổng tiền mặt | `PosOrderService::checkout` | Nhận thêm tiền |
| `Không tạo được mã hóa đơn, vui lòng thử lại.` | 422 | Mã ngẫu nhiên trùng 5 lần liên tiếp | `PosOrderService::taoMaHoaDonKhongTrung` | Bấm thanh toán lại |
| `Lỗi hệ thống, vui lòng thử lại sau.` | 500 | Exception ngoài lỗi nghiệp vụ, ví dụ database trigger hoặc lỗi lưu chi tiết | `PosOrderController::catch (Throwable)` | Thử lại; kiểm tra log server |
| `Giỏ hàng đang trống.` | Giao diện JS | Nút thanh toán khi cart trống | `pos.js::checkout` | Quét ít nhất một sản phẩm |
| `Tiền khách đưa chưa đủ.` | Giao diện JS | Tiền nhập ít hơn tổng hiển thị | `pos.js::checkout` | Nhập đủ tiền |
| `Số lượng phải là số nguyên từ 1 trở lên.` | Giao diện JS | Ô số lượng rỗng, âm, thập phân hoặc không phải số | `pos.js::changeQuantity` | Nhập số nguyên dương |
| `Sản phẩm không đủ số lượng trong kho, không thể cập nhật số lượng. Tồn kho hiện tại: <n>.` | Giao diện JS | Số lượng nhập tay lớn hơn tồn | `pos.js::changeQuantity` | Giảm số lượng |
| `Sản phẩm không đủ số lượng trong kho, không thể thêm vào giỏ. Tồn kho hiện tại: <n>.` | Giao diện JS | Quét hoặc cộng thêm vượt tồn | `pos.js::addBySku` | Giảm số lượng hoặc nhập kho |
| `Không tìm thấy sản phẩm.` | Giao diện JS | Response quét không OK nhưng không có message | `pos.js::addBySku` | Kiểm tra SKU và server |
| `Thanh toán không thành công.` | Giao diện JS | Response checkout không OK nhưng không có message | `pos.js::checkout` | Đọc thông báo server nếu có; kiểm tra log |
| `Không kết nối được máy chủ.` | Giao diện JS | fetch bị lỗi mạng hoặc không bắt được response | `pos.js::addBySku`, `pos.js::checkout` | Kiểm tra kết nối và Laravel server |
| `Lỗi máy chủ (mã <status>).` | Giao diện JS | Response không phải JSON hoặc lỗi đọc JSON | `pos.js::readJson` | Kiểm tra mã HTTP và log server |
| `Không có sản phẩm phù hợp.` | Giao diện JS | API `/pos/search` trả mảng rỗng | `pos.js::renderSuggestions` | Thử từ khóa khác hoặc quét mã SKU |
| `Hết hàng` | Giao diện JS | Sản phẩm tìm được có `current_stock=0` | `pos.js::renderSuggestions` | Không chọn dòng này; nhập kho trước |
| `Ngày bắt đầu phải đúng định dạng YYYY-MM-DD.` | 422 | start_date không theo định dạng ngày | `PosOrderController::myHistory` | Nhập lại ngày hợp lệ |
| `Ngày kết thúc phải đúng định dạng YYYY-MM-DD.` | 422 | end_date không theo định dạng ngày | `PosOrderController::myHistory` | Nhập lại ngày hợp lệ |
| `Ngày kết thúc không được trước ngày bắt đầu.` | 422 | end_date nhỏ hơn start_date | `PosOrderController::myHistory` | Chọn khoảng ngày hợp lệ |
| `Khoảng lọc tối đa 366 ngày.` | 422 | Khoảng giữa hai ngày vượt 366 | `PosOrderController::myHistory` | Rút ngắn khoảng lọc |
| `404 Not Found` | 404 | Hóa đơn không tồn tại hoặc không thuộc thu ngân đang đăng nhập | `PosOrderController::chiTietHoaDon` | Kiểm tra mã hóa đơn và tài khoản |
| `Vui lòng chọn phương thức thanh toán.` | 422 | Thiếu payment_method | `PosOrderController::checkout` | Chọn CASH hoặc VIETQR |
| `Phương thức thanh toán không hợp lệ.` | 422 | payment_method khác CASH/VIETQR | `PosOrderController::checkout` | Chọn đúng hai phương thức |
| `Tiền khách đưa phải là số.` | 422 | customer_paid không phải số | `PosOrderController::checkout` | Nhập số hợp lệ |
| `Tiền khách đưa không được âm.` | 422 | customer_paid nhỏ hơn 0 | `PosOrderController::checkout` | Nhập từ 0 trở lên |
| `Tiền khách đưa tối đa 1.000.000.000 đ.` | 422 | customer_paid vượt 1000000000 | `PosOrderController::checkout` | Nhập không vượt giới hạn |
| `Dữ liệu giỏ hàng không hợp lệ.` | 422 | items không phải mảng | `PosOrderController::checkout` | Gửi mảng items hợp lệ |
| `Giỏ hàng phải có ít nhất một sản phẩm.` | 422 | items có ít hơn 1 phần tử | `PosOrderController::checkout` | Thêm ít nhất một dòng |
| `Giỏ hàng tối đa 100 dòng.` | 422 | items có hơn 100 phần tử | `PosOrderController::checkout` | Chia giỏ hoặc giảm số dòng |
| `Vui lòng nhập mã SKU cho mỗi dòng.` | 422 | Thiếu sku_code | `PosOrderController::checkout` | Nhập đủ SKU |
| `Mã SKU phải là chuỗi ký tự.` | 422 | sku_code không phải string | `PosOrderController::checkout` | Gửi SKU dạng chuỗi |
| `Mã SKU tối đa 50 ký tự.` | 422 | sku_code dài hơn 50 ký tự | `PosOrderController::checkout` | Rút ngắn SKU |
| `Vui lòng nhập số lượng cho mỗi dòng.` | 422 | Thiếu quantity | `PosOrderController::checkout` | Nhập quantity |
| `Số lượng mỗi dòng phải là số nguyên.` | 422 | quantity không phải integer | `PosOrderController::checkout` | Gửi số nguyên |
| `Số lượng mỗi dòng tối thiểu là 1.` | 422 | quantity nhỏ hơn 1 | `PosOrderController::checkout` | Nhập từ 1 trở lên |
| `Số lượng mỗi dòng tối đa 9999.` | 422 | quantity lớn hơn 9999 | `PosOrderController::checkout` | Giảm quantity |

## Quy tắc chọn mã HTTP

- 422 dành cho lỗi nghiệp vụ mà thu ngân có thể sửa.
- 500 dành cho lỗi hệ thống bất ngờ.
- 403 dành cho tài khoản không có role SALES.
- 404 dành cho SKU không tìm thấy ở luồng quét.
