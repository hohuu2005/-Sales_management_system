# Giải thích code SIMS (để cả nhóm đọc trước buổi demo)

Tài liệu này giải thích bằng lời đơn giản: một yêu cầu đi qua những file nào, mỗi file làm gì, và các câu thầy có thể hỏi.

## 1. Một yêu cầu đi qua những đâu

Khi người dùng bấm một nút hay mở một trang, yêu cầu đi lần lượt qua 5 tầng (đúng như Chương 6 của báo cáo):

1. **Route** (`routes/web.php`): tra xem đường dẫn này do hàm nào xử lý, ai được vào.
2. **Middleware**: `auth` kiểm tra đã đăng nhập chưa; `CheckRole` kiểm tra đúng vai trò chưa.
3. **Controller**: nhận dữ liệu, kiểm tra dữ liệu hợp lệ (`validate`), rồi gọi Service hoặc Model.
4. **Service**: làm nghiệp vụ nhiều bước trong một transaction (bán hàng, nhập kho).
5. **Model**: đọc và ghi vào bảng MySQL.

Kết quả trả về là một trang Blade (`resources/views`) hoặc JSON (riêng màn hình POS).

## 2. Luồng đăng nhập

| Bước | File | Việc làm |
|---|---|---|
| 1 | `routes/web.php` | `GET /login` hiện form, `POST /login` xử lý đăng nhập |
| 2 | `AuthController::login()` | Kiểm tra đã nhập đủ, rồi gọi `Auth::attempt()` với `username`, `password`, `is_active = true` |
| 3 | `User::getAuthPassword()` | Cho Laravel biết mật khẩu nằm ở cột `password_hash` |
| 4 | `AuthController::login()` | Đúng thì đổi mã session và chuyển tới `/dashboard` |
| 5 | `routes/web.php` (`/dashboard`) | ADMIN về `/products`, STOCKKEEPER về `/stock-imports/create`, SALES về `/pos` |

- Mật khẩu không lưu dạng chữ thường mà lưu dạng mã hóa một chiều Bcrypt. Khi đăng nhập, Laravel mã hóa mật khẩu vừa gõ rồi so với `password_hash`.
- Tài khoản bị khóa (`is_active = 0`) không đăng nhập được vì điều kiện `is_active = true` nằm ngay trong câu truy vấn tìm người dùng.

## 3. Phân quyền (`app/Http/Middleware/CheckRole.php`)

Trong route viết `->middleware('role:ADMIN,STOCKKEEPER')` nghĩa là chỉ ADMIN hoặc STOCKKEEPER được vào. Màn hình POS khai báo `role:SALES`, đúng với sơ đồ use case: chỉ Nhân viên bán hàng dùng POS. `CheckRole` lấy người đang đăng nhập, gọi `User::hasRole()` cho từng vai trò được phép. Không khớp vai trò nào thì trả lỗi 403.

Thử nhanh: đăng nhập `banhang` rồi gõ địa chỉ `/stock-imports/create` sẽ thấy trang 403.

## 4. Luồng bán hàng POS

| Bước | Ở đâu | Việc làm |
|---|---|---|
| 1 | `public/js/pos.js` (`addBySku`) | Gõ hoặc quét mã SKU rồi Enter. Trình duyệt gọi `GET /pos/scan?sku_code=...` |
| 2 | `PosOrderController::scanBarcode()` | Tìm sản phẩm bằng `Product::findBySku()`, trả JSON: tên, giá, tồn kho |
| 3 | `pos.js` | Thêm vào giỏ. Quét lại cùng mã thì cộng 1. Giỏ hàng chỉ nằm tạm trong trình duyệt, chưa lưu vào CSDL |
| 4 | `pos.js` (`checkout`) | Bấm Xác nhận thanh toán (hoặc F9). Trình duyệt gửi `POST /pos/checkout` gồm phương thức, tiền khách đưa, danh sách mã SKU và số lượng |
| 5 | `PosOrderController::checkout()` | Kiểm tra dữ liệu, gọi `PosOrderService::checkout()` |
| 6 | `PosOrderService::checkout()` | Mở transaction, khóa và kiểm tra tồn kho, tính tổng tiền theo giá trong CSDL, tạo `Order`, tạo `OrderDetail`, trừ kho, commit |
| 7 | `pos.js` (`showReceipt`) | Hiện hóa đơn, làm trống giỏ hàng |

Ba ý quan trọng trong `PosOrderService`:

- **Transaction**: `DB::beginTransaction()` ... `DB::commit()`, lỗi thì `DB::rollBack()`. Nhờ vậy không bao giờ có chuyện đã lưu hóa đơn mà chưa trừ kho.
- **Khóa dòng** `lockForUpdate()` (tức `SELECT ... FOR UPDATE`): khi một thu ngân đang thanh toán, thu ngân khác phải chờ ở dòng tồn kho đó. Tránh hai người cùng bán món hàng cuối cùng.
- **Không tin giá từ trình duyệt**: trình duyệt chỉ gửi mã SKU và số lượng. Giá lấy từ bảng `products`. Nếu ai đó sửa giá trên trình duyệt thì cũng không có tác dụng.

Tồn kho được kiểm tra hai lần: ở trình duyệt (báo lỗi ngay cho nhanh) và ở máy chủ (lần kiểm tra quyết định).

## 5. Luồng nhập kho

| Bước | File | Việc làm |
|---|---|---|
| 1 | `StockImportController::create()` | Hiện danh sách sản phẩm kèm ô số lượng nhập và đơn giá nhập |
| 2 | `StockImportController::storeImport()` | Kiểm tra dữ liệu, chỉ giữ những dòng có số lượng lớn hơn 0, gọi Service |
| 3 | `StockImportService::createImport()` | Mở transaction, tính tổng tiền, tạo `StockImport`, tạo từng `StockImportDetail`, cộng kho bằng `Inventory::addStock()`, cập nhật giá vốn, commit |
| 4 | `StockImportController::importHistory()` | Hiện lịch sử phiếu nhập, lọc theo khoảng ngày |

## 6. Mỗi file làm gì

| File | Vai trò |
|---|---|
| `app/Models/User.php` | Người dùng. Có `getAuthPassword()` và `hasRole()` |
| `app/Models/Category.php` | Danh mục. Một danh mục có nhiều sản phẩm |
| `app/Models/Product.php` | Sản phẩm. `findBySku()` tìm theo mã SKU. Cột `attributes_json` lưu thuộc tính động (size, màu, hạn dùng) |
| `app/Models/Inventory.php` | Tồn kho, mỗi sản phẩm một dòng. `hasEnoughStock()`, `deductStock()`, `addStock()`, `isLowStock()` |
| `app/Models/Order.php`, `OrderDetail.php` | Hóa đơn và các dòng chi tiết |
| `app/Models/StockImport.php`, `StockImportDetail.php` | Phiếu nhập và các dòng chi tiết |
| `app/Services/PosOrderService.php` | Nghiệp vụ thanh toán |
| `app/Services/StockImportService.php` | Nghiệp vụ nhập kho |
| `app/Services/ProductService.php` | Tạo và sửa sản phẩm cùng dòng tồn kho trong một transaction |
| `resources/views/layouts/app.blade.php` | Khung chung: thanh menu theo vai trò, nút đăng xuất, vùng thông báo |
| `resources/views/...` | `auth/login`, `pos/index`, `pos/history`, `stock_imports/create`, `stock_imports/index`, `products/index`, `categories/index`, `users/index`, `reports/revenue`, `reports/inventory` |
| `public/css/sims.css` | Giao diện, lấy màu và kiểu dáng từ wireframe của nhóm |
| `public/js/pos.js` | Toàn bộ xử lý phía trình duyệt của màn hình POS |

## 7. Câu thầy có thể hỏi

**Vì sao cần tầng Service, sao không viết hết trong Controller?**
Thanh toán và nhập kho ghi vào nhiều bảng cùng lúc và cần transaction. Tách ra Service thì Controller ngắn gọn, chỉ lo nhận và trả dữ liệu.

**Nếu hai thu ngân cùng bán món hàng cuối cùng thì sao?**
Người thanh toán trước khóa dòng tồn kho bằng `lockForUpdate()`. Người sau phải chờ, đến lượt thì thấy tồn kho còn 0, Service báo lỗi "không đủ số lượng trong kho" và rollback.

**Vì sao lưu `unit_price` trong `order_details` khi `products` đã có giá?**
Để giữ đúng giá tại thời điểm bán. Sau này Admin đổi giá sản phẩm thì hóa đơn cũ không bị đổi theo.

**CSRF token là gì, dùng ở đâu?**
Là một mã bí mật Laravel cấp cho mỗi phiên làm việc. Form có `@csrf`, còn màn hình POS gửi mã này trong header `X-CSRF-TOKEN`. Trang web lạ không biết mã này nên không thể giả mạo yêu cầu thay người dùng.

**Vì sao tài khoản và sản phẩm dùng `is_active` thay vì xóa?**
Vì hóa đơn và phiếu nhập cũ còn tham chiếu tới chúng (khóa ngoại `ON DELETE RESTRICT`). Xóa cứng sẽ mất lịch sử.

**Vì sao `current_stock` là số không dấu (UNSIGNED)?**
Là lớp chặn cuối cùng ở mức CSDL: nếu có lỗi làm tồn kho xuống dưới 0, MySQL báo lỗi và transaction bị rollback.

## 8. Quản lý người dùng và đổi mật khẩu (phần của Hữu)

| Việc | Hàm | Điểm cần nhớ |
|---|---|---|
| Thêm tài khoản | `UserController::store()` | Mật khẩu được mã hóa bằng `Hash::make()` rồi mới lưu vào `password_hash` |
| Sửa thông tin | `UserController::update()` | Kiểm tra trùng mã nhân viên, tên đăng nhập, email nhưng bỏ qua chính tài khoản đang sửa (`Rule::unique(...)->ignore($id)`) |
| Phân quyền | `UserController::updateRole()` | Không cho tự đổi vai trò của chính mình |
| Khóa tài khoản | `UserController::destroy()` | Không xóa hẳn, chỉ đặt `is_active = false` để giữ lịch sử hóa đơn; không cho tự khóa chính mình |
| Đổi mật khẩu | `AuthController::changePassword()` | So mật khẩu cũ bằng `Hash::check()`; mật khẩu mới tối thiểu 8 ký tự, phải nhập lại hai lần và khác mật khẩu cũ |

Trong mỗi controller, phần kiểm tra dữ liệu nằm ở hàm `...Rules()`, phần xử lý nằm ở các hàm nhỏ như `createUser()`, `lockUser()`. Các hàm nhỏ này không dùng `Request` nên có thể chạy thử riêng.

## 9. Quản lý danh mục và hàng hóa (phần của Sơn)

| Việc | Hàm | Điểm cần nhớ |
|---|---|---|
| Thêm danh mục | `CategoryController::store()` | Slug tự sinh từ tên: "Thời trang" thành `thoi-trang`; nếu trùng thì thêm `-2`, `-3` |
| Xóa danh mục | `CategoryController::destroy()` | Chặn nếu danh mục còn sản phẩm |
| Thêm sản phẩm | `ProductController::store()` gọi `ProductService::createWithInventory()` | Tạo sản phẩm và dòng tồn kho (bằng 0) trong cùng một transaction |
| Sửa sản phẩm | `ProductController::update()` gọi `ProductService::updateWithInventory()` | Sửa được định mức tồn kho và vị trí kệ, không sửa số tồn kho (tồn kho chỉ đổi qua nhập kho và bán hàng) |
| Xóa sản phẩm | `ProductController::destroy()` | Sản phẩm đã có trong hóa đơn hoặc phiếu nhập thì chỉ chuyển sang Ngừng bán; chưa có chứng từ thì xóa hẳn |
| Thuộc tính động | `ProductController::collectAttributes()` | Kích cỡ, màu sắc, hạn dùng được gom thành mảng và lưu vào cột JSON `attributes_json`; ô nào trống thì không lưu |

Nếu giá bán thấp hơn giá vốn, hệ thống vẫn lưu nhưng hiện cảnh báo màu vàng để Admin kiểm tra lại.

## 10. Báo cáo và màn hình POS (phần của Minh)

| Việc | Hàm | Điểm cần nhớ |
|---|---|---|
| Báo cáo doanh thu | `ReportController::revenueReport()` gọi `buildRevenue()` | Chỉ tính hóa đơn `COMPLETED` trong khoảng ngày; ngày không có hóa đơn vẫn hiện cột bằng 0; tối đa 62 ngày |
| Biểu đồ cột | `resources/views/reports/revenue.blade.php` | Vẽ bằng CSS: chiều cao mỗi cột là phần trăm so với ngày cao nhất, không dùng thư viện |
| Báo cáo tồn kho | `ReportController::inventoryReport()` gọi `buildInventory()` | Sản phẩm có `current_stock <= safety_stock` xếp lên đầu; có bộ lọc chỉ hiện sản phẩm cần nhập thêm |
| Nút cộng, trừ số lượng | `public/js/pos.js`, hàm `changeQuantity()` | Không cho nhỏ hơn 1 và không cho vượt tồn kho |
| Nút chọn nhanh số tiền | `public/js/pos.js`, hàm `renderChips()` | Gợi ý: đúng tổng tiền, làm tròn lên 10.000, làm tròn lên 100.000, 500.000, 1.000.000 |

## 11. Những phần chưa làm (nên nói thẳng nếu thầy hỏi)

- Mã QR chuyển khoản mới là hình minh họa, chưa sinh mã VietQR thật.
- Chưa có tải ảnh sản phẩm.
- Form thêm, sửa nằm ngay trên trang chứ chưa phải hộp thoại như bản thiết kế Figma.
- Chưa có kiểm thử tự động.
