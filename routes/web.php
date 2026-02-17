<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AppRenderController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NavigationMenuController;
use App\Http\Controllers\RolePermissionController;
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
    Route::post('/delete-app', [AppController::class, 'deleteApp'])->name('delete.app');;

    // Navigation Menu 
    Route::post('/generate-navigation-menu-table', [NavigationMenuController::class, 'generateNavigationMenuTable'])->name('generate.navigation.menu.table');  
    Route::post('/generate-navigation-menu-options', [NavigationMenuController::class, 'generateNavigationMenuOptions'])->name('generate.navigation.menu.option');
    Route::post('/save-navigation-menu', [NavigationMenuController::class, 'saveNavigationMenu'])->name('save.navigation.menu');
    Route::post('/save-navigation-menu-route', [NavigationMenuController::class, 'saveNavigationMenuRoute'])->name('save.navigation.menu');
    Route::post('/fetch-navigation-menu-details', [NavigationMenuController::class, 'fetchNavigationMenuDetails'])->name('fetch.navigation.menu.details');
    Route::post('/fetch-navigation-menu-route-details', [NavigationMenuController::class, 'fetchNavigationMenuRouteDetails'])->name('fetch.navigation.menu.route.details');
    Route::post('/delete-multiple-navigation-menu', [NavigationMenuController::class, 'deleteMultipleNavigationMenu'])->name(name: 'delete.multiple.navigation.menu');
    Route::post('/delete-navigation-menu', [NavigationMenuController::class, 'deleteNavigationMenu'])->name('delete.navigation.menu');

    // Role Permission
    Route::post('/generate-navigation-menu-role-permission-table', [RolePermissionController::class, 'generateNavigationMenuRolePermissionTable'])->name('generate.navigation.menu.role.permission.table');

    // Audit logs
    Route::post('/get-audit-logs', [AuditLogController::class, 'fetchAuditLogs'])->name('get.audit.logs');

    // Import route
    Route::post('/import-preview', [ImportController::class, 'importPreview'])->name('import.preview');
    Route::post('/save-import-data', [ImportController::class, 'saveImportData'])->name('save.import.data');

    // Export route
    Route::post('/table-list', [ExportController::class, 'generateTableOptions'])->name('table.list');
    Route::post('/export-list', [ExportController::class, 'exportList'])->name('export.list');
    Route::post('/export', [ExportController::class, 'exportData'])->name('export.data');

    Route::get('/logout', [AuthenticationController::class, 'logout'])->name('logout');
});
