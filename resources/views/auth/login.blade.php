<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - SIMS</title>
    <link rel="stylesheet" href="{{ asset('css/sims.css') }}">
</head>
<body>
    <div class="login-page">
        <div class="card login-card">
            <h2>SIMS - KIOT QUẢN LÝ</h2>
            <p class="sub">Hệ thống Quản lý Bán hàng &amp; Tồn kho</p>

            @if ($errors->any())
                <div class="alert alert-error mt">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ url('/login') }}">
                @csrf

                <label for="username">Tên đăng nhập</label>
                <input class="input" id="username" name="username" type="text" value="{{ old('username') }}" autofocus required>

                <label for="password">Mật khẩu</label>
                <input class="input" id="password" name="password" type="password" required>

                <button type="submit" class="btn btn-blue btn-big">ĐĂNG NHẬP</button>
            </form>

            <p class="muted mt">Sau khi đăng nhập, hệ thống tự chuyển tới màn hình theo vai trò: Admin, Thủ kho hoặc Bán hàng.</p>
        </div>
    </div>
</body>
</html>
