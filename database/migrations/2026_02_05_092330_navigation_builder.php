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
        Schema::create('app', function (Blueprint $table) {
            $table->id();
            $table->string('app_name');
            $table->string('app_description')->nullable();
            $table->string('app_version')->default('1.0.0');
            $table->string('app_logo')->nullable();
            $table->bigInteger('navigation_menu_id');
            $table->string('navigation_menu_name');
            $table->integer('order_sequence')->default(0);
            $table->timestamps();

            $table->index(['navigation_menu_id'], 'app_index_navigation_menu_id');
        });

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
            $table->timestamps();

            $table->index(['app_id'], 'navigation_menu_app_id');
            $table->index(['parent_navigation_menu_id'], 'navigation_menu_parent_navigation_menu_id');
        });

        Schema::create('navigation_menu_route', function (Blueprint $table) {
            $table->id();

            $table->foreignId('navigation_menu_id')
                ->constrained('navigation_menu')
                ->cascadeOnDelete();

            $table->enum('route_type', ['index', 'details', 'new', 'import'])->default('index');
            $table->string('view_file');
            $table->string('js_file');
            $table->timestamps();

            $table->index(['navigation_menu_id', 'route_type'], 'navigation_menu_route_app_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_menu_route');
        Schema::dropIfExists('navigation_menu');
        Schema::dropIfExists('app');
    }
};
