<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('path')->unique();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_published')->default(true);
            $table->string('heading_ar');
            $table->string('heading_en');
            $table->text('intro_ar')->nullable();
            $table->text('intro_en')->nullable();
            $table->longText('body_ar')->nullable();
            $table->longText('body_en')->nullable();
            $table->string('meta_title_ar')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_description_ar', 320)->nullable();
            $table->string('meta_description_en', 320)->nullable();
            $table->unsignedTinyInteger('sitemap_priority')->default(5);
            $table->boolean('is_indexable')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'is_indexable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_pages');
    }
};
