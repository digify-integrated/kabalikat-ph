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
            $table->bigInteger('navigation_menu_id');
            $table->integer('order_sequence')->default(0);
            $table->timestamps();
        });

        Schema::create('navigation_menu', function (Blueprint $table) {
            $table->id();
            $table->string('navigation_menu_name');
            $table->string('navigation_menu_icon')->nullable();
            $table->bigInteger('app_id');
            $table->bigInteger('parent_navigation_menu_id')->nullable();
            $table->integer('order_sequence')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_menu');
        Schema::dropIfExists('app');
    }
};
