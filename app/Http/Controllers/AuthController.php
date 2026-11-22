<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
