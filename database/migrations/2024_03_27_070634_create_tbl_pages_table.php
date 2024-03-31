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
        Schema::create('tbl_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id')->default(0);
            $table->unsignedBigInteger('site_id')->default(0);
            $table->unsignedBigInteger('user_id')->default(0);
            $table->string('page_name');
            $table->enum('page_type', [1, 2, 3, 4, 5])->default(1);
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->string('page_status')->nullable();
            $table->boolean('show_page_header_footer')->default(true);
            $table->enum('menu_visibility', [1, 2, 3])->default(1);
            $table->boolean('show_hide_menu')->default(true);
            $table->string('url_slug')->nullable();
            $table->string('page_title')->nullable();
            $table->text('page_description')->nullable();
            $table->boolean('hide_from_search_engines')->default(false);
            $table->string('link_url')->nullable();
            $table->unsignedBigInteger('order')->default(1);
            $table->boolean('open_in_new_window')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_pages');
    }
};
