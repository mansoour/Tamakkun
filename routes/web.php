<?php

use App\Enums\PermissionName;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Counselor;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    // Sends each user to the area their permissions allow.
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::prefix('student')->name('student.')
        ->middleware('can:'.PermissionName::ACCESS_STUDENT_AREA->value)
        ->group(function () {
            Route::get('/dashboard', Student\DashboardController::class)->name('dashboard');
        });

    Route::prefix('counselor')->name('counselor.')
        ->middleware('can:'.PermissionName::ACCESS_COUNSELOR_AREA->value)
        ->group(function () {
            Route::get('/dashboard', Counselor\DashboardController::class)->name('dashboard');
        });

    Route::prefix('admin')->name('admin.')
        ->middleware('can:'.PermissionName::ACCESS_ADMIN_AREA->value)
        ->group(function () {
            Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');
        });
});

require __DIR__.'/auth.php';
