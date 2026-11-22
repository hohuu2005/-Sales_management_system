@extends('layouts.app')

@section('title', $title)

@section('content')
    {{-- View dùng cho hai màn hình:
         $manage = true  : Quản trị quản lý hàng hóa (có form thêm, sửa, ngừng bán).
         $manage = false : Tra cứu sản phẩm cho mọi vai trò (chỉ xem). --}}

    @if ($manage)
        @php
            $attrs = $editing && is_array($editing->attributes_json) ? $editing->attributes_json : [];
            $inv = $editing ? $editing->inventory : null;
        @endphp

        {{-- Form thêm mới hoặc sửa (khi địa chỉ có ?edit=<id>). Thẻ details để thu gọn form thêm mới. --}}
        <details class="card" style="margin-bottom:20px;" {{ ($editing || $errors->any()) ? 'open' : '' }}>
            <summary class="row-between" style="cursor:pointer;">
                <h3>{{ $editing ? 'Sửa hàng hóa ' . $editing->sku_code : '+ Thêm mới hàng hóa' }}</h3>
                <span class="muted">Bấm để mở hoặc thu gọn</span>
            </summary>

            @if ($editing)
                <form method="POST" action="{{ url('/products/' . $editing->id) }}">
                    @csrf
                    @method('PUT')
            @else
                <form method="POST" action="{{ url('/products') }}">
                    @csrf
            @endif

                <div class="form-grid three">
                    <div>
                        <label for="sku_code">Mã hàng hóa (SKU) *</label>
                        <input class="input" id="sku_code" name="sku_code" type="text" maxlength="50" required
                               value="{{ old('sku_code', $editing ? $editing->sku_code : '') }}">
                    </div>
                    <div>
                        <label for="name">Tên sản phẩm *</label>
                        <input class="input" id="name" name="name" type="text" maxlength="200" required
                               value="{{ old('name', $editing ? $editing->name : '') }}">
                    </div>
                    <div>
                        <label for="category_id">Danh mục *</label>
                        <select class="input" id="category_id" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) old('category_id', $editing ? $editing->category_id : '') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="unit">Đơn vị tính *</label>
                        <input class="input" id="unit" name="unit" type="text" maxlength="30" required placeholder="Cái, Chai, Hộp..."
                               value="{{ old('unit', $editing ? $editing->unit : '') }}">
                    </div>
                    <div>
                        <label for="cost_price">Giá vốn (đ) *</label>
                        <input class="input" id="cost_price" name="cost_price" type="number" min="0" step="1" required
                               value="{{ old('cost_price', $editing ? (int) $editing->cost_price : '') }}">
                    </div>
                    <div>
                        <label for="selling_price">Giá bán lẻ (đ) *</label>
                        <input class="input" id="selling_price" name="selling_price" type="number" min="1" step="1" required
                               value="{{ old('selling_price', $editing ? (int) $editing->selling_price : '') }}">
                    </div>
                    <div>
                        <label for="safety_stock">Định mức tồn kho tối thiểu *</label>
                        <input class="input" id="safety_stock" name="safety_stock" type="number" min="0" step="1" required
                               value="{{ old('safety_stock', $inv ? $inv->safety_stock : 10) }}">
                    </div>
                    <div>
                        <label for="shelf_location">Vị trí kệ</label>
                        <input class="input" id="shelf_location" name="shelf_location" type="text" maxlength="50" placeholder="Kệ A1 - Tầng 1"
                               value="{{ old('shelf_location', $inv ? $inv->shelf_location : '') }}">
                    </div>
                </div>

                <div class="box-attr">
                    <b>Thuộc tính theo ngành hàng (điền nếu có, để trống nếu không dùng)</b>
                    <div class="form-grid three">
                        <div>
                            <label for="attr_size">Kích cỡ</label>
                            <input class="input" id="attr_size" name="attr_size" type="text" maxlength="30" placeholder="S, M, L, 31..."
                                   value="{{ old('attr_size', $attrs['size'] ?? '') }}">
                        </div>
                        <div>
                            <label for="attr_color">Màu sắc</label>
                            <input class="input" id="attr_color" name="attr_color" type="text" maxlength="30"
                                   value="{{ old('attr_color', $attrs['color'] ?? '') }}">
                        </div>
                        <div>
                            <label for="attr_exp_date">Hạn sử dụng</label>
                            <input class="input" id="attr_exp_date" name="attr_exp_date" type="date"
                                   value="{{ old('attr_exp_date', $attrs['exp_date'] ?? '') }}">
                        </div>
                    </div>
                </div>

                @if ($editing)
                    <div class="check-row">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $editing->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                        <label for="is_active">Đang bán (bỏ chọn để ngừng bán)</label>
                    </div>
                @endif

                <div class="form-actions">
                    @if ($editing)
                        <a class="btn btn-outline" href="{{ url('/products') }}">Hủy bỏ</a>
                    @endif
                    <button type="submit" class="btn btn-blue">Lưu sản phẩm</button>
                </div>
            </form>
        </details>
    @endif

    <div class="container">
        @if ($manage)
            {{-- Cột trái: lọc theo danh mục --}}
            <div class="card col-cats">
                <div class="row-between">
                    <h3>Danh mục</h3>
                    <a class="btn btn-blue btn-small" href="{{ url('/categories') }}">Quản lý</a>
                </div>
                <a class="cat-link {{ $categoryId ? '' : 'active' }}" href="{{ url('/products') }}">Tất cả danh mục</a>
                @foreach ($categories as $category)
                    <a class="cat-link {{ $categoryId === $category->id ? 'active' : '' }}" href="{{ url('/products') }}?category_id={{ $category->id }}">
                        {{ $category->name }}
                        <small>{{ $category->products_count }} sản phẩm</small>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="card col-main">
            <h3 class="card-title">{{ $manage ? 'Danh sách hàng hóa & tồn kho' : 'Tra cứu sản phẩm' }}</h3>

            <form method="GET" action="{{ $manage ? url('/products') : url('/products/search') }}" class="pos-scan mt">
                @if ($manage && $categoryId)
                    <input type="hidden" name="category_id" value="{{ $categoryId }}">
                @endif
                <input class="input" type="text" name="{{ $manage ? 'q' : 'sku_code' }}" value="{{ $keyword }}" {{ $manage ? '' : 'autofocus' }}
                       placeholder="Nhập mã SKU hoặc tên sản phẩm">
                <button type="submit" class="btn btn-blue">Tìm kiếm</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Mã SKU</th>
                        <th>Tên hàng hóa</th>
                        <th>Danh mục</th>
                        <th>ĐVT</th>
                        @if ($manage)
                            <th class="num">Giá vốn</th>
                        @endif
                        <th class="num">Giá bán</th>
                        <th class="num">Tồn kho</th>
                        <th>Vị trí kệ</th>
                        <th>Thuộc tính</th>
                        <th>Trạng thái</th>
                        @if ($manage)
                            <th>Thao tác</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php
                            $a = is_array($product->attributes_json) ? $product->attributes_json : [];
                            $low = $product->inventory && $product->inventory->isLowStock();
                        @endphp
                        <tr class="{{ $product->is_active ? ($low ? 'row-low' : '') : 'row-off' }}">
                            <td><b>{{ $product->sku_code }}</b></td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category ? $product->category->name : '' }}</td>
                            <td>{{ $product->unit }}</td>
                            @if ($manage)
                                <td class="num">{{ number_format((float) $product->cost_price, 0, ',', '.') }} đ</td>
                            @endif
                            <td class="num"><b>{{ number_format((float) $product->selling_price, 0, ',', '.') }} đ</b></td>
                            <td class="num">
                                @if (! $product->inventory)
                                    <span class="badge badge-red">0</span>
                                @elseif ($low)
                                    <span class="badge badge-red">{{ $product->inventory->current_stock }} (sắp hết)</span>
                                @else
                                    <span class="badge badge-green">{{ $product->inventory->current_stock }}</span>
                                @endif
                            </td>
                            <td>{{ $product->inventory ? $product->inventory->shelf_location : '' }}</td>
                            <td>
                                @if (! empty($a['size']))
                                    <span class="tag">Size: {{ $a['size'] }}</span>
                                @endif
                                @if (! empty($a['color']))
                                    <span class="tag">Màu: {{ $a['color'] }}</span>
                                @endif
                                @if (! empty($a['exp_date']))
                                    <span class="tag">HSD: {{ strtotime($a['exp_date']) ? date('d/m/Y', strtotime($a['exp_date'])) : $a['exp_date'] }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($product->is_active)
                                    <span class="badge badge-blue">Đang bán</span>
                                @else
                                    <span class="badge badge-red">Ngừng bán</span>
                                @endif
                            </td>
                            @if ($manage)
                                <td>
                                    <a class="btn btn-outline btn-small" href="{{ url('/products') }}?edit={{ $product->id }}">Sửa</a>
                                    @if ($product->is_active)
                                        <form method="POST" action="{{ url('/products/' . $product->id) }}" class="inline-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-red btn-small">Xóa / Ngừng bán</button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="{{ $manage ? 11 : 9 }}">Không tìm thấy sản phẩm nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
