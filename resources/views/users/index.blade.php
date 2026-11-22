@extends('layouts.app')

@section('title', 'Nhân sự & Phân quyền')

@section('content')
    @php
        $roleNames = ['ADMIN' => 'Quản trị', 'STOCKKEEPER' => 'Thủ kho', 'SALES' => 'Bán hàng'];
    @endphp

    {{-- Form thêm mới hoặc sửa (khi địa chỉ có ?edit=<id>) --}}
    <div class="card">
        @if ($editing)
            <h3 class="card-title">Sửa tài khoản {{ $editing->username }}</h3>
            <form method="POST" action="{{ url('/users/' . $editing->id) }}">
                @csrf
                @method('PUT')
        @else
            <h3 class="card-title">Thêm mới tài khoản nhân viên</h3>
            <form method="POST" action="{{ url('/users') }}">
                @csrf
        @endif

            <div class="form-grid three">
                <div>
                    <label for="employee_code">Mã nhân viên *</label>
                    <input class="input" id="employee_code" name="employee_code" type="text" maxlength="20" required
                           value="{{ old('employee_code', $editing ? $editing->employee_code : '') }}">
                </div>
                <div>
                    <label for="username">Tên đăng nhập *</label>
                    <input class="input" id="username" name="username" type="text" maxlength="50" required
                           value="{{ old('username', $editing ? $editing->username : '') }}">
                </div>
                <div>
                    <label for="full_name">Họ và tên *</label>
                    <input class="input" id="full_name" name="full_name" type="text" maxlength="100" required
                           value="{{ old('full_name', $editing ? $editing->full_name : '') }}">
                </div>
                <div>
                    <label for="email">Email *</label>
                    <input class="input" id="email" name="email" type="email" maxlength="100" required
                           value="{{ old('email', $editing ? $editing->email : '') }}">
                </div>
                <div>
                    <label for="phone">Số điện thoại</label>
                    <input class="input" id="phone" name="phone" type="text" maxlength="15"
                           value="{{ old('phone', $editing ? $editing->phone : '') }}">
                </div>
                <div>
                    <label for="work_shift">Ca làm việc</label>
                    <input class="input" id="work_shift" name="work_shift" type="text" maxlength="50" placeholder="Ví dụ: Ca sáng"
                           value="{{ old('work_shift', $editing ? $editing->work_shift : '') }}">
                </div>

                @if (! $editing)
                    <div>
                        <label for="role">Vai trò *</label>
                        <select class="input" id="role" name="role" required>
                            @foreach ($roleNames as $value => $label)
                                <option value="{{ $value }}" {{ old('role', 'SALES') === $value ? 'selected' : '' }}>{{ $label }} ({{ $value }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="password">Mật khẩu khởi tạo (tối thiểu 8 ký tự) *</label>
                        <input class="input" id="password" name="password" type="password" minlength="8" required>
                    </div>
                @endif
            </div>

            @if ($editing && $editing->id !== auth()->user()->id)
                <div class="check-row">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $editing->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                    <label for="is_active">Tài khoản đang hoạt động (bỏ chọn để khóa)</label>
                </div>
            @endif

            <div class="form-actions">
                @if ($editing)
                    <a class="btn btn-outline" href="{{ url('/users') }}">Hủy bỏ</a>
                    <button type="submit" class="btn btn-blue">Lưu thay đổi</button>
                @else
                    <button type="submit" class="btn btn-green">+ Tạo tài khoản</button>
                @endif
            </div>
        </form>
    </div>

    <div class="card">
        <h3 class="card-title">Danh sách tài khoản nhân viên</h3>

        <table>
            <thead>
                <tr>
                    <th>Mã NV</th>
                    <th>Tên đăng nhập</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Ca làm việc</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @php $isSelf = $user->id === auth()->user()->id; @endphp
                    <tr class="{{ $user->is_active ? '' : 'row-off' }}">
                        <td><b>{{ $user->employee_code }}</b></td>
                        <td>{{ $user->username }}</td>
                        <td>
                            <b>{{ $user->full_name }}</b>
                            @if ($isSelf)
                                <span class="badge badge-blue">Bạn</span>
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone }}</td>
                        <td>{{ $user->work_shift }}</td>
                        <td>
                            @if ($isSelf)
                                <span class="badge badge-blue">{{ $roleNames[$user->role] ?? $user->role }}</span>
                            @else
                                {{-- Phân quyền: đổi vai trò rồi bấm Lưu vai trò --}}
                                <form method="POST" action="{{ url('/users/' . $user->id . '/role') }}" class="inline-form">
                                    @csrf
                                    @method('PUT')
                                    <select class="input" name="role">
                                        @foreach ($roleNames as $value => $label)
                                            <option value="{{ $value }}" {{ $user->role === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-blue btn-small">Lưu vai trò</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            @if ($user->is_active)
                                <span class="badge badge-green">Đang hoạt động</span>
                            @else
                                <span class="badge badge-red">Đã khóa</span>
                            @endif
                        </td>
                        <td>
                            <a class="btn btn-outline btn-small" href="{{ url('/users') }}?edit={{ $user->id }}">Sửa</a>
                            @if ($isSelf)
                                <span class="muted">Không thể tự khóa</span>
                            @elseif ($user->is_active)
                                <form method="POST" action="{{ url('/users/' . $user->id) }}" class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-red btn-small">Khóa tài khoản</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
