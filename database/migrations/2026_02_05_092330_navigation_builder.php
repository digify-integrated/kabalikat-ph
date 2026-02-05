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
            $table->string('app_logo')->nullable();
            $table->integer('navigation_menu_id')->nullable();
            $table->integer('navigation_menu_name');
            $table->integer('order_sequence')->default(0);
            $table->timestamps();
        });

        Schema::create('navigation_menu', function (Blueprint $table) {
            $table->id();
            $table->string('navigation_menu_name');
            $table->string('navigation_menu_icon')->nullable();
            $table->integer('app_id');
            $table->string('app_name');
            $table->integer('app_module_id');
            $table->integer('parent_navigation_menu_id')->nullable();
            $table->string('parent_navigation_menu_name')->nullable();
            $table->integer('order_sequence')->default(0);
            $table->timestamps();
        });

        Schema::create('navigation_menu_route', function (Blueprint $table) {
            $table->id();
            $table->string('navigation_menu_id');
            $table->string('route_key')->default('index');
            $table->integer('view');
            $table->string('js_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_module');
        Schema::dropIfExists('navigation_menu');
        Schema::dropIfExists('navigation_menu_route');
    }
};
