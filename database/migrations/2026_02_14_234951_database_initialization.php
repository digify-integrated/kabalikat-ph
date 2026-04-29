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

            $table->string('app_description')
            ->nullable();

            $table->string('app_version')
            ->default('1.0.0');

            $table->string('app_logo')
            ->nullable();

            $table->foreignId('navigation_menu_id')
            ->nullable();

            $table->string('navigation_menu_name')
            ->nullable();
            
            $table->integer('order_sequence')
            ->default(0);

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

            $table->string('navigation_menu_icon')
            ->nullable();

            $table->foreignId('app_id')
            ->nullable();

            $table->string('app_name')
            ->nullable();

            $table->bigInteger('parent_navigation_menu_id')
            ->nullable();

            $table->string('parent_navigation_menu_name')
            ->nullable();

            $table->string('database_table')
            ->nullable();

            $table->integer('order_sequence')
            ->default(0);

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
            ->cascadeOnDelete();

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

            $table->foreignId('file_type_id')
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
            $table->foreignId(column: 'upload_setting_id')
            ->constrained('upload_setting')
            ->cascadeOnDelete();

            $table->string('upload_setting_name');

            $table->foreignId('file_extension_id')
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

            $table->foreignId('country_id')
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

            $table->foreignId('state_id')
            ->constrained('state')
            ->cascadeOnDelete();

            $table->string('state_name');

            $table->foreignId('country_id')
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
            
            $table->foreignId('city_id')
            ->constrained('city');

            $table->string('city_name');

            $table->foreignId('state_id')
            ->constrained('state');

            $table->string('state_name');

            $table->foreignId('country_id')
            ->constrained('country');

            $table->string('country_name');
            
            $table->string('tax_id')->nullable();

            $table->foreignId('currency_id')
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

            $table->foreignId('attribute_id')
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
            TABLE: Stock Transfer Reason
        ============================================================================================= */

        Schema::create('stock_transfer_reason', function (Blueprint $table) {
            $table->id();

            $table->string('stock_transfer_reason_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* =============================================================================================
            TABLE: Supplier
        ============================================================================================= */

        Schema::create('supplier', function (Blueprint $table) {
            $table->id();

            $table->string('supplier_name');

            $table->string('contact_person')
            ->nullable();

            $table->enum('supplier_status', ['Active', 'Inactive'])
            ->default('Active');

            $table->string('address');
            
            $table->foreignId('city_id')
            ->constrained('city');

            $table->string('city_name');

            $table->foreignId('state_id')
            ->constrained('state');

            $table->string('state_name');

            $table->foreignId('country_id')
            ->constrained('country');

            $table->string('country_name');

            $table->string('phone')
            ->nullable();

            $table->string('telephone')
            ->nullable();

            $table->string('email')
            ->nullable();

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

            $table->foreignId('unit_type_id')
            ->constrained('unit_type')
            ->cascadeOnDelete();

            $table->string('unit_type_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_type_id'], 'unit_unit_type_id_idx');
        });
        
        /* =============================================================================================
            TABLE: Unit Conversion
        ============================================================================================= */

        Schema::create('unit_conversion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('from_unit_id')
            ->constrained('unit')
            ->cascadeOnDelete();

            $table->string('from_unit_name');

            $table->foreignId('to_unit_id')
            ->constrained('unit')
            ->cascadeOnDelete();

            $table->string('to_unit_name');
            $table->double('conversion_factor');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['from_unit_id'], 'unit_from_unit_id_idx');
            $table->index(['to_unit_id'], 'unit_to_unit_id_idx');
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
            
            $table->string('contact_person')
            ->nullable();

            $table->enum('warehouse_status', ['Active', 'Inactive'])
            ->default('Active');

            $table->foreignId('warehouse_type_id')
            ->constrained('warehouse_type')
            ->cascadeOnDelete();

            $table->string('warehouse_type_name');
            $table->string('address');
            
            $table->foreignId('city_id')
            ->constrained('city');

            $table->string('city_name');

            $table->foreignId('state_id')
            ->constrained('state');

            $table->string('state_name');

            $table->foreignId('country_id')
            ->constrained('country');

            $table->string('country_name');

            $table->string('phone')
            ->nullable();

            $table->string('telephone')
            ->nullable();

            $table->string('email')
            ->nullable();

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_status'], 'warehouse_warehouse_status_idx');
            $table->index(['warehouse_type_id'], 'warehouse_warehouse_type_id_idx');
            $table->index(['city_id'], 'warehouse_city_id_idx');
            $table->index(['state_id'], 'warehouse_state_id_idx');
            $table->index(['country_id'], 'warehouse_country_id_idx');
        });

        /* =============================================================================================
            TABLE: Product
        ============================================================================================= */

        Schema::create('product', function (Blueprint $table) {
            $table->id();

            $table->string('product_name');

            $table->string('product_description')
            ->nullable();
            
            $table->string('product_image')
            ->nullable();

            $table->enum('product_status', ['Active', 'Inactive']);
            $table->string('sku')
            ->nullable();

            $table->string('barcode')
            ->nullable();

            $table->enum('product_type', ['Goods', 'Service']);

            $table->double('base_price')
            ->default(0);

            $table->double('cost_price')
            ->default(0);

            $table->enum('inventory_flow', ['FIFO', 'FEFO', 'LIFO', 'Manual']);
            $table->enum('tax_classification', ['Vatable', 'VAT Exempt', 'Zero Rated']);

            $table->integer('attribute_count')
            ->default(0);

            $table->enum('track_inventory', ['Yes', 'No'])
            ->default('Yes');

            $table->enum('is_variant', ['Yes', 'No'])
            ->default('No');

            $table->enum('is_addon', ['Yes', 'No'])
            ->default('No');

            $table->enum('batch_tracking', ['Yes', 'No'])
            ->default('No');

            $table->enum('expiration_tracking', ['Yes', 'No'])
            ->default('No');

            $table->foreignId('parent_product_id')
            ->nullable()
            ->constrained('product')
            ->nullOnDelete();

            $table->string('parent_product_name')
            ->nullable();

            $table->string('variant_signature')
            ->nullable();

            $table->double('reorder_level')
            ->default(0);

            $table->foreignId('base_unit_id')
            ->nullable()
            ->constrained('unit')
            ->nullOnDelete();

            $table->string('base_unit_name');
            $table->string('base_unit_abbreviation');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['parent_product_id', 'variant_signature'],
                'unique_variant_signature'
            );

            $table->index(['sku'], 'product_sku_idx');
            $table->index(['barcode'], 'product_barcode_idx');
            $table->index(['tax_classification'], 'product_tax_classification_idx');
            $table->index(['product_type'], 'product_product_type_idx');
            $table->index(['parent_product_id'], 'product_parent_product_id_idx');
            $table->index(['variant_signature'], 'product_variant_signature_idx');
            $table->index(['base_unit_id'], 'product_base_unit_id_idx');
        });

        /* =============================================================================================
            TABLE: Product Category Map
        ============================================================================================= */

        Schema::create('product_category_map', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
            ->constrained('product')
            ->cascadeOnDelete();

            $table->string('product_name');

            $table->foreignId('product_category_id')
            ->constrained('product_category');

            $table->string('product_category_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id'], 'product_category_map_product_id_idx');
            $table->index(['product_category_id'], 'product_category_map_product_category_id_idx');
        });

        /* =============================================================================================
            TABLE: Product Attribute
        ============================================================================================= */

        Schema::create('product_attribute', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
            ->constrained('product')
            ->cascadeOnDelete();

            $table->string('product_name');

            $table->foreignId('attribute_id')
            ->constrained('attribute');

            $table->string('attribute_name');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id'], 'product_attribute_product_id_idx');
            $table->index(['attribute_id'], 'product_attribute_attribute_id_idx');
        });

        /* =============================================================================================
            TABLE: Product BOM
        ============================================================================================= */

        Schema::create('product_bom', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
            ->constrained('product')
            ->cascadeOnDelete();

            $table->string('product_name');

            $table->foreignId('bom_product_id')
            ->constrained('product')
            ->cascadeOnDelete();

            $table->string('bom_product_name');

            $table->double('quantity')
            ->default(0);

            $table->enum('stock_policy', ['Strict', 'Allow Negative'])
            ->default('Strict');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id'], 'product_bom_product_id_idx');
            $table->index(['bom_product_id'], 'product_bom_bom_product_id_idx');
        });

        /* =============================================================================================
            TABLE: Product Add-on
        ============================================================================================= */

        Schema::create('product_addon', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
            ->constrained('product')
            ->cascadeOnDelete();

            $table->string('product_name');

            $table->foreignId('addon_product_id')
            ->constrained('product')
            ->cascadeOnDelete();

            $table->string('addon_product_name');

            $table->double('max_quantity')
            ->default(0);

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id'], 'product_addon_product_id_idx');
            $table->index(['addon_product_id'], 'product_addon_addon_product_id_idx');
        });

        /* =============================================================================================
            TABLE: Stock Batch
        ============================================================================================= */

        Schema::create('stock_batch', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')
            ->nullable()
            ->constrained('warehouse')
            ->nullOnDelete();

            $table->string('warehouse_name')
            ->nullable();

            $table->enum('batch_status', ['Draft', 'For Approval', 'Approved', 'Cancelled'])
            ->default('Draft');

            $table->string('batch_number');

            $table->string('remarks')
            ->nullable();

            $table->date('expiration_date')
            ->nullable();

            $table->date('received_date')
            ->nullable();

            $table->date('for_approval_date')
            ->nullable();

            $table->date('approved_date')
            ->nullable();

            $table->date('cancellation_date')
            ->nullable();

            $table->date('set_to_draft_date')
            ->nullable();
            
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id'], 'stock_batch_product_id_idx');
            $table->index(['warehouse_id'], 'stock_batch_warehouse_id_idx');
            $table->index(['expiration_date'], 'stock_batch_expiration_date_idx');
            $table->index(['received_date'], 'stock_batch_received_date_idx');
            $table->index(['for_approval_date'], 'stock_batch_for_approval_date_idx');
            $table->index(['approved_date'], 'stock_batch_approved_date_idx');
            $table->index(['cancellation_date'], 'stock_batch_cancellation_date_idx');
            $table->index(['set_to_draft_date'], 'stock_batch_set_to_draft_date_idx');
            $table->index(['batch_number'], 'stock_batch_batch_number_idx');
        });

        /* =============================================================================================
            TABLE: Stock Level
        ============================================================================================= */

        Schema::create('stock_level', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
            ->constrained('product')
            ->cascadeOnDelete();

            $table->string('product_name');

            $table->foreignId('warehouse_id')
            ->nullable()
            ->constrained('warehouse')
            ->nullOnDelete();

            $table->string('warehouse_name')
            ->nullable();

            $table->enum('stock_status', ['In Stock', 'Low Stock', 'Out of Stock'])
            ->default('In Stock');

            $table->double('quantity')
            ->default(0);

            $table->enum('reference_type', ['Stock Batch', 'Purchase Order'])
            ->nullable();

            $table->string('reference_number');

            $table->date('expiration_date')
            ->nullable();

            $table->date('received_date')
            ->nullable();

            $table->double('cost_per_unit')
            ->default(0);

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id'], 'stock_level_product_id_idx');
            $table->index(['warehouse_id'], 'stock_level_warehouse_id_idx');
            $table->index(['stock_status'], 'stock_level_stock_status_idx');
            $table->index(['expiration_date'], 'stock_level_expiration_date_idx');
            $table->index(['received_date'], 'stock_level_received_date_idx');
            $table->index(['stock_batch_id'], 'stock_level_stock_batch_id_idx');
        });

        /* =============================================================================================
            TABLE: Stock Adjustment
        ============================================================================================= */

        Schema::create('stock_adjustment', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_level_id')
            ->constrained('stock_level')
            ->cascadeOnDelete();

            $table->enum('adjustment_type', ['Add Stock', 'Remove Stock', 'Set Exact Stock']);

            $table->enum('stock_adjustment_status', ['Draft', 'For Approval', 'Approved', 'Cancelled'])
            ->default('Draft');

            $table->double('quantity')
            ->default(0);

            $table->foreignId('stock_adjustment_reason_id')
            ->nullable()
            ->constrained('stock_adjustment_reason')
            ->nullOnDelete();
            
            $table->string('stock_adjustment_reason_name')
            ->nullable();

            $table->string('remarks')
            ->nullable();

            $table->date('for_approval_date')
            ->nullable();

            $table->date('approved_date')
            ->nullable();

            $table->date('cancellation_date')
            ->nullable();

            $table->date('set_to_draft_date')
            ->nullable();

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['stock_level_id'], 'stock_adjustment_stock_level_id_idx');
            $table->index(['stock_adjustment_status'], 'stock_adjustment_stock_adjustment_status_idx');
            $table->index(['adjustment_type'], 'stock_adjustment_adjustment_type_idx');
            $table->index(['stock_adjustment_reason_id'], 'stock_adjustment_stock_adjustment_reason_id_idx');
            $table->index(['for_approval_date'], 'stock_adjustment_for_approval_date_idx');
            $table->index(['approved_date'], 'stock_adjustment_approved_date_idx');
            $table->index(['cancellation_date'], 'stock_adjustment_cancellation_date_idx');
            $table->index(['set_to_draft_date'], 'stock_adjustment_set_to_draft_date_idx');
        });

        /* =============================================================================================
            TABLE: Stock Transfer
        ============================================================================================= */

        Schema::create('stock_transfer', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_level_from_id')
            ->constrained('stock_level')
            ->cascadeOnDelete();

            $table->foreignId('stock_level_to_id')
            ->constrained('stock_level')
            ->cascadeOnDelete();

            $table->enum('stock_transfer_status', ['Draft', 'For Approval', 'Approved', 'Cancelled'])
            ->default('Draft');

            $table->double('quantity')
            ->default(0);

            $table->foreignId('stock_transfer_reason_id')
            ->nullable()
            ->constrained('stock_transfer_reason')
            ->nullOnDelete();
            
            $table->string('stock_transfer_reason_name')
            ->nullable();

            $table->string('remarks')
            ->nullable();

            $table->date('for_approval_date')
            ->nullable();

            $table->date('approved_date')
            ->nullable();

            $table->date('cancellation_date')
            ->nullable();

            $table->date('set_to_draft_date')
            ->nullable();

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['stock_level_from_id'], 'stock_transfer_stock_level_from_id_idx');
            $table->index(['stock_level_to_id'], 'stock_transfer_stock_level_to_id_idx');
            $table->index(['stock_transfer_status'], 'stock_transfer_stock_transfer_status_idx');
            $table->index(['stock_transfer_reason_id'], 'stock_transfer_stock_transfer_reason_id_idx');
            $table->index(['for_approval_date'], 'stock_transfer_for_approval_date_idx');
            $table->index(['approved_date'], 'stock_transfer_approved_date_idx');
            $table->index(['cancellation_date'], 'stock_transfer_cancellation_date_idx');
            $table->index(['set_to_draft_date'], 'stock_transfer_set_to_draft_date_idx');
        });

        /* =============================================================================================
            TABLE: Stock Movement
        ============================================================================================= */

        Schema::create('stock_movement', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_level_id')
            ->constrained('stock_level')
            ->cascadeOnDelete();

            $table->enum('movement_type', ['In', 'Out', 'Transfer In', 'Transfer Out', 'Adjustment', 'Return', 'Loan Issue', 'Loan Return']);

            $table->double('quantity')
            ->default(0);

            $table->enum('reference_type', ['Purchase Order', 'Batch Tracking', 'Stock Adjustment', 'POS Sale', 'Transfer Ticket', 'Inventory Audit', 'Borrow Agreement']);
            $table->string('reference_id');

            $table->string('remarks');

            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['stock_level_id'], 'stock_movement_stock_level_id_idx');
            $table->index(['movement_type'], 'stock_movement_movement_type_idx');
            $table->index(['reference_type'], 'stock_movement_reference_type_idx');
            $table->index(['reference_id'], 'stock_movement_reference_id_idx');
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
        // 1. Disable constraints
        Schema::disableForeignKeyConstraints();

        // 2. Now you can drop them in any order
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('navigation_menu_route');
        Schema::dropIfExists('role_user_account');
        Schema::dropIfExists('role_system_action_permission');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('upload_setting_file_extension');
        Schema::dropIfExists('file_extension');
        Schema::dropIfExists('product_category_map');
        Schema::dropIfExists('product_attribute');
        Schema::dropIfExists('product_bom');
        Schema::dropIfExists('product_addon');
        Schema::dropIfExists('product');
        Schema::dropIfExists('attribute_value');
        Schema::dropIfExists('unit');
        Schema::dropIfExists('unit_conversion');
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
        Schema::dropIfExists('stock_transfer_reason');
        Schema::dropIfExists('stock_level');
        Schema::dropIfExists('stock_batch');
        Schema::dropIfExists('stock_adjustment');
        Schema::dropIfExists('stock_transfer');
        Schema::dropIfExists('stock_movement');
        Schema::dropIfExists('nationality');
        Schema::dropIfExists('currency');
        Schema::dropIfExists('country');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');

        // 3. Re-enable constraints
        Schema::enableForeignKeyConstraints();
    }
};
