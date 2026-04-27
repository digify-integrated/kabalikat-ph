<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AppRenderController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeValueController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\BatchTrackingController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileExtensionController;
use App\Http\Controllers\FileTypeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NationalityController;
use App\Http\Controllers\NavigationMenuController;
use App\Http\Controllers\ProductAddonController;
use App\Http\Controllers\ProductBOMController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductCategoryMapController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductAttributeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\RoleSystemActionPermissionController;
use App\Http\Controllers\RoleUserAccountController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\StockAdjustmentReasonController;
use App\Http\Controllers\StockLevelController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SystemActionController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitConversionController;
use App\Http\Controllers\UnitTypeController;
use App\Http\Controllers\UploadSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseTypeController;
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

    // Country
    Route::prefix('country')
        ->name('country.')
        ->controller(CountryController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // State
    Route::prefix('state')
        ->name('state.')
        ->controller(StateController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // City
    Route::prefix('city')
        ->name('city.')
        ->controller(CityController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });


    // Currency
    Route::prefix('currency')
        ->name('currency.')
        ->controller(CurrencyController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Company
    Route::prefix('company')
        ->name('company.')
        ->controller(CompanyController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/upload-company-logo', 'uploadCompanyLogo')->name('upload.logo');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Upload Setting
    Route::prefix('upload-setting')
        ->name('upload.setting.')
        ->controller(UploadSettingController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
        });

    // Attribute
    Route::prefix('attribute')
        ->name('attribute.')
        ->controller(AttributeController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
            Route::post('/generate-product-attribute-options', 'generateProductAttributeOptions')->name('generate.options');
        });

     // Attribute Value
    Route::prefix('attribute-value')
        ->name('attribute.value.')
        ->controller(AttributeValueController::class)
        ->group(function () {
            Route::post('/save', 'save')
                ->name('save');

            Route::post('/delete', 'delete')->name('delete');

            Route::post('/generate-table', 'generateTable')
                ->name('generate.table');
        });

    // Product Category
    Route::prefix('product-category')
        ->name('product.category.')
        ->controller(ProductCategoryController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Stock Adjustment Reason
    Route::prefix('stock-adjustment-reason')
        ->name('stock.adjustment.reason.')
        ->controller(StockAdjustmentReasonController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Supplier
    Route::prefix('supplier')
        ->name('supplier.')
        ->controller(SupplierController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Unit Type
    Route::prefix('unit-type')
        ->name('unit.type.')
        ->controller(UnitTypeController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Units
    Route::prefix('unit')
        ->name('unit.')
        ->controller(UnitController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Unit Conversion
    Route::prefix('unit-conversion')
        ->name('unit.conversion.')
        ->controller(UnitConversionController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
        });

    // Warehouse Type
    Route::prefix('warehouse-type')
        ->name('warehouse.type.')
        ->controller(WarehouseTypeController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Warehouse
    Route::prefix('warehouse')
        ->name('warehouse.')
        ->controller(WarehouseController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Products
    Route::prefix('products')
        ->name('products')
        ->controller(ProductController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/save-product-setting', 'saveProductSetting')->name('save.product.setting');
            Route::post('/save-product-variation', 'saveProductVariation')->name('save.product.variation');
            Route::post('/upload-product-image', 'uploadProductImage')->name('upload.product-image');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-variation-table', 'generateVariationTable')->name('generate.variation.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
            Route::post('/generate-product-bom-options', 'generateBomOptions')->name('generate.bom.options');
            Route::post('/generate-product-addon-options', 'generateAddOnOptions')->name('generate.addon.options');
            Route::post('/generate-product-batch-tracking-options', 'generateBatchTrackingOptions')->name('generate.batch.tracking.options');
        });

    // Product Attribute
    Route::prefix('product-attribute')
        ->name('product.attribute.')
        ->controller(ProductAttributeController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
        });

    // Product Category Map
    Route::prefix('product-category-map')
        ->name('product.category.map.')
        ->controller(ProductCategoryMapController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
        });

    // Product BOM
    Route::prefix('product-bom')
        ->name('product.bom.')
        ->controller(ProductBOMController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
        });

    // Product Addon
    Route::prefix('product-addon')
        ->name('product.addon.')
        ->controller(ProductAddonController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
        });

    // Batch Tracking
    Route::prefix('batch-tracking')
        ->name('batch.tracking.')
        ->controller(BatchTrackingController::class)
        ->group(function () {
            Route::post('/save', 'save')->name('save');
            Route::post('/for-approval', 'forApproval')->name('for.approval');
            Route::post('/cancel', 'cancel')->name('cancel');
            Route::post('/approve', 'approve')->name('approve');
            Route::post('/set-to-draft', 'setToDraft')->name('set.to.draft');
            Route::post('/delete', 'delete')->name('delete');
            Route::post('/delete-multiple', 'deleteMultiple')->name('delete.multiple');
            Route::post('/fetch-details', 'fetchDetails')->name('fetch.details');
            Route::post('/generate-table', 'generateTable')->name('generate.table');
            Route::post('/generate-options', 'generateOptions')->name('generate.options');
        });

    // Stock Level
    Route::prefix('stock-level')
        ->name('stock.level.')
        ->controller(StockLevelController::class)
        ->group(function () {
            Route::post('/generate-table', 'generateTable')->name('generate.table');
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
