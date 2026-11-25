<?php

/*
 * Ngoại lệ dùng cho lỗi nghiệp vụ POS mà thu ngân được phép xem, ví dụ thiếu tiền hoặc hết hàng.
 * Được ném từ PosOrderService/Inventory và bắt bằng HTTP 422 trong PosOrderController::checkout.
 * Xem bảng thông báo tại docs/05-bang-tra-cuu-loi.md.
 */

namespace App\Exceptions;

use Exception;

class LoiBanHangException extends Exception
{
}
