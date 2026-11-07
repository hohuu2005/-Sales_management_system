<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockImportController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');


Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
    $role = Auth::user()->role;
    return match ($role) {
        'ADMIN' => redirect('/products'),
        'STOCKKEEPER' => redirect('/stock-imports/create'),
        'SALES' => redirect('/pos'),
        default => redirect('/login'),
    };
    
});


Route::post('/password', [AuthController::class, 'changePassword']);
Route::get('/products/search', [ProductController::class, 'search']);


Route::middleware(['role:ADMIN'])->group(function () {
    
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    Route::get('/products', [ProductController::class, 'index']);
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

Route::middleware(['role:STOCKKEEPER'])->group(function () {
    Route::get('/stock-imports/create', [StockImportController::class, 'create']);
    Route::post('/stock-imports', [StockImportController::class, 'storeImport']);
    Route::get('/stock-imports', [StockImportController::class, 'importHistory']);
});
Route::middleware(['role:SALES,ADMIN'])->group(function () {
    Route::get('/pos', [ProductController::class, 'posScreen']);
    Route::get('/pos/scan', [ProductController::class, 'scanBarcode']);
    Route::post('/pos/checkout', [ProductController::class, 'checkout']);
    Route::get('/pos/history', [ProductController::class, 'myHistory']);
});
});
