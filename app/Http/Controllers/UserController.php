<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Danh sách tài khoản nhân viên. Thêm ?edit=<id> vào địa chỉ để mở form sửa.
     */
    public function index(Request $request)
    {
        $editing = $request->query('edit') ? User::find($request->query('edit')) : null;

        return view('users.index', [
            'users' => User::orderBy('id')->get(),
            'editing' => $editing,
        ]);
    }

    /**
     * Tạo tài khoản mới, mật khẩu được mã hóa Bcrypt trước khi lưu.
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->userRules(null), $this->messages());

        $user = $this->createUser($data);

        return redirect('/users')->with('success', "Đã tạo tài khoản {$user->username}.");
    }

    /**
     * Cập nhật thông tin nhân viên; có thể mở khóa hoặc khóa tài khoản (trừ tài khoản của chính mình).
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate($this->userRules($user->id), $this->messages());

        $this->updateUser($user, $data, (int) Auth::id(), $request->boolean('is_active'));

        return redirect('/users')->with('success', "Đã cập nhật tài khoản {$user->username}.");
    }

    /**
     * Phân quyền: đổi vai trò của một tài khoản (không được tự đổi vai trò của mình).
     */
    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'role' => ['required', Rule::in(['ADMIN', 'STOCKKEEPER', 'SALES'])],
        ], ['role.in' => 'Vai trò không hợp lệ.']);

        if (! $this->changeRole($user, $data['role'], (int) Auth::id())) {
            return back()->withErrors(['role' => 'Không thể tự đổi vai trò của chính mình.']);
        }

        return redirect('/users')->with('success', "Đã lưu vai trò {$user->role} cho tài khoản {$user->username}.");
    }

    /**
     * "Xóa" người dùng = khóa tài khoản (is_active = false) để giữ lịch sử hóa đơn, phiếu nhập.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (! $this->lockUser($user, (int) Auth::id())) {
            return back()->withErrors(['user' => 'Không thể tự khóa tài khoản của chính mình.']);
        }

        return redirect('/users')->with('success', "Đã khóa tài khoản {$user->username}.");
    }

    // ---------------------------------------------------------------------
    // Các hàm xử lý bên dưới không phụ thuộc vào Request nên dễ kiểm thử riêng.
    // ---------------------------------------------------------------------

    /**
     * Quy tắc kiểm tra dữ liệu. $ignoreId = null là thêm mới; khác null là sửa tài khoản có id đó.
     */
    protected function userRules(?int $ignoreId): array
    {
        $rules = [
            'employee_code' => ['required', 'string', 'max:20', Rule::unique('users', 'employee_code')->ignore($ignoreId)],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($ignoreId)],
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($ignoreId)],
            'phone' => ['nullable', 'string', 'max:15'],
            'work_shift' => ['nullable', 'string', 'max:50'],
        ];

        if ($ignoreId === null) {
            $rules['role'] = ['required', Rule::in(['ADMIN', 'STOCKKEEPER', 'SALES'])];
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'employee_code.required' => 'Vui lòng nhập mã nhân viên.',
            'employee_code.unique' => 'Mã nhân viên đã tồn tại.',
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.unique' => 'Tên đăng nhập đã tồn tại.',
            'username.alpha_dash' => 'Tên đăng nhập chỉ gồm chữ, số, dấu gạch ngang và gạch dưới.',
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã được dùng cho tài khoản khác.',
            'role.required' => 'Vui lòng chọn vai trò.',
            'role.in' => 'Vai trò không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu khởi tạo.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
        ];
    }

    protected function createUser(array $data): User
    {
        return User::create([
            'employee_code' => $data['employee_code'],
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'work_shift' => $data['work_shift'] ?? null,
            'is_active' => true,
        ]);
    }

    protected function updateUser(User $user, array $data, int $currentUserId, bool $isActive): User
    {
        $changes = [
            'employee_code' => $data['employee_code'],
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'work_shift' => $data['work_shift'] ?? null,
        ];

        // Không cho tự khóa chính mình qua form sửa.
        if ((int) $user->id !== $currentUserId) {
            $changes['is_active'] = $isActive;
        }

        $user->update($changes);

        return $user;
    }

    protected function changeRole(User $user, string $role, int $currentUserId): bool
    {
        if ((int) $user->id === $currentUserId) {
            return false;
        }

        $user->update(['role' => $role]);

        return true;
    }

    protected function lockUser(User $user, int $currentUserId): bool
    {
        if ((int) $user->id === $currentUserId) {
            return false;
        }

        $user->update(['is_active' => false]);

        return true;
    }
}
