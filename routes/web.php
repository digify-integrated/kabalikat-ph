<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AppRenderController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NavigationMenuController;
use Illuminate\Support\Facades\Route;

// Routes that should NOT be accessible when logged in
Route::middleware('guest')->group(function () {
    Route::view('/', 'auth.login', [
        'pageTitle' => env('APP_NAME', 'Laravel'), // set below if you want
        'title' => 'Hi, Welcome back!',
        'description' => 'Please enter your credentials'
    ])->name('login');

    Route::view('/forgot', 'auth.forgot', [
        'pageTitle' => 'Forgot Password',
        'title' => 'Forgot Password?',
        'description' => 'Enter your email to reset your password.'
    ])->name('forgot');

    Route::view('/register', 'auth.register', [
        'pageTitle' => 'Register',
        'title' => 'Sign Up',
        'description' => 'Join us by creating a free account!'
    ])->name('register');

    Route::view('/otp', 'auth.otp-verification', [
        'pageTitle' => 'Two-Factor',
        'title' => 'Verify Your Account',
        'description' => 'Enter the 6 digit code sent to the registered email.'
    ])->name('otp');

    Route::view('/reset-password', 'auth.reset-password', [
        'pageTitle' => 'Reset Password',
        'title' => 'Reset Password',
        'description' => 'Set your new password here.'
    ])->name('reset.password');

    Route::post('/authenticate', [AuthenticationController::class, 'authenticate'])
        ->name('authenticate');
});

// Routes that require login
Route::middleware('auth')->group(function () {
    Route::get('/app', [AppRenderController::class, 'index'])->name('apps.index');

    Route::middleware(['menu.read', 'breadcrumbs', 'nav.menu'])->group(function () {
        Route::get('/app/{appId}/module/{navigationMenuId}', [AppRenderController::class, 'base'])
            ->name('apps.base');

        Route::get('/app/{appId}/module/{navigationMenuId}/new', [AppRenderController::class, 'new'])
            ->name('apps.new');

        Route::get('/app/{appId}/module/{navigationMenuId}/details/{details_id}', [AppRenderController::class, 'details'])
            ->name('apps.details');

        Route::get('/app/{appId}/module/{navigationMenuId}/import', [AppRenderController::class, 'import'])
            ->name('apps.import');
    });

    // App
    Route::post('/generate-app-table', [AppController::class, 'generateAppTable'])->name('generate.app.table');
    Route::post('/generate-app-options', [AppController::class, 'generateAppOptions'])->name('generate.app.option');
    Route::post('/save-app', [AppController::class, 'saveApp'])->name('save.app');
    Route::post('/upload-app-logo', [AppController::class, 'uploadAppLogo'])->name('upload.app.logo');
    Route::post('/fetch-app-details', [AppController::class, 'fetchAppDetails'])->name('fetch.app.details');
    Route::post('/delete-multiple-app', [AppController::class, 'deleteMultipleApp'])->name(name: 'delete.multiple.app');
    Route::post('/delete-app', [AppController::class, 'deleteApp'])->name('delete.app');
    Route::post('/get-app-details', [AppController::class, 'getAppDetails'])->name('get.app.details');

    // Navigation Menu    
    Route::post('/generate-navigation-menu-options', [NavigationMenuController::class, 'generateNavigationMenuOptions'])->name('generate.navigation.menu.option');

    // Audit logs
    Route::post('/get-audit-logs', [AuditLogController::class, 'fetchAuditLogs'])->name('get.audit.logs');

    // Import route
    Route::post('/import-preview', [ImportController::class, 'importPreview'])->name('import.preview');
    Route::post('/save-import-data', [ExportController::class, 'saveImportData'])->name('save.import.data');

    // Export route
    Route::post('/export-list', [ExportController::class, 'exportList'])->name('export.list');
    Route::post('/export', [ExportController::class, 'exportData'])->name('export.data');

    Route::get('/logout', [AuthenticationController::class, 'logout'])->name('logout');
});
