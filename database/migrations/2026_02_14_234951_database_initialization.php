<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /* =============================================================================================
            TABLE: AUDIT LOG
        ============================================================================================= */

        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reference_id');
            $table->string('table_name');
            $table->text('log');

            $table->foreignId('changed_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->index(['reference_id', 'table_name'], 'audit_log_idx');
        });
        
        /* =============================================================================================
            TABLE: APP
        ============================================================================================= */

        Schema::create('app', function (Blueprint $table) {
            $table->id();
            $table->string('app_name');
            $table->string('app_description')->nullable();
            $table->string('app_version')->default('1.0.0');
            $table->string('app_logo')->nullable();
            $table->bigInteger('navigation_menu_id');
            $table->string('navigation_menu_name');
            $table->integer('order_sequence')->default(0);
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['navigation_menu_id'], 'idx_app_navigation_menu_id');
        });

        /* =============================================================================================
            TABLE: NAVIGATION MENU
        ============================================================================================= */

        Schema::create('navigation_menu', function (Blueprint $table) {
            $table->id();
            $table->string('navigation_menu_name');
            $table->string('navigation_menu_icon')->nullable();
            $table->bigInteger('app_id');
            $table->string('app_name');
            $table->bigInteger('parent_navigation_menu_id')->nullable();
            $table->string('parent_navigation_menu_name')->nullable();
            $table->string('database_table')->nullable();
            $table->integer('order_sequence')->default(0);
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['app_id'], 'navigation_menu_app_id_idx');
            $table->index(['parent_navigation_menu_id'], 'navigation_menu_parent_navigation_menu_id_idx');
        });

        /* =============================================================================================
            TABLE: NAVIGATION MENU ROUTE
        ============================================================================================= */

        Schema::create('navigation_menu_route', function (Blueprint $table) {
            $table->id();

            $table->foreignId('navigation_menu_id')
                ->constrained('navigation_menu')
                ->cascadeOnDelete();

            $table->enum('route_type', ['index', 'details', 'new', 'import'])
            ->default('index');
            
            $table->string('view_file');
            $table->string('js_file');
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['navigation_menu_id', 'route_type'], 'navigation_menu_route_app_id_idx');
        });
        
        /* =============================================================================================
            TABLE: SYSTEM ACTION
        ============================================================================================= */

        Schema::create('system_action', function (Blueprint $table) {
            $table->id();
            $table->string('system_action_name');
            $table->string('system_action_description');
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* =============================================================================================
            TABLE: ROLE
        ============================================================================================= */

        Schema::create('role', function (Blueprint $table) {
            $table->id();
            $table->string('role_name');
            $table->string('role_description');
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* =============================================================================================
            TABLE: ROLE PERMISSION
        ============================================================================================= */

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();

            $table->foreignId('role_id')
                ->constrained('role')
                ->cascadeOnDelete()
                ->cascade;

            $table->string('role_name');

            $table->foreignId('navigation_menu_id')
                ->constrained('navigation_menu')
                ->cascadeOnDelete();

            $table->string('navigation_menu_name');

            $table->boolean('read_access')->default(false);
            $table->boolean('write_access')->default(false);
            $table->boolean('create_access')->default(false);
            $table->boolean('delete_access')->default(false);
            $table->boolean('import_access')->default(false);
            $table->boolean('export_access')->default(false);
            $table->boolean('logs_access')->default(false);
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['role_id', 'navigation_menu_id']);

            $table->index(['role_id'], 'role_permission_role_id_idx');
            $table->index(['navigation_menu_id'], 'role_permission_navigation_menu_id_idx');
        });

        /* =============================================================================================
            TABLE: ROLE SYSTEM ACTION PERMISSION
        ============================================================================================= */

        Schema::create('role_system_action_permission', function (Blueprint $table) {
            $table->id();

            $table->foreignId('role_id')
                ->constrained('role')
                ->cascadeOnDelete();

            $table->string('role_name');

            $table->foreignId('system_action_id')
                ->constrained('system_action')
                ->cascadeOnDelete();

            $table->string('system_action_name');

            $table->boolean('system_action_access')->default(false);
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'system_action_id']);

            $table->index(['role_id'], 'role_system_action_permission_role_id_idx');
            $table->index(['system_action_id'], 'role_system_action_permission_system_action_id_idx');
        });

        /* =============================================================================================
            TABLE: ROLE USER ACCOUNT
        ============================================================================================= */

        Schema::create('role_user_account', function (Blueprint $table) {
            $table->id();

            $table->foreignId('role_id')
                ->constrained('role')
                ->cascadeOnDelete();
                
            $table->string('role_name');

            $table->foreignId('user_account_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('user_name');
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['role_id', 'user_account_id']);
            
            $table->index(['role_id'], 'role_user_account_role_id_idx');
            $table->index(['user_account_id'], 'role_user_account_user_account_id_idx');
        });

        /* =============================================================================================
            TABLE: FILE TYPE
        ============================================================================================= */

        Schema::create('file_type', function (Blueprint $table) {
            $table->id();
            $table->string('file_type_name');
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* =============================================================================================
            TABLE: FILE EXTENSION
        ============================================================================================= */
        
        Schema::create('file_extension', function (Blueprint $table) {
            $table->id();
            $table->string('file_extension_name');
            $table->string('file_extension');

            $table->bigInteger('file_type_id')
            ->constrained('file_type')
            ->cascadeOnDelete();

            $table->string('file_type_name');
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['file_type_id'], 'file_extension_file_type_id_idx');
        });

        /* =============================================================================================
            TABLE: UPLOAD SETTING
        ============================================================================================= */

        Schema::create('upload_setting', function (Blueprint $table) {
            $table->id();
            $table->string('upload_setting_name');
            $table->string('upload_setting_description');
            $table->double('max_file_size');
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* =============================================================================================
            TABLE: UPLOAD SETTING FILE EXTENSION
        ============================================================================================= */

        Schema::create('upload_setting_file_extension', function (Blueprint $table) {
            $table->id();
            $table->bigInteger(column: 'upload_setting_id')
            ->constrained('upload_setting')
            ->cascadeOnDelete();

            $table->string('upload_setting_name');

            $table->bigInteger('file_extension_id')
            ->constrained('file_extension')
            ->cascadeOnDelete();

            $table->string('file_extension_name');
            $table->string('file_extension');
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['upload_setting_id'], 'upload_setting_file_extension_upload_setting_id_idx');
            $table->index(['file_extension_id'], 'upload_setting_file_extension_file_extension_id_idx');
        });

        /* =============================================================================================
            TABLE: Country
        ============================================================================================= */

        Schema::create('country', function (Blueprint $table) {
            $table->id();

            $table->string('country_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* =============================================================================================
            TABLE: State
        ============================================================================================= */

        Schema::create('state', function (Blueprint $table) {
            $table->id();

            $table->string('state_name');

            $table->bigInteger('country_id')
            ->constrained('country')
            ->cascadeOnDelete();

            $table->string('country_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['country_id'], 'state_country_id_idx');
        });

        /* =============================================================================================
            TABLE: City
        ============================================================================================= */

        Schema::create('city', function (Blueprint $table) {
            $table->id();

            $table->string('city_name');

            $table->bigInteger('state_id')
            ->constrained('state')
            ->cascadeOnDelete();

            $table->string('state_name');

            $table->bigInteger('country_id')
            ->constrained('country')
            ->cascadeOnDelete();

            $table->string('country_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['country_id'], 'city_country_id_idx');
            $table->index(['state_id'], 'city_state_id_idx');
        });

        /* =============================================================================================
            TABLE: Nationality
        ============================================================================================= */

        Schema::create('nationality', function (Blueprint $table) {
            $table->id();

            $table->string('nationality_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* =============================================================================================
            TABLE: Currency
        ============================================================================================= */

        Schema::create('currency', function (Blueprint $table) {
            $table->id();

            $table->string('currency_name');
            $table->string('symbol');
            $table->string('shorthand');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* =============================================================================================
            TABLE: Company
        ============================================================================================= */

        Schema::create('company', function (Blueprint $table) {
            $table->id();

            $table->string('company_name');
            $table->string('company_logo')->nullable();
            $table->string('address');
            
            $table->bigInteger('city_id')
            ->constrained('city');

            $table->string('city_name');

            $table->bigInteger('state_id')
            ->constrained('state');

            $table->string('state_name');

            $table->bigInteger('country_id')
            ->constrained('country');

            $table->string('country_name');
            
            $table->string('tax_id')->nullable();

            $table->bigInteger('currency_id')
            ->constrained('currency');

            $table->string('currency_name');

            $table->string('phone')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['city_id'], 'company_city_id_idx');
            $table->index(['state_id'], 'company_state_id_idx');
            $table->index(['country_id'], 'company_country_id_idx');
            $table->index(['currency_id'], 'company_currency_id_idx');
        });

        /* =============================================================================================
            TABLE: Attribute
        ============================================================================================= */

        Schema::create('attribute', function (Blueprint $table) {
            $table->id();

            $table->string('attribute_name');
            $table->enum('selection_type', ['Single', 'Multiple'])
            ->default('Single');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['selection_type'], 'attribute_selection_type_idx');
        });

        /* =============================================================================================
            TABLE: Attribute Value
        ============================================================================================= */

        Schema::create('attribute_value', function (Blueprint $table) {
            $table->id();

            $table->string('attribute_value');

            $table->bigInteger('attribute_id')
            ->constrained('attribute')
            ->cascadeOnDelete();

            $table->string('attribute_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

             $table->index(['attribute_id'], 'attribute_value_attribute_id_idx');
        });

        
        /* =============================================================================================
            TABLE: Product Category
        ============================================================================================= */

        Schema::create('product_category', function (Blueprint $table) {
            $table->id();

            $table->string('product_category_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        
        /* =============================================================================================
            TABLE: Stock Adjustment Reason
        ============================================================================================= */

        Schema::create('stock_adjustment_reason', function (Blueprint $table) {
            $table->id();

            $table->string('stock_adjustment_reason_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* =============================================================================================
            TABLE: Supplier
        ============================================================================================= */

        Schema::create('supplier', function (Blueprint $table) {
            $table->id();

            $table->string('supplier_name');
            $table->string('contact_person')->nullable();
            $table->enum('supplier_status', ['Active', 'Inactive'])
            ->default('Active');

            $table->string('address');
            
            $table->bigInteger('city_id')
            ->constrained('city');

            $table->string('city_name');

            $table->bigInteger('state_id')
            ->constrained('state');

            $table->string('state_name');

            $table->bigInteger('country_id')
            ->constrained('country');

            $table->string('country_name');

            $table->string('phone')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_status'], 'supplier_supplier_status_idx');
            $table->index(['city_id'], 'supplier_city_id_idx');
            $table->index(['state_id'], 'supplier_state_id_idx');
            $table->index(['country_id'], 'supplier_country_id_idx');
        });

        /* =============================================================================================
            TABLE: Unit Type
        ============================================================================================= */

        Schema::create('unit_type', function (Blueprint $table) {
            $table->id();

            $table->string('unit_type_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        
        /* =============================================================================================
            TABLE: Unit
        ============================================================================================= */

        Schema::create('unit', function (Blueprint $table) {
            $table->id();

            $table->string('unit_name');
            $table->string('abbreviation');

            $table->bigInteger('unit_type_id')
            ->constrained('unit_type')
            ->cascadeOnDelete();

            $table->string('unit_type_name');

            $table->enum('is_base_unit', ['Yes', 'No'])
            ->default('No');
            
            $table->double('conversion_factor')
            ->default(0);

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_type_id'], 'unit_unit_type_id_idx');
        });

        /* =============================================================================================
            TABLE: Warehouse Type
        ============================================================================================= */

        Schema::create('warehouse_type', function (Blueprint $table) {
            $table->id();

            $table->string('warehouse_type_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* =============================================================================================
            TABLE: Warehouse
        ============================================================================================= */

        Schema::create('warehouse', function (Blueprint $table) {
            $table->id();

            $table->string('warehouse_name');
            $table->string('contact_person')->nullable();
            $table->enum('warehouse_status', ['Active', 'Inactive'])
            ->default('Active');

            $table->bigInteger('warehouse_type_id')
            ->constrained('warehouse_type')
            ->cascadeOnDelete();
            $table->string('warehouse_type_name');

            $table->string('address');
            
            $table->bigInteger('city_id')
            ->constrained('city');

            $table->string('city_name');

            $table->bigInteger('state_id')
            ->constrained('state');

            $table->string('state_name');

            $table->bigInteger('country_id')
            ->constrained('country');

            $table->string('country_name');

            $table->string('phone')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_status'], 'warehouse_warehouse_status_idx');
            $table->index(['warehouse_type_id'], 'warehouse_warehouse_type_id_idx');
            $table->index(['city_id'], 'warehouse_city_id_idx');
            $table->index(['state_id'], 'warehouse_state_id_idx');
            $table->index(['country_id'], 'warehouse_country_id_idx');
        });

        /* =============================================================================================
            TABLE: 
        ============================================================================================= */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');

        Schema::dropIfExists('navigation_menu_route');

        Schema::dropIfExists('role_user_account');
        Schema::dropIfExists('role_system_action_permission');
        Schema::dropIfExists('role_permission');

        Schema::dropIfExists('upload_setting_file_extension');
        Schema::dropIfExists('file_extension');

        Schema::dropIfExists('attribute_value');

        Schema::dropIfExists('unit');
        Schema::dropIfExists('warehouse');

        Schema::dropIfExists('supplier');
        Schema::dropIfExists('company');

        Schema::dropIfExists('city');
        Schema::dropIfExists('state');

        Schema::dropIfExists('attribute');
        Schema::dropIfExists('unit_type');
        Schema::dropIfExists('warehouse_type');

        Schema::dropIfExists('upload_setting');
        Schema::dropIfExists('file_type');

        Schema::dropIfExists('navigation_menu');
        Schema::dropIfExists('app');

        Schema::dropIfExists('system_action');
        Schema::dropIfExists('role');

        Schema::dropIfExists('product_category');
        Schema::dropIfExists('stock_adjustment_reason');
        Schema::dropIfExists('nationality');
        Schema::dropIfExists('currency');
        Schema::dropIfExists('country');

        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
