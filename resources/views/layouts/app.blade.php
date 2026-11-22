<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIMS') - Hệ thống Quản lý Bán hàng và Tồn kho</title>
    <link rel="stylesheet" href="{{ asset('css/sims.css') }}">
</head>
<body>
    <div class="topbar">
        <div class="brand">
            <span>SIMS</span>
            <div class="nav-links">
                {{-- Menu theo vai trò, đúng với sơ đồ use case --}}
                @if (auth()->user()->role === 'ADMIN')
                    <a class="nav-item {{ request()->is('products') || request()->is('categories') ? 'active' : '' }}" href="{{ url('/products') }}">Hàng hóa &amp; Danh mục</a>
                    <a class="nav-item {{ request()->is('users') ? 'active' : '' }}" href="{{ url('/users') }}">Nhân sự &amp; Phân quyền</a>
                    <a class="nav-item {{ request()->is('reports/*') ? 'active' : '' }}" href="{{ url('/reports/revenue') }}">Báo cáo &amp; Thống kê</a>
                @elseif (auth()->user()->role === 'STOCKKEEPER')
                    <a class="nav-item {{ request()->is('stock-imports/create') ? 'active' : '' }}" href="{{ url('/stock-imports/create') }}">Lập phiếu nhập kho</a>
                    <a class="nav-item {{ request()->is('stock-imports') ? 'active' : '' }}" href="{{ url('/stock-imports') }}">Lịch sử phiếu nhập</a>
                    <a class="nav-item {{ request()->is('reports/inventory') ? 'active' : '' }}" href="{{ url('/reports/inventory') }}">Cảnh báo tồn kho</a>
                @else
                    <a class="nav-item {{ request()->is('pos') ? 'active' : '' }}" href="{{ url('/pos') }}">Bán hàng (POS)</a>
                    <a class="nav-item {{ request()->is('pos/history') ? 'active' : '' }}" href="{{ url('/pos/history') }}">Lịch sử giao dịch</a>
                    <a class="nav-item {{ request()->is('products/search') ? 'active' : '' }}" href="{{ url('/products/search') }}">Tra cứu sản phẩm</a>
                @endif
            </div>
        </div>
        <div class="topbar-right">
            <span class="user-badge">{{ auth()->user()->full_name }} ({{ auth()->user()->employee_code }} - {{ auth()->user()->role }})</span>

            {{-- Hộp Đổi mật khẩu: bấm để mở, dùng thẻ details nên không cần JavaScript --}}
            <details class="pw-box">
                <summary class="btn-logout">Đổi mật khẩu</summary>
                <form method="POST" action="{{ url('/password') }}" class="pw-form">
                    @csrf
                    <label for="current_password">Mật khẩu hiện tại *</label>
                    <input class="input" id="current_password" name="current_password" type="password" required>
                    <label for="new_password">Mật khẩu mới (tối thiểu 8 ký tự) *</label>
                    <input class="input" id="new_password" name="new_password" type="password" minlength="8" required>
                    <label for="new_password_confirmation">Nhập lại mật khẩu mới *</label>
                    <input class="input" id="new_password_confirmation" name="new_password_confirmation" type="password" minlength="8" required>
                    <button type="submit" class="btn btn-blue">Cập nhật mật khẩu</button>
                </form>
            </details>

            <form method="POST" action="{{ url('/logout') }}">
                @csrf
                <button type="submit" class="btn-logout">Đăng xuất</button>
            </form>
        </div>
    </div>

    <div class="page">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

    @yield('scripts')
</body>
</html>
