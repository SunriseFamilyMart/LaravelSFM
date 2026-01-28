<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Inventory\Auth\LoginController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\ProductController;

// Inventory Auth Routes
Route::prefix('inventory')->name('inventory.')->group(function () {

    // Login Routes
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('auth.login');
    Route::post('login', [LoginController::class, 'login'])->name('auth.login.submit');
    Route::post('logout', [LoginController::class, 'logout'])->name('auth.logout');

    // Dashboard / Protected Routes
    Route::middleware('inventory.auth')->group(function () {
        Route::get('dashboard', [InventoryController::class, 'index'])->name('dashboard');
        Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');

        Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('purchases/store', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/reports/category-sales', [InventoryController::class, 'categoryWiseSalesReport'])
            ->name('reports.category_sales');
        Route::get('/reports/category-sales/export', [InventoryController::class, 'exportCategorySales'])
            ->name('reports.category_sales.export');
        Route::get('/reports/stock/export', [InventoryController::class, 'stockReportExport'])
            ->name('stock.export');
        Route::get('/reports/stock', [InventoryController::class, 'stockReport'])
            ->name('stock.report');
        Route::resource('suppliers', SupplierController::class);
        Route::get('/products', [InventoryController::class, 'pindex'])->name('products.index');
        Route::get('/products/{id}', [InventoryController::class, 'pshow'])->name('products.show');
        Route::get('/admin/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/admin/products/store', [ProductController::class, 'store'])->name('products.store');
        Route::get('/categories/{id}/subcategories', [ProductController::class, 'getSubcategories'])
            ->name('categories.subcategories');

    });
});
