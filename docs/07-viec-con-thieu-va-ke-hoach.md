# Việc đã làm và kế hoạch tiếp theo

**Mục đích tài liệu**: Phân biệt rõ phần POS đã hoàn thiện và phần còn thiếu.

**Ai nên đọc**: Người quản lý dự án, nhóm phát triển và người review.

**Cập nhật lần cuối**: 25/11/2026.

**File liên quan**: Tất cả file trong `app/`, `public/js/pos.js`, `resources/views/pos/`, `database/migrations/`, `tests/Feature/`.

## Đã làm

- S1: Tách lỗi nghiệp vụ bằng `LoiBanHangException`; lỗi hệ thống trả 500 an toàn.
- S2: Thông báo riêng khi sản phẩm ngừng bán.
- S3: Khóa SKU bằng tiền tố để SKU toàn chữ số không bị ép kiểu.
- S4: Sinh mã hóa đơn 4 số ngẫu nhiên, kiểm tra trùng tối đa 5 lần.
- S5: Lưu `unit_cost` vào hóa đơn tại thời điểm bán.
- S6: Chống bấm thanh toán hai lần bằng cờ `submitting`.
- S7: Kiểm tra số lượng nhập tay là số nguyên dương.
- S8: Giới hạn input và thêm throttle cho scan/checkout.
- S9: Chặn trừ tồn ở tầng ứng dụng khi tồn không đủ.
- Tìm sản phẩm theo tên hoặc SKU một phần bằng hộp gợi ý POS.
- Lọc ngày, phân trang 20 dòng và xem tổng hóa đơn của thu ngân.
- Xem chi tiết và in lại hóa đơn thuộc thu ngân đang đăng nhập.

## Chưa làm

| Mức ưu tiên | Mô tả | Vì sao cần | File dự kiến đụng tới | Độ khó |
|---|---|---|---|---|
| Cao | Thanh toán kết hợp nhiều phương thức trên một hóa đơn | POS thực tế có thể nhận tiền mặt và chuyển khoản trong cùng đơn | `PosOrderService.php`, migration orders, POS view/JS | Khó |
| Cao | Hủy hóa đơn và hoàn lại tồn kho | Cần xử lý bán nhầm hoặc hoàn tiền | `PosOrderController.php`, `PosOrderService.php`, migration trạng thái, `pos/history.blade.php` | Vừa |
| Cao | Trả hàng | Cần ghi nhận lượng hoàn lại và hoàn tồn kho | Migration mới, models, controller, service | Khó |
| Cao | Giảm giá theo dòng hoặc hóa đơn | Nhu cầu bán hàng thực tế | Migration orders/order_details, POS JS/service | Vừa |
| Cao | Gắn khách hàng vào hóa đơn | Dùng cho khuyến mãi, công nợ và lịch sử mua | Migration customers/orders, model, POS view | Khó |
| Cao | Ghi chú hóa đơn thực tế | Cột `note` đã có nhưng chưa nhận dữ liệu từ POS | `PosOrderController.php`, `pos/index.blade.php`, `orders` | Dễ |
| Cao | Tích hợp VietQR thật | Hiện QR chỉ là minh họa, chưa xác minh nhận tiền | Service thanh toán, view, cấu hình dịch vụ | Khó |
| Cao | Bảng biến động kho | Cần truy vết nhập, bán, điều chỉnh kho | Migration mới, model mới, inventory reports | Khó |
| Cao | Cảnh báo sắp hết hàng trên POS | Giúp thu ngân nhận biết hàng sắp hết ngay khi quét | `PosOrderController.php`, `pos.js`, `pos/index.blade.php` | Dễ |
| Cao | Test chạy trên MySQL | SQLite không thực thi đầy đủ lockForUpdate | Cấu hình test database, CI, tài liệu kiểm thử | Vừa |
| Trung bình | Kết ca và tổng kết doanh thu ca | Cần thống kê theo thu ngân và thời gian ca | Orders/report/controller/migration | Khó |
| Trung bình | Giữ giỏ hàng khi tải lại trang | Hiện giỏ nằm trong biến JavaScript và mất khi reload | `pos.js`, session hoặc bảng pending order | Vừa |
| Trung bình | Gửi mã hóa đơn và tránh unique race hoàn toàn | Hiện kiểm tra trùng trước tạo vẫn có nguy cơ hai request cùng mã | `PosOrderService.php`, cơ chế retry theo unique violation | Vừa |

## Rủi ro đã biết

Hai yêu cầu cùng sinh mã hóa đơn sau bước kiểm tra vẫn có thể gặp ràng buộc `order_code` unique. Cột unique là lớp bảo vệ cuối, nhưng code hiện chưa bắt riêng unique violation để thử lại ngay trong transaction. Cần bổ sung cơ chế retry an toàn sau khi kiểm chứng với MySQL và SQLite.
