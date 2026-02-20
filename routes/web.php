<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AppRenderController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileExtensionController;
use App\Http\Controllers\FileTypeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NavigationMenuController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\RoleSystemActionPermissionController;
use App\Http\Controllers\RoleUserAccountController;
use App\Http\Controllers\SystemActionController;
use App\Http\Controllers\UserController;
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

    // System Action
    Route::prefix('system-action')
        ->name('system.action.')
        ->controller(SystemActionController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Role
    Route::prefix('role')
        ->name('role.')
        ->controller(RoleController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });


    // Role Permission
    Route::prefix('role-permission')
        ->name('role.permission.')
        ->controller(RolePermissionController::class)
        ->group(function () {
            Route::post('/save-navigation-menu-role-assignment', 'saveRoleAssignment')
                ->name('save.role.assignment');
            Route::post('/save-role-navigation-menu-assignment', 'saveNavigationMenuAssignment')
                ->name('save.navigation.menu.assignment');

            Route::post('/update', 'update')->name('update');
            Route::post('/delete', 'delete')->name('delete');

            Route::post('/generate-navigation-menu-role-permission-table', 'generateNavigationMenuRolePermissionTable')
                ->name('generate.navigation.menu.role.table');

            Route::post('/generate-role-navigation-menu-permission-table', 'generateRoleNavigationMenuPermissionTable')
                ->name('generate.role.navigation.menu.table');

            Route::post('/generate-navigation-menu-role-dual-listbox-options', 'generateNavigationMenuRoleDualListboxOptions')
                ->name('generate.navigation.menu.role.dual.listbox.options');

            Route::post('/generate-role-navigation-menu-dual-listbox-options', 'generateRoleNavigationMenuDualListboxOptions')
                ->name('generate.role.navigation.menu.dual.listbox.options');
        });

    // Role System Action Permission
    Route::prefix('role-system-action-permission')
        ->name('role.system.action.permission.')
        ->controller(RoleSystemActionPermissionController::class)
        ->group(function () {
            Route::post('/save-system-action-role-assignment', 'saveRoleAssignment')
                ->name('save.role.assignment');
            Route::post('/save-role-system-action-assignment', 'saveSystemActionAssignment')
                ->name('save.system.action.assignment');

            Route::post('/update', 'update')->name('update');
            Route::post('/delete', 'delete')->name('delete');

            Route::post('/generate-system-action-role-permission-table', 'generateSystemActionRolePermissionTable')
                ->name('generate.system.action.role.table');

            Route::post('/generate-role-system-action-permission-table', 'generateRoleSystemActionPermissionTable')
                ->name('generate.role.system.action.table');

            Route::post('/generate-system-action-role-dual-listbox-options', 'generateSystemActionRoleDualListboxOptions')
                ->name('generate.system.action.role.dual.listbox.options');

            Route::post('/generate-role-system-action-dual-listbox-options', 'generateRoleSystemActionDualListboxOptions')
                ->name('generate.role.system.action.dual.listbox.options');
        });

    // Role User Account
    Route::prefix('role-user-account')
        ->name('role.user.account.')
        ->controller(RoleUserAccountController::class)
        ->group(function () {
            Route::post('/save-user-account-role-assignment', 'saveRoleAssignment')
                ->name('save.role.assignment');
            Route::post('/save-role-user-account-assignment', 'saveUserAccountAssignment')
                ->name('save.user.account.assignment');

            Route::post('/delete', 'delete')->name('delete');

            Route::post('/generate-user-account-role-table', 'generateUserAccountRoleTable')
                ->name('generate.user.account.role.table');

            Route::post('/generate-role-user-account-table', 'generateRoleUserAccountTable')
                ->name('generate.role.user.account.table');

            Route::post('/generate-user-account-role-dual-listbox-options', 'generateUserAccountRoleDualListboxOptions')
                ->name('generate.user.account.role.dual.listbox.options');

            Route::post('/generate-role-user-account-dual-listbox-options', 'generateRoleUserAccountDualListboxOptions')
                ->name('generate.role.user.account.dual.listbox.options');
        });

    // Users
    Route::prefix('user')
        ->name('user.')
        ->controller(UserController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/upload-user-profile-picture', 'uploadProfilePicture')->name('upload.profile-picture');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/activate', 'activate')->name('activate');
            Route::post('/activate-multiple', 'activateMultiple')->name('activate.multiple');
            Route::post('/deactivate', 'deactivate')->name('deactivate');
            Route::post('/deactivate-multiple', 'deactivateMultiple')->name('deactivate.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // File Type
    Route::prefix('file-type')
        ->name('file.type.')
        ->controller(FileTypeController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // File Extension
    Route::prefix('file-extension')
        ->name('file.extension.')
        ->controller(FileExtensionController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
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

    // Audit Logs
    Route::prefix('audit-log')
        ->name('audit-log.')
        ->controller(AuditLogController::class)
        ->group(function () {
            Route::post('/fetch', 'fetch')->name('fetch');
        });


    Route::get('/logout', [AuthenticationController::class, 'logout'])->name('logout');
});
