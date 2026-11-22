# SIMS - Hệ thống Quản lý Bán hàng và Tồn kho

Đồ án môn Chuyên đề tổng hợp - Nhóm 15, lớp 68PM2, Khoa Công nghệ thông tin, Trường Đại học Xây dựng Hà Nội.
Giảng viên hướng dẫn: TS Phạm Hữu Tùng.

| Thành viên | Mã SV |
|---|---|
| Hồ Chí Hữu | 0014768 |
| Nguyễn Ngọc Sơn | 0023968 |
| Đào Văn Minh | 0018368 |

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
| `admin` | ADMIN | Danh sách hàng hóa và tồn kho |
| `thukho` | STOCKKEEPER | Lập phiếu nhập kho |
| `banhang` | SALES | Bán hàng POS |

## Tình trạng chức năng

| Chức năng | Tình trạng |
|---|---|
| Đăng nhập, đăng xuất, phân quyền theo vai trò | Đã xong |
| Bán hàng POS: quét mã SKU, giỏ hàng, thanh toán, trừ tồn kho | Đã xong (mã QR chuyển khoản mới là hình minh họa) |
| Lịch sử giao dịch của thu ngân | Đã xong |
| Lập phiếu nhập kho, cộng tồn kho, lịch sử nhập | Đã xong |
| Danh sách hàng hóa và tồn kho, tra cứu sản phẩm | Đã xong (chỉ xem) |
| Thêm, sửa, xóa danh mục, sản phẩm, người dùng | Chưa làm |
| Báo cáo doanh thu, báo cáo tồn kho | Chưa làm |
| Đổi mật khẩu | Chưa làm |

## Cấu trúc thư mục chính

```
app/Http/Controllers/   Nhận request, kiểm tra dữ liệu, trả View hoặc JSON
app/Http/Middleware/    CheckRole: phân quyền theo vai trò
app/Services/           Nghiệp vụ nhiều bước, có transaction (bán hàng, nhập kho)
app/Models/             8 model tương ứng 8 bảng
database/migrations/    Định nghĩa các bảng
database/seeders/       Dữ liệu mẫu
resources/views/        Giao diện Blade
routes/web.php          Toàn bộ đường dẫn
docs/GIAI_THICH_CODE.md Giải thích từng file và từng luồng xử lý
```
