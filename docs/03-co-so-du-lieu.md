# Cơ sở dữ liệu SIMS

**Mục đích tài liệu**: Giải thích các bảng liên quan bán hàng và cách đọc dữ liệu.

**Ai nên đọc**: Người phát triển backend, người viết báo cáo và người kiểm thử dữ liệu.

**Cập nhật lần cuối**: 25/11/2026.

**File liên quan**: `database/migrations/`, `app/Models/Order.php`, `app/Models/OrderDetail.php`, `app/Models/Inventory.php`, `app/Models/Product.php`.

## Sơ đồ quan hệ

```mermaid
erDiagram
    USERS ||--o{ ORDERS : cashier
    USERS ||--o{ STOCK_IMPORTS : stockkeeper
    CATEGORIES ||--o{ PRODUCTS : contains
    USERS ||--o{ CATEGORIES : creates
    PRODUCTS ||--|| INVENTORIES : has
    ORDERS ||--o{ ORDER_DETAILS : contains
    PRODUCTS ||--o{ ORDER_DETAILS : sold_as
    STOCK_IMPORTS ||--o{ STOCK_IMPORT_DETAILS : contains
    PRODUCTS ||--o{ STOCK_IMPORT_DETAILS : imported_as

    USERS {
      bigint id PK
      string role
      string employee_code
      boolean is_active
    }
    CATEGORIES {
      bigint id PK
      bigint created_by FK
      string name
    }
    PRODUCTS {
      bigint id PK
      bigint category_id FK
      string sku_code UK
      decimal cost_price
      decimal selling_price
      boolean is_active
    }
    INVENTORIES {
      bigint id PK
      bigint product_id FK UK
      integer current_stock
      integer safety_stock
    }
    ORDERS {
      bigint id PK
      string order_code UK
      bigint cashier_id FK
      decimal total_amount
      decimal customer_paid
      decimal change_amount
      string payment_method
      string status
      datetime created_at
    }
    ORDER_DETAILS {
      bigint id PK
      bigint order_id FK
      bigint product_id FK
      integer quantity
      decimal unit_price
      decimal unit_cost
      decimal subtotal
    }

    STOCK_IMPORTS {
      bigint id PK
      string import_code UK
      bigint stockkeeper_id FK
      decimal total_cost
      datetime created_at
    }

    STOCK_IMPORT_DETAILS {
      bigint id PK
      bigint stock_import_id FK
      bigint product_id FK
      integer import_quantity
      decimal import_price
      decimal subtotal
    }
```

## Bảng orders

Nguồn: `database/migrations/2026_10_28_162103_create_orders_table.php` và `Order::casts`.

| Cột | Kiểu | Ý nghĩa | Ràng buộc |
|---|---|---|---|
| `id` | bigint | Khóa chính | Tự tăng |
| `order_code` | varchar(30) | Mã hóa đơn hiển thị | Unique |
| `cashier_id` | bigint | Thu ngân tạo hóa đơn | Foreign key tới users, restrict khi xóa |
| `total_amount` | decimal(15,2) | Tổng tiền | Không cho null |
| `customer_paid` | decimal(15,2) | Tiền khách đưa | Không cho null |
| `change_amount` | decimal(15,2) | Tiền thừa | Mặc định 0 |
| `payment_method` | enum | CASH hoặc VIETQR | Không cho null |
| `status` | enum | COMPLETED hoặc CANCELLED | Mặc định COMPLETED |
| `note` | varchar(255) | Ghi chú hóa đơn | Nullable, POS hiện chưa dùng |
| `created_at` | timestamp | Thời điểm tạo | Index riêng, nằm trong composite `(created_at,status)` và có chỉ mục `(cashier_id,created_at)` cho lịch sử POS |

## Bảng order_details

Nguồn: `database/migrations/2026_10_28_162114_create_order_details_table.php`, migration unit_cost và `app/Models/OrderDetail.php`.

| Cột | Kiểu | Ý nghĩa | Ràng buộc |
|---|---|---|---|
| `id` | bigint | Khóa chính | Tự tăng |
| `order_id` | bigint | Thuộc hóa đơn nào | Foreign key, cascade khi xóa order |
| `product_id` | bigint | Sản phẩm đã bán | Foreign key, restrict khi xóa sản phẩm |
| `quantity` | unsigned integer | Số lượng bán | Không cho phép giá trị âm ở schema unsigned |
| `unit_price` | decimal(15,2) | Giá bán chốt lúc bán | Không cho null |
| `unit_cost` | decimal(15,2) | Giá vốn chốt lúc bán | Mặc định 0; POS hiện ghi từ product.cost_price |
| `subtotal` | decimal(15,2) | unit_price nhân quantity | Không cho null |

## Bảng inventories

Nguồn: `database/migrations/2026_10_28_162042_create_inventories_table.php`.

| Cột | Kiểu | Ý nghĩa | Ràng buộc |
|---|---|---|---|
| `id` | bigint | Khóa chính | Tự tăng |
| `product_id` | bigint | Sản phẩm tương ứng | Unique, foreign key tới products, cascade |
| `current_stock` | unsigned integer | Tồn kho hiện tại | Mặc định 0 |
| `safety_stock` | unsigned integer | Định mức cảnh báo | Mặc định 10 |
| `shelf_location` | varchar(50) | Vị trí kệ | Nullable |
| `updated_at` | timestamp | Lần cập nhật gần nhất | useCurrent/useCurrentOnUpdate |

## Bảng products

Nguồn: `database/migrations/2026_10_28_162027_create_products_table.php`.

| Cột | Kiểu | Ý nghĩa | Ràng buộc |
|---|---|---|---|
| `id` | bigint | Khóa chính | Tự tăng |
| `sku_code` | varchar(50) | Mã SKU | Unique và index |
| `category_id` | bigint | Danh mục | Foreign key, restrict |
| `name` | varchar(200) | Tên hàng | Index |
| `unit` | varchar(30) | Đơn vị tính | Không cho null |
| `cost_price` | decimal(15,2) | Giá vốn hiện tại | Mặc định 0 |
| `selling_price` | decimal(15,2) | Giá bán hiện tại | Không cho null |
| `image_url` | varchar(255) | Đường dẫn ảnh | Nullable, chưa triển khai upload |
| `attributes_json` | json | Size, màu, hạn sử dụng | Nullable |
| `is_active` | boolean | Đang bán hay ngừng bán | Mặc định true |

## Ba loại giá cần phân biệt

- `unit_price`: giá bán đã chốt trong từng dòng hóa đơn; không đổi khi product.selling_price thay đổi sau này.
- `unit_cost`: giá vốn đã chốt trong từng dòng hóa đơn khi POS thanh toán.
- `products.cost_price`: giá vốn hiện tại. Mỗi phiếu nhập có thể cập nhật giá này; không đại diện giá vốn tại thời điểm hóa đơn cũ.

Hóa đơn tạo trước migration `unit_cost` có giá trị mặc định 0 vì hệ thống không thể biết giá vốn quá khứ.

## SQL doanh thu theo ngày

Câu lệnh dưới chạy được trên SQLite và MySQL:

```sql
SELECT
    DATE(created_at) AS ngay,
    COUNT(*) AS so_hoa_don,
    SUM(total_amount) AS doanh_thu
FROM orders
WHERE status = 'COMPLETED'
  AND DATE(created_at) BETWEEN '2026-11-01' AND '2026-11-25'
GROUP BY DATE(created_at)
ORDER BY ngay;
```

## SQL lợi nhuận gộp theo ngày

Công thức tạm tính: doanh thu trừ số lượng nhân giá vốn chốt trong dòng hóa đơn.

```sql
SELECT
    DATE(o.created_at) AS ngay,
    SUM(d.quantity * d.unit_price) AS doanh_thu,
    SUM(d.quantity * d.unit_cost) AS gia_von,
    SUM(d.quantity * (d.unit_price - d.unit_cost)) AS loi_nhuan_gop
FROM orders o
JOIN order_details d ON d.order_id = o.id
WHERE o.status = 'COMPLETED'
  AND o.created_at >= '2026-11-01'
  AND o.created_at < '2026-11-26'
GROUP BY DATE(o.created_at)
ORDER BY ngay;
```

Lưu ý: dòng hóa đơn cũ có `unit_cost=0` nên lợi nhuận gộp của dòng đó bị tính cao hơn thực tế.
