# Nhật ký thay đổi POS

**Mục đích tài liệu**: Ghi lại các thay đổi đã thực hiện trên nhánh POS.

**Ai nên đọc**: Người review code và người bảo trì.

**Cập nhật lần cuối**: 25/11/2026.

**File liên quan**: `app/Services/PosOrderService.php`, `app/Http/Controllers/PosOrderController.php`, `public/js/pos.js`, `tests/Feature/ThanhToanPosTest.php`.

| Mã mục | Ngày | Mô tả tiếng Việt | File bị đổi | Commit |
|---|---|---|---|---|
| S1 | 25/11/2026 | Tách lỗi nghiệp vụ khỏi lỗi hệ thống và trả HTTP 422/500 phù hợp | `PosOrderService.php`, `PosOrderController.php`, `LoiBanHangException.php` | Xem git log |
| S2 | 25/11/2026 | Bổ sung thông báo ngừng bán riêng | `PosOrderService.php` | Xem git log |
| S3 | 25/11/2026 | Đảm bảo SKU toàn chữ số không bị PHP đổi thành khóa int | `PosOrderService.php` | Xem git log |
| S4 | 25/11/2026 | Sinh mã hóa đơn 4 chữ số ngẫu nhiên, kiểm tra unique và thử lại 5 lần | `PosOrderService.php`, `Order.php` | Xem git log |
| S5 | 25/11/2026 | Lưu unit_cost tại thời điểm bán bằng migration mới | `OrderDetail.php`, migration mới, `PosOrderService.php` | Xem git log |
| S6 | 25/11/2026 | Thêm cờ submitting chống thanh toán hai lần | `public/js/pos.js` | Xem git log |
| S7 | 25/11/2026 | Chặn nhập số lượng không phải số nguyên | `public/js/pos.js` | Xem git log |
| S8 | 25/11/2026 | Giới hạn dữ liệu đầu vào và thêm throttle | `PosOrderController.php`, `routes/web.php` | Xem git log |
| S9 | 25/11/2026 | Chặn trừ tồn khi số lượng trừ lớn hơn tồn hiện tại | `Inventory.php` | Xem git log |
| Đổi tên vòng tiếp theo | 25/11/2026 | Đặt lại tên exception, file test và hàm sinh mã bằng tiếng Việt không dấu | `LoiBanHangException.php`, `ThanhToanPosTest.php`, `PosOrderService.php` | Xem git log |
| Tài liệu | 25/11/2026 | Viết bộ tài liệu hướng dẫn luồng bán hàng POS | `docs/` và `README.md` | 7c1c195 |
| Khôi phục Boost | 25/11/2026 | Khôi phục boost đã có sẵn trong commit gốc | `composer.json`, `composer.lock` | d7d4cfa |
| Validation tiếng Việt | 25/11/2026 | Bổ sung message validation, hiển thị errors đầu tiên trong POS JS và test ba giới hạn | `PosOrderController.php`, `pos.js`, `ThanhToanPosTest.php`, `pos/index.blade.php` | e705c89 |
| Đóng sổ tài liệu | 25/11/2026 | Cập nhật tài liệu cũ, hoàn thiện hướng dẫn MySQL và nhật ký | `docs/`, `README.md` | 708f204 |

Chạy `git log --oneline -8` để xem mã commit và mô tả đầy đủ.
