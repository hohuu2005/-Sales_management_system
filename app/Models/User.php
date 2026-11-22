<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    // Bảng users lưu mật khẩu ở cột password_hash (không phải cột password mặc định của Laravel).
    protected $authPasswordName = 'password_hash';

    protected $fillable = [
        'employee_code',
        'username',
        'password_hash',
        'full_name',
        'email',
        'phone',
        'role',
        'work_shift',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Trả về mật khẩu đã mã hóa để Laravel Auth đối chiếu khi đăng nhập.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    /**
     * Kiểm tra người dùng có đúng vai trò hay không (ADMIN / STOCKKEEPER / SALES).
     * Middleware CheckRole dùng hàm này.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'cashier_id');
    }

    public function stockImports(): HasMany
    {
        return $this->hasMany(StockImport::class, 'stockkeeper_id');
    }
}
