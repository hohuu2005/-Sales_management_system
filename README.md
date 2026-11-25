# SIMS - Hệ thống Quản lý Bán hàng và Tồn kho

Đồ án môn Chuyên đề tổng hợp - Nhóm 15, lớp 68PM2, Khoa Công nghệ thông tin, Trường Đại học Xây dựng Hà Nội.
Giảng viên hướng dẫn: TS Phạm Hữu Tùng.

## Thành viên và phần phụ trách

| Thành viên | Mã SV | Phần phụ trách |
|---|---|---|
| Hồ Chí Hữu | 0014768 | Đăng nhập, phân quyền, đổi mật khẩu, quản lý người dùng, định tuyến và giao diện chung |
| Nguyễn Ngọc Sơn | 0023968 | Nhập kho, quản lý danh mục và hàng hóa |
| Đào Văn Minh | 0018368 | Bán hàng POS, lịch sử giao dịch, báo cáo doanh thu và tồn kho |

## Công nghệ

- PHP 8.4 trở lên, Laravel 13 (mô hình MVC, thêm tầng Service cho nghiệp vụ ghi nhiều bảng)
- MySQL (InnoDB)
- Giao diện: Blade, CSS tự viết (`public/css/sims.css`), JavaScript thuần cho màn hình POS (`public/js/pos.js`)

## Cài đặt và chạy

1. Bật MySQL (XAMPP), tạo cơ sở dữ liệu tên `sims_db`.
2. Sao chép `.env.example` thành `.env`, sửa các dòng `DB_...` cho đúng máy, rồi chạy:

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

3. Mở trình duyệt: http://localhost:8000

Lưu ý: `migrate:fresh --seed` xóa toàn bộ dữ liệu cũ và tạo lại dữ liệu mẫu.

## Tài khoản mẫu (mật khẩu đều là `12345678`)

| Tên đăng nhập | Vai trò | Màn hình sau khi đăng nhập |
|---|---|---|
| `admin` | ADMIN | Hàng hóa và danh mục |
| `thukho` | STOCKKEEPER | Lập phiếu nhập kho |
| `banhang` | SALES | Bán hàng POS |

## Chức năng theo vai trò

| Vai trò | Chức năng |
|---|---|
| Mọi vai trò | Đăng nhập, đăng xuất, đổi mật khẩu |
| ADMIN | Quản lý danh mục, quản lý hàng hóa (thêm, sửa, ngừng bán), quản lý người dùng (thêm, sửa, phân quyền, khóa), báo cáo doanh thu, báo cáo tồn kho |
| STOCKKEEPER | Lập phiếu nhập kho, lịch sử phiếu nhập, cảnh báo tồn kho |
| SALES | Bán hàng POS (quét mã, giỏ hàng, thanh toán tiền mặt hoặc chuyển khoản), lịch sử giao dịch, tra cứu sản phẩm |

## Phần chưa làm

- Mã QR chuyển khoản mới là hình minh họa, chưa sinh mã VietQR thật.
- Chưa có tải ảnh sản phẩm (cột `image_url` để trống).
- Chưa có kiểm thử tự động.

## Tài liệu

Bộ tài liệu kỹ thuật bằng tiếng Việt dành cho người phát triển và kiểm thử nằm trong [docs/](docs/00-muc-luc.md). Bắt đầu bằng [mục lục tài liệu](docs/00-muc-luc.md), đặc biệt đọc [luồng bán hàng POS](docs/02-luong-ban-hang-pos.md) để hiểu từ tìm và quét mã đến tạo hóa đơn, lọc lịch sử và xem/in lại hóa đơn. Khi triển khai MySQL bằng XAMPP, xem [hướng dẫn chạy MySQL](docs/09-huong-dan-chay-mysql.md).

## Cấu trúc thư mục chính

```
app/Http/Controllers/   Nhận request, kiểm tra dữ liệu, trả View hoặc JSON
app/Http/Middleware/    CheckRole: phân quyền theo vai trò
app/Services/           Nghiệp vụ nhiều bước, có transaction (bán hàng, nhập kho, tạo sản phẩm)
app/Models/             8 model tương ứng 8 bảng
database/migrations/    Định nghĩa các bảng
database/seeders/       Dữ liệu mẫu
resources/views/        Giao diện Blade
routes/web.php          Toàn bộ đường dẫn
docs/GIAI_THICH_CODE.md Giải thích từng file và từng luồng xử lý
```
