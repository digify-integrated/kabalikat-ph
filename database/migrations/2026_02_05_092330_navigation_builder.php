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
        Schema::create('app_module', function (Blueprint $table) {
            $table->id();
            $table->string('app_module_name');
            $table->string('app_module_description')->nullable();
            $table->string('app_logo')->nullable();
            $table->integer('menu_item_id')->nullable();
            $table->integer('order_sequence')->default(0);
            $table->timestamps();
        });

        Schema::create('navigation_menu', function (Blueprint $table) {
            $table->id();
            $table->string('navigation_menu_name');
            $table->string('navigation_menu_icon')->nullable();
            $table->string('route');
            $table->integer('app_module_id');
            $table->integer('parent_id')->nullable();
            $table->integer('order_sequence')->default(0);
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
    }
};
