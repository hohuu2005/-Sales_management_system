<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Hiển thị form đăng nhập.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Xác thực username + mật khẩu, tạo session, điều hướng theo vai trò.
     * Tài khoản bị khóa (is_active = false) không đăng nhập được.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $credentials = [
            'username' => $data['username'],
            'password' => $data['password'],
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['username' => 'Sai tên đăng nhập hoặc mật khẩu, hoặc tài khoản đã bị khóa.'])
                ->onlyInput('username');
        }

        // Đổi mã session sau khi đăng nhập để chống chiếm phiên (session fixation).
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Hủy session và quay về trang đăng nhập.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Đổi mật khẩu cá nhân: kiểm tra mật khẩu cũ, lưu mật khẩu mới dạng mã hóa Bcrypt.
     */
    public function changePassword(Request $request)
    {
        $data = $request->validate($this->passwordRules(), [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'new_password.confirmed' => 'Hai lần nhập mật khẩu mới không giống nhau.',
            'new_password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ]);

        $user = $request->user();

        if (! $this->applyNewPassword($user, $data['current_password'], $data['new_password'])) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }

        return back()->with('success', 'Đổi mật khẩu thành công.');
    }

    /**
     * Quy tắc kiểm tra dữ liệu của form đổi mật khẩu.
     */
    protected function passwordRules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ];
    }

    /**
     * So mật khẩu cũ với password_hash; đúng thì lưu mật khẩu mới. Trả về false nếu mật khẩu cũ sai.
     */
    protected function applyNewPassword($user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password_hash)) {
            return false;
        }

        $user->update(['password_hash' => Hash::make($newPassword)]);

        return true;
    }
}
