<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackerController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\Admin\BossConfigController;

// Public Tracker Routes (With Anti-Brute-Force Rate Limiting & Special Character Support)
Route::get('/', [TrackerController::class, 'landing'])->name('tracker.landing');
Route::post('/verify', [TrackerController::class, 'verify'])->middleware('throttle:10,1')->name('tracker.verify');
Route::get('/tracker/{access_code}', [TrackerController::class, 'show'])->where('access_code', '.*')->name('tracker.show');

// Admin Authentication Routes (Protected with Anti-Brute-Force Rate Limiting)
Route::get('/admin/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('admin.login.submit');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Protected Admin Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Server Management
    Route::get('/servers/create', [ServerController::class, 'create'])->name('servers.create');
    Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');
    Route::get('/servers/{server}/edit', [ServerController::class, 'edit'])->name('servers.edit');
    Route::put('/servers/{server}', [ServerController::class, 'update'])->name('servers.update');
    Route::delete('/servers/{server}', [ServerController::class, 'destroy'])->name('servers.destroy');
    
    Route::post('/servers/{server}/generate-code', [ServerController::class, 'generateCode'])->name('servers.generate_code');
    Route::post('/servers/{server}/toggle-active', [ServerController::class, 'toggleActive'])->name('servers.toggle_active');
    Route::post('/servers/{server}/control-bot', [ServerController::class, 'controlBot'])->name('servers.control_bot');

    // Admin Interactive Tracker Controls
    Route::get('/servers/{server}/tracker', [ServerController::class, 'showTracker'])->name('servers.tracker');
    Route::post('/servers/{server}/quick-interval', [ServerController::class, 'quickInterval'])->name('servers.quick_interval');
    Route::post('/servers/{server}/manual-event', [ServerController::class, 'manualEvent'])->name('servers.manual_event');
    Route::post('/servers/{server}/parse-log', [ServerController::class, 'parseLog'])->name('servers.parse_log');
    Route::post('/servers/{server}/reset-boss', [ServerController::class, 'resetBoss'])->name('servers.reset_boss');
    Route::post('/servers/{server}/delete-boss', [ServerController::class, 'deleteBoss'])->name('servers.delete_boss');

    // Boss Configs per Server
    Route::get('/servers/{server}/configs', [BossConfigController::class, 'index'])->name('servers.configs');
    Route::post('/servers/{server}/configs', [BossConfigController::class, 'store'])->name('servers.configs.store');
    Route::delete('/servers/{server}/configs/{config}', [BossConfigController::class, 'destroy'])->name('servers.configs.destroy');

    // Multi-Key Access / Commercial Voucher Management
    Route::get('/keys', [\App\Http\Controllers\Admin\AccessKeyController::class, 'index'])->name('keys.index');
    Route::post('/keys', [\App\Http\Controllers\Admin\AccessKeyController::class, 'store'])->name('keys.store');
    Route::post('/keys/{key}/toggle-active', [\App\Http\Controllers\Admin\AccessKeyController::class, 'toggleActive'])->name('keys.toggle_active');
    Route::post('/keys/{key}/extend', [\App\Http\Controllers\Admin\AccessKeyController::class, 'extend'])->name('keys.extend');
    Route::delete('/keys/{key}', [\App\Http\Controllers\Admin\AccessKeyController::class, 'destroy'])->name('keys.destroy');

    // Global Settings & Access Code Mode Toggle
    Route::post('/settings/toggle-access-code', [\App\Http\Controllers\Admin\SettingController::class, 'toggleAccessCode'])->name('settings.toggle_access_code');

    // Admin Profile & Credentials Management (Email & Password)
    Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
});

// Catch-all Fallback Route (Anti-Probing & Zero Information Leakage)
Route::fallback(function () {
    return redirect()->route('tracker.landing');
});
