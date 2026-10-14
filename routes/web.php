<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ClothTypeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\IncomeCategoryController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\MeasurementPartController;
use App\Http\Controllers\Auth\RecoverPasswordController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['guest']], function () {
    Route::get('login', [LoginController::class, 'index'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('recover-password', [RecoverPasswordController::class, 'index'])->name('reset-password');
    Route::post('recover-password', [RecoverPasswordController::class, 'resetPassword']);
});

Route::group(['middleware' => ['auth']], function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('logout', [LogoutController::class, 'index'])->name('logout');

    Route::get('user-profile', [UserController::class, 'profile'])->name('user-profile');
    Route::put('user-profile', [UserController::class, 'updateProfile']);
    Route::post('user-profile', [UserController::class, 'updatePassword']);

    // Orders: readable by admin and by the employee the order is assigned to
    // (authorisation is enforced inside OrdersController).
    Route::get('orders/{order}', [OrdersController::class, 'show'])->name('order.show');
    Route::post('orders/{order}/status', [OrdersController::class, 'updateStatus'])->name('order.status');
    Route::get('orders/{order}/slip', [OrdersController::class, 'slip'])->name('order.slip');
    Route::get('orders/{order}/invoice', [OrdersController::class, 'invoice'])->name('order.invoice');

    // Employee area
    Route::get('my-work', [EmployeeController::class, 'work'])->name('my-work');
    Route::get('my-calendar', [EmployeeController::class, 'calendar'])->name('my-calendar');
    Route::post('leaves', [EmployeeController::class, 'applyLeave'])->name('leaves.store');

    // ---- Admin only ----
    Route::group(['middleware' => ['admin']], function () {
        Route::get('orders', [OrdersController::class, 'index'])->name('orders');
        Route::post('orders', [OrdersController::class, 'store']);
        Route::post('orders/{order}/assign', [OrdersController::class, 'assign'])->name('order.assign');
        Route::delete('orders', [OrdersController::class, 'destroy'])->name('order.destroy');

        Route::get('customers', [CustomerController::class, 'index'])->name('customers');
        Route::post('customers', [CustomerController::class, 'store']);
        Route::get('customers/{customer}/measurements', [CustomerController::class, 'measurements'])->name('customer.measurements');
        Route::delete('customers', [CustomerController::class, 'destroy'])->name('customer.destroy');

        Route::get('users', [UserController::class, 'index'])->name('users');
        Route::delete('users', [UserController::class, 'destroy'])->name('user.destroy');
        Route::post('add-user', [UserController::class, 'store'])->name('add-user');

        Route::get('designations', [DesignationController::class, 'index'])->name('designations');
        Route::post('designations', [DesignationController::class, 'store']);
        Route::put('designations', [DesignationController::class, 'update']);
        Route::delete('designations', [DesignationController::class, 'destroy'])->name('designation.destroy');

        Route::get('staff', [StaffController::class, 'index'])->name('staff');
        Route::post('staff', [StaffController::class, 'store']);
        Route::put('staff', [StaffController::class, 'update']);
        Route::delete('staff', [StaffController::class, 'destroy'])->name('staff.destroy');

        Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories');
        Route::post('expense-categories', [ExpenseCategoryController::class, 'store']);
        Route::put('expense-categories', [ExpenseCategoryController::class, 'update']);
        Route::delete('expense-categories', [ExpenseCategoryController::class, 'destroy'])->name('expense-category.destroy');

        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::post('expenses', [ExpenseController::class, 'store']);
        Route::put('expenses', [ExpenseController::class, 'update']);
        Route::delete('expenses', [ExpenseController::class, 'destroy'])->name('expense.destroy');

        Route::get('income-categories', [IncomeCategoryController::class, 'index'])->name('income-categories');
        Route::post('income-categories', [IncomeCategoryController::class, 'store']);
        Route::put('income-categories', [IncomeCategoryController::class, 'update']);
        Route::delete('income-categories', [IncomeCategoryController::class, 'destroy'])->name('income-category.destroy');

        Route::get('income', [IncomeController::class, 'index'])->name('income');
        Route::post('income', [IncomeController::class, 'store']);
        Route::put('income', [IncomeController::class, 'update']);
        Route::delete('income', [IncomeController::class, 'destroy'])->name('income.destroy');

        Route::get('cloth-types', [ClothTypeController::class, 'index'])->name('cloth-types');
        Route::post('cloth-types', [ClothTypeController::class, 'store']);
        Route::put('cloth-types', [ClothTypeController::class, 'update']);
        Route::delete('cloth-types', [ClothTypeController::class, 'destroy'])->name('cloth-type.destroy');

        Route::get('measurement-parts', [MeasurementPartController::class, 'index'])->name('measurement-parts');
        Route::post('measurement-parts', [MeasurementPartController::class, 'store']);
        Route::put('measurement-parts', [MeasurementPartController::class, 'update']);
        Route::delete('measurement-parts', [MeasurementPartController::class, 'destroy']);

        Route::get('settings', [SettingController::class, 'index'])->name('settings');

        Route::post('holidays', [HolidayController::class, 'store'])->name('holidays.store');
        Route::delete('holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
        Route::post('leaves/{leave}/decide', [EmployeeController::class, 'decideLeave'])->name('leaves.decide');
    });
});
