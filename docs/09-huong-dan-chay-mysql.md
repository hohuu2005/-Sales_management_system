# Hướng dẫn chạy MySQL bằng XAMPP

**Mục đích tài liệu**: Hướng dẫn chuyển môi trường phát triển từ SQLite sang MySQL XAMPP và kiểm thử khóa tồn kho.

**Ai nên đọc**: Người triển khai, người kiểm thử POS và người vận hành.

**Cập nhật lần cuối**: 25/11/2026.

**File liên quan**: `.env`, `database/migrations/`, `app/Services/PosOrderService.php`, `app/Models/Inventory.php`.

## Tạo cơ sở dữ liệu

1. Mở XAMPP Control Panel.
2. Khởi động `Apache` nếu cần truy cập phpMyAdmin và `MySQL`.
3. Mở `http://localhost/phpmyadmin`.
4. Chọn tab cơ sở dữ liệu.
5. Tạo database tên `sims_db` với collation `utf8mb4_unicode_ci`.

Không commit file `.env` thật và không ghi mật khẩu hoặc APP_KEY vào tài liệu.

## Các biến DB trong .env

Chỉnh các biến sau trong `.env` cục bộ. Đây là giá trị mẫu mặc định của XAMPP:

| Biến | Giá trị mẫu |
|---|---|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `sims_db` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | để trống |

Sau khi đổi cấu hình, Laravel cần tạo lại cache cấu hình nếu có:

```bash
php artisan optimize:clear
```

## Chạy migration và seed

Trong thư mục dự án:

```bash
composer install
php artisan migrate
php artisan db:seed
php artisan serve
```

Không chạy `php artisan migrate:fresh` nếu đang có dữ liệu cần giữ.

## Kiểm thử hai trình duyệt cùng mua 1 sản phẩm còn 1

Mục tiêu: xác nhận `lockForUpdate` trong `PosOrderService::checkout` hoạt động với MySQL/InnoDB.

1. Đảm bảo một sản phẩm đang bán có `current_stock = 1`.
2. Mở Hai trình duyệt riêng hoặc hai cửa sổ ẩn danh.
3. Đăng nhập bằng hai tài khoản role SALES khác nhau.
4. Cả hai trình duyệt quét cùng một SKU.
5. Mỗi giỏ thêm 1 sản phẩm.
6. Nhấn thanh toán tại hai tab trong cùng một thời điểm.

## Kết quả mong đợi

- Một request thanh toán thành công.
- Hóa đơn thành công còn lại tồn kho bằng 0.
- Request còn lại nhận thông báo không đủ tồn kho.
- Chỉ có một hóa đơn được tạo.
- Không có hóa đơn được tạo nhưng tồn kho chưa trừ hoặc trừ kho mà chưa có hóa đơn.

Nếu cả hai hóa đơn đều tạo thành công hoặc tồn kho âm, quá trình kiểm thử chưa đạt. Khi đó cần kiểm tra engine bảng là InnoDB, transaction có commit/rollback đúng và không có thay đổi ngoài luồng POS.

## Phân biệt SQLite và MySQL

SQLite trong môi trường phát triển không mô phỏng đầy đủ hành vi khóa dòng như MySQL/InnoDB. Vì vậy test đồng thời trên SQLite không thay thế được bước kiểm thử hai trình duyệt trên MySQL.
