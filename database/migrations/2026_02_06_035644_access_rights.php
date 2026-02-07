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
        Schema::create('role', function (Blueprint $table) {
            $table->id();
            $table->string('role_name');
            $table->string('role_description');
            $table->timestamps();
        });

        Schema::create('system_action', function (Blueprint $table) {
            $table->id();
            $table->string('system_action_name');
            $table->string('system_action_description');
            $table->timestamps();
        });

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

            $table->timestamps();

            $table->unique(['role_id', 'navigation_menu_id']);
        });

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
            $table->timestamps();

            $table->unique(['role_id', 'system_action_id']);
        });

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

            $table->timestamps();

            $table->unique(['role_id', 'user_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user_account');
        Schema::dropIfExists('role_system_action_permission');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('system_action');
        Schema::dropIfExists('role');
    }
};
