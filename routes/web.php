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
            Route::get('/students', [Counselor\StudentController::class, 'index'])->name('students.index');
            Route::get('/students/{student}', [Counselor\StudentController::class, 'show'])->name('students.show');
        });

    Route::prefix('admin')->name('admin.')
        ->middleware('can:'.PermissionName::ACCESS_ADMIN_AREA->value)
        ->group(function () {
            Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');

            Route::middleware('can:'.PermissionName::MANAGE_SCHOOLS->value)->group(function () {
                Route::resource('schools', Admin\SchoolController::class)->except('show');
                Route::resource('academic-years', Admin\AcademicYearController::class)->except('show');
                Route::resource('grades', Admin\GradeController::class)->except('show');
                Route::resource('classes', Admin\ClassroomController::class)->except('show')
                    ->parameters(['classes' => 'classroom']);
            });

            Route::middleware('can:'.PermissionName::MANAGE_USERS->value)->group(function () {
                Route::resource('students', Admin\StudentController::class)->except(['show', 'destroy']);
                Route::resource('counselors', Admin\CounselorController::class)->except(['show', 'destroy']);
                Route::patch('/users/{user}/status', Admin\UserStatusController::class)->name('users.status');
            });

            Route::middleware('can:'.PermissionName::IMPORT_STUDENTS->value)->prefix('imports')->name('imports.')->group(function () {
                Route::get('/students', [Admin\StudentImportController::class, 'create'])->name('create');
                Route::get('/students/template', [Admin\StudentImportController::class, 'template'])->name('template');
                Route::post('/students', [Admin\StudentImportController::class, 'store'])->name('store');
                Route::get('/students/{import}', [Admin\StudentImportController::class, 'show'])->name('show');
                Route::post('/students/{import}/confirm', [Admin\StudentImportController::class, 'confirm'])->name('confirm');
            });
        });
});

require __DIR__.'/auth.php';
