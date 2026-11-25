# Hướng dẫn kiểm thử POS

**Mục đích tài liệu**: Hướng dẫn kiểm thử tự động và thủ công luồng bán hàng.

**Ai nên đọc**: Người phát triển và người kiểm thử.

**Cập nhật lần cuối**: 25/11/2026.

**File liên quan**: `tests/Feature/ThanhToanPosTest.php`, `tests/Feature/ExampleTest.php`, `app/Services/PosOrderService.php`, `public/js/pos.js`.

## Cập nhật code từ nhánh này

Sau khi chuyển sang nhánh `fix/pos-checkout`, chạy các lệnh sau:

```bash
git pull
composer install
php artisan migrate
php artisan optimize:clear
php artisan test
```

Hóa đơn cũ có thể có `order_details.unit_cost = 0` vì migration mới không truy ngược được giá vốn tại thời điểm tạo hóa đơn cũ.

## Chạy kiểm thử tự động

```bash
php artisan test
php artisan test --filter=ThanhToanPosTest
```

Test mẫu `tests/Feature/ExampleTest.php` đã kỳ vọng `/` chuyển hướng về `/login`, không còn kỳ vọng 200. Xem `ExampleTest::test_the_application_returns_a_successful_response`.

## Danh sách ca tự động

| Tên hàm test | Kiểm tra |
|---|---|
| `test_thanh_toan_tien_mat_thanh_cong_va_tru_kho` | Trừ tồn, lưu `unit_price` và `unit_cost` |
| `test_gop_cac_dong_trung_sku` | Gộp nhiều dòng SKU trùng thành một dòng kiểm tra kho |
| `test_tien_khach_dua_thieu_tra_ve_422` | Không tạo order và không đổi tồn khi thiếu tiền |
| `test_so_luong_vuot_ton_kho` | Không tạo order và không đổi tồn khi vượt tồn |
| `test_san_pham_ngung_ban` | Trả 422 thông báo ngừng bán |
| `test_bo_qua_don_gia_do_trinh_duyet_gui` | Luôn lấy giá bán từ database |
| `test_tai_khoan_khong_phai_sales_bi_chan_403` | Tài khoản không phải SALES không truy cập checkout |
| `test_loi_bat_ngo_hoan_tac_va_khong_lo_chi_tiet` | Rollback và trả 500 không lộ lỗi kỹ thuật |
| `test_ma_hoa_don_trung_se_thu_lai` | Khi mã trùng, thử lại bằng mã khác |

## Checklist kiểm thử thủ công trên trình duyệt

1. Đăng nhập bằng tài khoản có vai trò SALES.
2. Quét mã SKU đang tồn tại.
3. Quét lại cùng mã để kiểm tra cộng số lượng.
4. Quét mã không tồn tại.
5. Quét sản phẩm ngừng bán.
6. Quét sản phẩm tồn kho bằng 0.
7. Nhập số lượng 1.5.
8. Nhập chuỗi hoặc để ô số lượng trống.
9. Đưa số lượng lớn hơn tồn kho.
10. Tiền khách thiếu vẫn không gửi thanh toán.
11. Thanh toán tiền mặt đủ tiền.
12. Thanh toán tiền mặt thừa tiền.
13. Chọn VIETQR và xác nhận.
14. Bấm nút thanh toán hai lần liên tiếp.
15. Nhấn giữ F9 và kiểm tra chỉ gửi một lần.
16. Đang gõ ô số lượng rồi bấm F9; không được thanh toán.
17. Bấm F9 khi đang nhập ô SKU hoặc ô tiền khách; POS vẫn hoạt động như cũ.
18. Bấm in hóa đơn.
19. Tải lại trang và xác nhận giỏ hàng bị mất.
20. Tài khoản ADMIN hoặc STOCKKEEPER truy cập `/pos` hoặc gọi checkout phải bị 403.

## Kiểm thử đồng thời trên MySQL

`lockForUpdate` không có tác dụng khóa dòng thực sự trong SQLite. Cần thử lại bằng MySQL/InnoDB:

1. Mở hai trình duyệt hoặc hai tab đăng nhập hai tài khoản SALES.
2. Đưa giỏ mỗi tab mua 1 cái khi tồn kho còn 1.
3. Thanh toán gần đồng thời.
4. Một tab thanh toán thành công, tab còn lại phải nhận thông báo không đủ tồn kho.
