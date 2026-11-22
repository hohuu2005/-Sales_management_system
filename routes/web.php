<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PosOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockImportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// ----- Đăng nhập / đăng xuất -----
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth'])->group(function () {

    // Sau khi đăng nhập, mỗi vai trò được đưa về màn hình làm việc chính của mình.
    Route::get('/dashboard', function () {
        return match (Auth::user()->role) {
            'ADMIN' => redirect('/products'),
            'STOCKKEEPER' => redirect('/stock-imports/create'),
            'SALES' => redirect('/pos'),
            default => redirect()->route('login'),
        };
    })->name('dashboard');

    // Mọi vai trò đều được tra cứu sản phẩm.
    Route::get('/products/search', [ProductController::class, 'search']);

    // (Đã thiết kế, CHƯA cài đặt hàm changePassword trong AuthController.)
    Route::post('/password', [AuthController::class, 'changePassword']);

    // ----- ADMIN -----
    Route::middleware(['role:ADMIN'])->group(function () {
        Route::get('/products', [ProductController::class, 'index']);

        // CÁC ROUTE DƯỚI ĐÂY ĐÃ THIẾT KẾ NHƯNG CHƯA CÀI ĐẶT HÀM TRONG CONTROLLER.
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        Route::get('/reports/revenue', [ReportController::class, 'revenueReport']);
    });

    Route::middleware(['role:ADMIN,STOCKKEEPER'])->group(function () {
        Route::get('/reports/inventory', [ReportController::class, 'inventoryReport']);
    });

    // ----- THỦ KHO: nhập kho -----
    Route::middleware(['role:STOCKKEEPER'])->group(function () {
        Route::get('/stock-imports/create', [StockImportController::class, 'create']);
        Route::post('/stock-imports', [StockImportController::class, 'storeImport']);
        Route::get('/stock-imports', [StockImportController::class, 'importHistory']);
    });

    // ----- BÁN HÀNG: màn hình POS -----
    Route::middleware(['role:SALES,ADMIN'])->group(function () {
        Route::get('/pos', [PosOrderController::class, 'posScreen']);
        Route::get('/pos/scan', [PosOrderController::class, 'scanBarcode']);
        Route::post('/pos/checkout', [PosOrderController::class, 'checkout']);
        Route::get('/pos/history', [PosOrderController::class, 'myHistory']);
    });
});
