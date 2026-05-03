<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrdersController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/auth/firebase', [AuthController::class, 'firebaseLogin'])->name('auth.firebase');

Route::middleware('firebase.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/orders', [OrdersController::class, 'index'])->name('orders');
        Route::get('/orders/history', [OrdersController::class, 'history'])->name('orders.history');
        Route::patch('/orders/{order}/status', [OrdersController::class, 'updateStatus'])->name('orders.updateStatus');
    });

    Route::get('/kitchen/dashboard', function () {
        return Inertia::render('Kitchen/Dashboard');
    })->name('kitchen.dashboard');

    Route::get('/finance/dashboard', function () {
        return Inertia::render('Finance/Dashboard');
    })->name('finance.dashboard');

    Route::get('/waiter/dashboard', function () {
        return Inertia::render('Waiter/Dashboard');
    })->name('waiter.dashboard');
});
