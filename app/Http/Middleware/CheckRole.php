<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Chỉ cho đi tiếp nếu người dùng có một trong các vai trò được khai báo ở route.
     * Ví dụ: ->middleware('role:ADMIN,STOCKKEEPER') thì $roles = ['ADMIN', 'STOCKKEEPER'].
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Tài khoản đã bị khóa thì đăng xuất ngay.
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['username' => 'Tài khoản đã bị khóa.']);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403, 'Bạn không có quyền truy cập chức năng này.');
    }
}
