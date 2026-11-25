# Quy tắc nghiệp vụ POS

**Mục đích tài liệu**: Liệt kê các quy tắc bắt buộc khi sửa luồng bán hàng.

**Ai nên đọc**: Người viết code backend, frontend hoặc kiểm thử POS.

**Cập nhật lần cuối**: 25/11/2026.

**File liên quan**: `PosOrderService.php`, `PosOrderController.php`, `Inventory.php`, `Product.php`, `public/js/pos.js`, `routes/web.php`.

1. **Giá bán luôn lấy từ CSDL**. Lý do: trình duyệt có thể bị sửa dữ liệu. Nằm ở `PosOrderService::checkout` đọc `Product::selling_price`.
2. **Gộp các dòng SKU trùng**. Lý do: tránh kiểm tra hai lần hoặc trừ sai tổng số lượng. Nằm ở `PosOrderService::checkout`.
3. **Dùng tiền tố khóa SKU**. Lý do: SKU toàn chữ số không được PHP ép thành khóa số nguyên. Nằm ở `PosOrderService::checkout`.
4. **Khóa dòng tồn kho**. Lý do: hai giao dịch cần chờ nhau thay vì cùng trừ một tồn kho. Nằm ở `PosOrderService::checkout` dùng `Inventory::where(...)->lockForUpdate()`.
5. **Không bán sản phẩm ngừng bán**. Lý do: `is_active=false` không còn là hàng đang kinh doanh. Nằm ở `PosOrderController::scanBarcode` và `PosOrderService::checkout`.
6. **Không bán quá tồn**. Lý do: current_stock không được âm. Nằm ở `PosOrderService::checkout` và `Inventory::deductStock`.
7. **Tiền mặt bắt buộc đủ**. Lý do: không lưu hóa đơn khi khách đưa ít hơn tổng. Nằm ở `PosOrderService::checkout` với `customer_paid < totalAmount`.
8. **Tiền thừa tính ở server**. Lý do: client không phải nguồn dữ liệu tin cậy. Nằm ở `PosOrderService::checkout` tính `changeAmount = customerPaid - totalAmount`.
9. **VIETQR coi khách đã trả đủ tổng tiền**. Lý do: hệ thống chưa gọi cổng và chưa xác minh giao dịch ngân hàng. Nằm ở `PosOrderService::checkout`.
10. **Toàn bộ checkout nằm trong transaction**. Transaction là nhóm thao tác chỉ lưu cùng nhau; rollback hoàn tác nếu lỗi. Nằm ở `PosOrderService::checkout` dùng `DB::beginTransaction`, `DB::commit`, `DB::rollBack`.
11. **Lỗi nghiệp vụ trả HTTP 422**. Lý do: thu ngân được phép xem thông báo để sửa thao tác. Nằm ở `LoiBanHangException` và `PosOrderController::checkout`.
12. **Lỗi hệ thống trả HTTP 500 và không lộ chi tiết**. Lý do: tránh đưa stack trace hoặc thông tin kỹ thuật ra trình duyệt. Nằm ở `PosOrderController::catch (Throwable)` gọi `report($e)`.
13. **Mã hóa đơn không trùng**. `taoMaHoaDonUngVien` tạo `ORD-YYYYMMDDHHMMSS-XXXX` bằng `random_int`; `taoMaHoaDonKhongTrung` thử tối đa 5 lần, sau đó ném lỗi nghiệp vụ.
14. **Giá vốn chốt tại thời điểm bán**. Lý do: `products.cost_price` có thể đổi sau nhập kho hoặc cập nhật. Nằm ở `PosOrderService::checkout` lưu `unit_cost` vào `OrderDetail`.
15. **Giỏ giữ ở trình duyệt**. Cờ `submitting` chống bấm đúp; tải lại trang làm giỏ mất. Nằm ở `public/js/pos.js`.
16. **Giới hạn dữ liệu đầu vào**. Nằm ở `PosOrderController::checkout`: tối đa 100 dòng, quantity tối đa 9999, customer_paid tối đa 1.000.000.000.
17. **Throttle hai API POS**. Lý do: giảm request lặp hoặc spam. Nằm ở `routes/web.php`: checkout 60 request/phút, scan 120 request/phút.
18. **Số lượng nhập tay phải là số nguyên**. `changeQuantity` trong `public/js/pos.js` không đổi quantity cũ với chuỗi rỗng, âm, thập phân hoặc chữ.
19. **F9 không chạy khi đang gõ số lượng**. `public/js/pos.js` bỏ qua khi target có class `input-qty`; bỏ qua cả khi `event.repeat=true`.
