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

            $table->index(['reference_id', 'table_name'], 'audit_log_index');
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

            $table->index(['app_id'], 'navigation_menu_app_id');
            $table->index(['parent_navigation_menu_id'], 'navigation_menu_parent_navigation_menu_id');
        });

        /* =============================================================================================
            TABLE: NAVIGATION MENU ROUTE
        ============================================================================================= */

        Schema::create('navigation_menu_route', function (Blueprint $table) {
            $table->id();

            $table->foreignId('navigation_menu_id')
                ->constrained('navigation_menu')
                ->cascadeOnDelete();

            $table->enum('route_type', ['index', 'details', 'new', 'import'])->default('index');
            $table->string('view_file');
            $table->string('js_file');
            $table->foreignId('last_log_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['navigation_menu_id', 'route_type'], 'navigation_menu_route_app_id');
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

            $table->index(['file_type_id'], 'file_extension_file_type_id');
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

            $table->index(['upload_setting_id', 'file_extension_id'], 'upload_setting_file_extension_idx');
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
        Schema::dropIfExists('role_user_account');
        Schema::dropIfExists('role_system_action_permission');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('system_action');
        Schema::dropIfExists('role');
        Schema::dropIfExists('upload_setting_file_extension');
        Schema::dropIfExists('upload_setting');
        Schema::dropIfExists('file_extension');
        Schema::dropIfExists('file_type');
        Schema::dropIfExists('navigation_menu_route');
        Schema::dropIfExists('navigation_menu');
        Schema::dropIfExists('app');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
