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
                @if (auth()->user()->role === 'ADMIN')
                    <a class="nav-item {{ request()->is('products') ? 'active' : '' }}" href="{{ url('/products') }}">Hàng hóa &amp; Tồn kho</a>
                    <a class="nav-item {{ request()->is('pos') ? 'active' : '' }}" href="{{ url('/pos') }}">Bán hàng (POS)</a>
                    <a class="nav-item {{ request()->is('pos/history') ? 'active' : '' }}" href="{{ url('/pos/history') }}">Lịch sử bán</a>
                @elseif (auth()->user()->role === 'STOCKKEEPER')
                    <a class="nav-item {{ request()->is('stock-imports/create') ? 'active' : '' }}" href="{{ url('/stock-imports/create') }}">Lập phiếu nhập kho</a>
                    <a class="nav-item {{ request()->is('stock-imports') ? 'active' : '' }}" href="{{ url('/stock-imports') }}">Lịch sử nhập kho</a>
                    <a class="nav-item {{ request()->is('products/search') ? 'active' : '' }}" href="{{ url('/products/search') }}">Tra cứu sản phẩm</a>
                @else
                    <a class="nav-item {{ request()->is('pos') ? 'active' : '' }}" href="{{ url('/pos') }}">Bán hàng (POS)</a>
                    <a class="nav-item {{ request()->is('pos/history') ? 'active' : '' }}" href="{{ url('/pos/history') }}">Lịch sử giao dịch</a>
                    <a class="nav-item {{ request()->is('products/search') ? 'active' : '' }}" href="{{ url('/products/search') }}">Tra cứu sản phẩm</a>
                @endif
            </div>
        </div>
        <div class="topbar-right">
            <span class="user-badge">{{ auth()->user()->full_name }} ({{ auth()->user()->employee_code }} - {{ auth()->user()->role }})</span>
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
