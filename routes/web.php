<?php

use App\Http\Controllers\Admin\AgendaController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\GeocodingController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\RecapController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Member\CheckInController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\NotificationController;
use App\Http\Controllers\Member\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route(auth()->user()->isAdmin() ? 'admin.overview' : 'member.dashboard')
    : redirect()->route('login'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Area Member (dapat diakses Member maupun Admin yang juga melakukan check-in)
    Route::get('/dashboard', DashboardController::class)->name('member.dashboard');
    Route::post('/sessions/{session}/check-in', CheckInController::class)
        ->middleware('throttle:20,1')
        ->name('member.checkin');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('member.notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('member.notifications.read-all');

    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('member.push.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('member.push.destroy');

    // Area Admin (dibatasi middleware 'admin')
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', OverviewController::class)->name('overview');

        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
        Route::post('/members/{member}/toggle-active', [MemberController::class, 'toggleActive'])->name('members.toggle-active');
        Route::post('/members/{member}/reset-password', [MemberController::class, 'resetPassword'])->name('members.reset-password');

        Route::post('/divisions', [DivisionController::class, 'store'])->name('divisions.store');
        Route::put('/divisions/{division}', [DivisionController::class, 'update'])->name('divisions.update');
        Route::delete('/divisions/{division}', [DivisionController::class, 'destroy'])->name('divisions.destroy');

        Route::get('/agendas', [AgendaController::class, 'index'])->name('agendas.index');
        Route::post('/agendas', [AgendaController::class, 'store'])->name('agendas.store');
        Route::put('/agendas/{agenda}', [AgendaController::class, 'update'])->name('agendas.update');

        Route::get('/geocode', GeocodingController::class)
            ->middleware('throttle:12,1')
            ->name('geocode');

        Route::patch('/sessions/{session}/shift-window', [SessionController::class, 'shiftWindow'])->name('sessions.shift-window');
        Route::post('/sessions/{session}/cancel', [SessionController::class, 'cancel'])->name('sessions.cancel');
        Route::post('/sessions/{session}/remind-pending', [SessionController::class, 'remindPending'])->name('sessions.remind-pending');

        Route::get('/recaps', [RecapController::class, 'index'])->name('recaps.index');
        Route::post('/recaps/{session}/manual', [RecapController::class, 'storeManual'])->name('recaps.manual');
        Route::get('/recaps/export/{format}', ExportController::class)
            ->whereIn('format', ['xlsx', 'pdf'])
            ->name('recaps.export');

        Route::get('/logs', AuditLogController::class)->name('logs.index');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
