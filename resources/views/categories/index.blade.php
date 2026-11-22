@extends('layouts.app')

@section('title', 'Quản lý danh mục')

@section('content')
    <div class="container">
        {{-- Cột trái: form thêm mới hoặc sửa (khi địa chỉ có ?edit=<id>) --}}
        <div class="card col-side">
            @if ($editing)
                <h3 class="card-title">Sửa danh mục</h3>
                <form method="POST" action="{{ url('/categories/' . $editing->id) }}">
                    @csrf
                    @method('PUT')
            @else
                <h3 class="card-title">Thêm mới danh mục</h3>
                <form method="POST" action="{{ url('/categories') }}">
                    @csrf
            @endif

                <label for="name">Tên danh mục *</label>
                <input class="input" id="name" name="name" type="text" maxlength="100" required
                       value="{{ old('name', $editing ? $editing->name : '') }}">

                <label for="description">Mô tả</label>
                <input class="input" id="description" name="description" type="text" maxlength="1000"
                       value="{{ old('description', $editing ? $editing->description : '') }}">

                <p class="muted mt">Đường dẫn (slug) được hệ thống tự sinh từ tên danh mục.</p>

                <div class="form-actions">
                    @if ($editing)
                        <a class="btn btn-outline" href="{{ url('/categories') }}">Hủy bỏ</a>
                        <button type="submit" class="btn btn-blue">Lưu danh mục</button>
                    @else
                        <button type="submit" class="btn btn-green">+ Thêm danh mục</button>
                    @endif
                </div>
            </form>
        </div>

        {{-- Cột phải: danh sách danh mục --}}
        <div class="card col-main">
            <div class="row-between card-title">
                <h3>Danh sách danh mục</h3>
                <a class="btn btn-outline" href="{{ url('/products') }}">Quay lại Hàng hóa</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Tên danh mục</th>
                        <th>Đường dẫn (slug)</th>
                        <th>Mô tả</th>
                        <th class="num">Số sản phẩm</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><b>{{ $category->name }}</b></td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->description }}</td>
                            <td class="num">{{ $category->products_count }}</td>
                            <td>
                                <a class="btn btn-outline btn-small" href="{{ url('/categories') }}?edit={{ $category->id }}">Sửa</a>
                                <form method="POST" action="{{ url('/categories/' . $category->id) }}" class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-red btn-small">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="5">Chưa có danh mục nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
