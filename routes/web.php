<?php

use App\Enums\PermissionName;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Counselor;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\Shared;
use App\Http\Controllers\Student;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/about', [PublicPageController::class, 'about'])->name('about');
Route::get('/leaderboard', [PublicPageController::class, 'leaderboard'])->name('leaderboard');
Route::get('/content', [PublicContentController::class, 'index'])->name('browse');
Route::get('/content/{section}', [PublicContentController::class, 'section'])
    ->whereIn('section', ['quantitative', 'verbal', 'tahsili'])->name('browse.section');
Route::get('/resources', [PublicPageController::class, 'resources'])->name('resources');
Route::get('/privacy', [PublicPageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PublicPageController::class, 'terms'])->name('terms');
Route::get('/manifest.webmanifest', [PublicPageController::class, 'manifest'])->name('manifest');

Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    // Sends each user to the area their permissions allow.
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/view-as/stop', [Admin\ViewAsController::class, 'stop'])->name('view-as.stop');

    Route::prefix('student')->name('student.')
        ->middleware('can:'.PermissionName::ACCESS_STUDENT_AREA->value)
        ->group(function () {
            Route::get('/dashboard', Student\DashboardController::class)->name('dashboard');
            Route::get('/quantitative', [Student\LearningController::class, 'quantitative'])->name('quantitative');
            Route::get('/verbal', [Student\LearningController::class, 'verbal'])->name('verbal');
            Route::get('/achievement', [Student\LearningController::class, 'achievement'])->name('achievement');
            Route::get('/achievement/{subject:slug}', [Student\LearningController::class, 'subject'])->name('achievement.subject');
            Route::get('/videos', [Student\LearningController::class, 'videos'])->name('videos');
            Route::get('/content/{content:slug}', [Student\LearningController::class, 'show'])->name('content.show');
            Route::get('/links', Student\ImportantLinkController::class)->name('links');
            Route::get('/progress', Student\ProgressController::class)->name('progress');
            Route::get('/challenge', [Student\EngagementController::class, 'challenge'])->name('challenge');
            Route::post('/challenge/questions/{question}/answer', [Student\EngagementController::class, 'answer'])
                ->middleware('throttle:30,1')->name('challenge.answer');
            Route::get('/motivation', [Student\EngagementController::class, 'motivation'])->name('motivation');
            Route::get('/notifications', [Student\EngagementController::class, 'notifications'])->name('notifications');
            Route::post('/notifications/read', [Student\EngagementController::class, 'markNotificationsRead'])->name('notifications.read');
            Route::get('/notifications/{id}', [Student\EngagementController::class, 'openNotification'])->name('notifications.open');
            Route::resource('exams', Student\ExamController::class)->except('show')->parameters(['exams' => 'attempt']);
            Route::get('/favorites', Student\FavoriteController::class)->name('favorites');
            foreach (['start', 'complete', 'uncomplete', 'favorite'] as $action) {
                Route::post("/content/{content:slug}/{$action}", [Student\ContentProgressController::class, $action])->name("content.{$action}");
            }
            Route::post('/content/{content:slug}/quiz', [Student\QuizController::class, 'submit'])
                ->middleware('throttle:20,1')->name('content.quiz.submit');
        });

    Route::prefix('counselor')->name('counselor.')
        ->middleware('can:'.PermissionName::ACCESS_COUNSELOR_AREA->value)
        ->group(function () {
            Route::get('/dashboard', Counselor\DashboardController::class)->name('dashboard');
            Route::get('/students', [Counselor\StudentController::class, 'index'])->name('students.index');
            Route::get('/students/{student}', [Counselor\StudentController::class, 'show'])->name('students.show');
            Route::patch('/students/{student}/follow-up', [Counselor\FollowUpController::class, 'updateStatus'])->name('students.follow-up');
            Route::post('/students/{student}/notes', [Counselor\FollowUpController::class, 'storeNote'])->name('students.notes.store');
            Route::delete('/students/{student}/notes/{note}', [Counselor\FollowUpController::class, 'destroyNote'])->name('students.notes.destroy');
            Route::get('/follow-up', [Counselor\FollowUpController::class, 'index'])->name('follow-up');
            Route::get('/alerts', [Counselor\AlertController::class, 'index'])->name('alerts');
            Route::post('/alerts/{alert}/acknowledge', [Counselor\AlertController::class, 'acknowledge'])->name('alerts.acknowledge');
            Route::post('/alerts/{alert}/resolve', [Counselor\AlertController::class, 'resolve'])->name('alerts.resolve');
            Route::get('/exams', [Counselor\ExamOverviewController::class, 'exams'])->name('exams');
            Route::get('/results', [Counselor\ExamOverviewController::class, 'results'])->name('results');

            Route::middleware('can:'.PermissionName::VIEW_REPORTS->value)->group(function () {
                Route::get('/reports', [Shared\ReportController::class, 'index'])->name('reports.index');
                Route::get('/reports/{report}', [Shared\ReportController::class, 'show'])->name('reports.show');
            });

            Route::middleware('can:'.PermissionName::SEND_ANNOUNCEMENTS->value)->group(function () {
                Route::get('/announcements', [Shared\AnnouncementController::class, 'index'])->name('announcements.index');
                Route::get('/announcements/create', [Shared\AnnouncementController::class, 'create'])->name('announcements.create');
                Route::post('/announcements', [Shared\AnnouncementController::class, 'store'])->name('announcements.store');
                Route::post('/announcements/{announcement}/withdraw', [Shared\AnnouncementController::class, 'withdraw'])->name('announcements.withdraw');
            });
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

            Route::post('/users/{user}/view-as', [Admin\ViewAsController::class, 'start'])
                ->middleware('can:'.PermissionName::VIEW_AS_USER->value)->name('users.view-as');

            Route::middleware('can:'.PermissionName::MANAGE_CONTENT->value)->group(function () {
                Route::resource('content', Admin\ContentController::class)->except(['show', 'destroy']);
                foreach (['publish', 'unpublish', 'archive', 'restore'] as $action) {
                    Route::post("/content/{content}/{$action}", [Admin\ContentController::class, $action])->name("content.{$action}");
                }
                Route::get('/content/{content}/quiz', [Admin\QuizController::class, 'edit'])->name('content.quiz.edit');
                Route::put('/content/{content}/quiz', [Admin\QuizController::class, 'update'])->name('content.quiz.update');
                Route::resource('sources', Admin\SourceController::class)->except('show');
                Route::resource('categories', Admin\CategoryController::class)->except('show');
                Route::resource('subjects', Admin\SubjectController::class)->except('show');
                Route::resource('chapters', Admin\ChapterController::class)->except('show');
                Route::resource('topics', Admin\TopicController::class)->except('show');
            });

            Route::middleware('can:'.PermissionName::MANAGE_CHALLENGES->value)->group(function () {
                Route::resource('challenges', Admin\ChallengeController::class)->except(['show', 'destroy']);
                Route::post('/challenges/{challenge}/publish', [Admin\ChallengeController::class, 'publish'])->name('challenges.publish');
                Route::post('/challenges/{challenge}/unpublish', [Admin\ChallengeController::class, 'unpublish'])->name('challenges.unpublish');
            });

            Route::middleware('can:'.PermissionName::MANAGE_MOTIVATIONS->value)->group(function () {
                Route::resource('motivations', Admin\MotivationController::class)->except('show');
            });

            Route::middleware('can:'.PermissionName::ANNOUNCE_TO_ALL->value)->group(function () {
                Route::get('/announcements', [Shared\AnnouncementController::class, 'index'])->name('announcements.index');
                Route::get('/announcements/create', [Shared\AnnouncementController::class, 'create'])->name('announcements.create');
                Route::post('/announcements', [Shared\AnnouncementController::class, 'store'])->name('announcements.store');
                Route::post('/announcements/{announcement}/withdraw', [Shared\AnnouncementController::class, 'withdraw'])->name('announcements.withdraw');
            });

            Route::middleware('can:'.PermissionName::VIEW_REPORTS->value)->group(function () {
                Route::get('/reports', [Shared\ReportController::class, 'index'])->name('reports.index');
                Route::get('/reports/{report}', [Shared\ReportController::class, 'show'])->name('reports.show');
            });

            Route::get('/audit-logs', [Admin\LogController::class, 'audit'])
                ->middleware('can:'.PermissionName::VIEW_AUDIT_LOGS->value)->name('audit-logs');
            Route::get('/email-logs', [Admin\LogController::class, 'email'])
                ->middleware('can:'.PermissionName::VIEW_EMAIL_LOGS->value)->name('email-logs');

            Route::middleware('can:'.PermissionName::MANAGE_LINKS->value)->group(function () {
                Route::resource('links', Admin\ImportantLinkController::class)->except('show');
            });

            Route::middleware('can:'.PermissionName::MANAGE_ROLES->value)->group(function () {
                Route::get('/roles', [Admin\RoleController::class, 'index'])->name('roles.index');
                Route::get('/roles/{role}/edit', [Admin\RoleController::class, 'edit'])->name('roles.edit');
                Route::put('/roles/{role}', [Admin\RoleController::class, 'update'])->name('roles.update');
            });

            Route::middleware('can:'.PermissionName::MANAGE_SETTINGS->value)->group(function () {
                Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
                Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
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
