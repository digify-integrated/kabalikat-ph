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

Route::middleware('guest')->group(function () {
    Route::view('/', 'auth.login', [
        'pageTitle' => env('APP_NAME', 'Laravel'),
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

    Route::post('/auth/authenticate', [AuthenticationController::class, 'authenticate'])
        ->name('authenticate');
});

Route::middleware('auth')->group(function () {
    Route::controller(AppRenderController::class)->group(function () {
        Route::get('/app', 'index')->name('apps.index');

        Route::middleware(['menu.read', 'breadcrumbs', 'nav.menu'])->group(function () {
            Route::get('/app/{appId}/module/{navigationMenuId}', 'base')->name('apps.base');
            Route::get('/app/{appId}/module/{navigationMenuId}/new', 'new')->name('apps.new');
            Route::get('/app/{appId}/module/{navigationMenuId}/details/{details_id}', 'details')->name('apps.details');
            Route::get('/app/{appId}/module/{navigationMenuId}/import', 'import')->name('apps.import');
        });
    });

    // App (same URLs)
    Route::prefix('app')
        ->name('app.')
        ->controller(AppController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/upload-app-logo', 'uploadAppLogo')->name('upload.logo');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Navigation Menu
    Route::prefix('navigation-menu')
        ->name('navigation.menu.')
        ->controller(NavigationMenuController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/save-route', 'saveRoute')->name('save.route');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/fetch-route-details', 'fetchRouteDetails')->name('fetch.route.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });


    // Role Permission
    Route::prefix('role-permission')
        ->name('role.permission.')
        ->controller(RolePermissionController::class)
        ->group(function () {
            Route::post('/save-navigation-menu-role-assignment', 'saveNavigationMenuRoleAssignment')
                ->name('save.navigation.menu.role.assignment');

            Route::post('/update', 'update')->name('update');
            Route::post('/delete', 'delete')->name('delete');

            Route::post('/generate-navigation-menu-role-permission-table', 'generateNavigationMenuRolePermissionTable')
                ->name('generate.navigation.menu.table');

            Route::post('/generate-navigation-menu-role-dual-listbox-options', 'generateNavigationMenuRoleDualListboxOptions')
                ->name('generate.navigation.menu.dual.listbox.options');
        });


    // Import
    Route::prefix('import')
        ->name('import.')
        ->controller(ImportController::class)
        ->group(function () {
            Route::post('/preview', 'preview')->name('preview');
            Route::post('/save', 'save')->name('save.data');
        });


    // Export
    Route::prefix('export')
        ->name('export.')
        ->controller(ExportController::class)
        ->group(function () {
            Route::post('/table-list', 'generateTableOptions')->name('table.list');
            Route::post('/export-list', 'exportList')->name('list');
            Route::post('/export', 'exportData')->name('data');
        });


    // Audit logs
    Route::post('/audit-log/fetch', [AuditLogController::class, 'fetchAuditLogs'])->name('get.audit.logs');

    Route::get('/logout', [AuthenticationController::class, 'logout'])->name('logout');
});
