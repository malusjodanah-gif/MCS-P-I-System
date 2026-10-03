<?php

use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ItemBranchController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->middleware('guest');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ----- Purchasing (Admin + Purchasing Officer) -----
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::middleware('role:Purchasing Officer')->group(function () {
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');

    Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::middleware('role:Purchasing Officer')->group(function () {
        Route::get('/receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
        Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
        Route::get('/receipts/{receipt}/edit', [ReceiptController::class, 'edit'])->name('receipts.edit');
        Route::put('/receipts/{receipt}', [ReceiptController::class, 'update'])->name('receipts.update');
        Route::delete('/receipts/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');
    });

    // ----- Inventory (Admin + PO + IO) -----
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/item-branch', [ItemBranchController::class, 'index'])->name('itembranch.index');
    Route::get('/issues', [IssueController::class, 'index'])->name('issues.index');
    Route::get('/adjustments', [AdjustmentController::class, 'index'])->name('adjustments.index');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');

    Route::middleware('role:Purchasing Officer,Inventory Officer')->group(function () {
        // Items
        Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');

        // Item Branch (ITEMBRANCH)
        Route::get('/item-branch/create', [ItemBranchController::class, 'create'])->name('itembranch.create');
        Route::post('/item-branch', [ItemBranchController::class, 'store'])->name('itembranch.store');
        Route::get('/item-branch/{itemId}/{branchId}/edit', [ItemBranchController::class, 'edit'])->name('itembranch.edit');
        Route::put('/item-branch/{itemId}/{branchId}', [ItemBranchController::class, 'update'])->name('itembranch.update');
        Route::delete('/item-branch/{itemId}/{branchId}', [ItemBranchController::class, 'destroy'])->name('itembranch.destroy');

        // Customers
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        // Assets
        Route::get('/assets/create', [AssetController::class, 'create'])->name('assets.create');
        Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
        Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
        Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
    });

    // Specification v4 grants Purchasing Officers full inventory access.
    Route::middleware('role:Purchasing Officer,Inventory Officer')->group(function () {
        Route::get('/issues/create', [IssueController::class, 'create'])->name('issues.create');
        Route::post('/issues', [IssueController::class, 'store'])->name('issues.store');
        Route::get('/issues/{issue}/edit', [IssueController::class, 'edit'])->name('issues.edit');
        Route::put('/issues/{issue}', [IssueController::class, 'update'])->name('issues.update');
        Route::delete('/issues/{issue}', [IssueController::class, 'destroy'])->name('issues.destroy');

        Route::get('/adjustments/create', [AdjustmentController::class, 'create'])->name('adjustments.create');
        Route::post('/adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');
        Route::get('/adjustments/{adjustment}/edit', [AdjustmentController::class, 'edit'])->name('adjustments.edit');
        Route::put('/adjustments/{adjustment}', [AdjustmentController::class, 'update'])->name('adjustments.update');
        Route::delete('/adjustments/{adjustment}', [AdjustmentController::class, 'destroy'])->name('adjustments.destroy');
    });

    // ----- Admin only -----
    Route::middleware('role:Administrator')->group(function () {
        // Note: EnsureRole allows Administrator for any role:X middleware,
        // but we also need exclusive admin routes — use a dedicated check
    });

    // Branches & Users: Admin only (Purchasing Officer role string won't match Administrator-only intent)
    // EnsureRole grants Administrator access to all role middleware — so restrict via controller or custom.
    // We'll use role:Administrator which only matches Administrator (see middleware: Admin OR listed roles).
    Route::middleware('role:Administrator')->group(function () {
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Reports — all authenticated
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/stock', [ReportController::class, 'stockLevels'])->name('reports.stock');
    Route::get('/reports/reorder', [ReportController::class, 'reorder'])->name('reports.reorder');
    Route::get('/reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
    Route::get('/reports/issues', [ReportController::class, 'issues'])->name('reports.issues');
});
