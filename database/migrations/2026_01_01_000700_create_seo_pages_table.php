<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_pages', function (Blueprint $table) {
            $table->id();
            // city | governorate | specialty | city_specialty | governorate_specialty | service | custom
            $table->string('page_type', 48);
            $table->string('path')->unique();
            $table->foreignId('governorate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('meta_title_ar')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->text('meta_description_ar')->nullable();
            $table->text('meta_description_en')->nullable();
            $table->string('h1_ar')->nullable();
            $table->string('h1_en')->nullable();
            $table->text('intro_content_ar')->nullable();
            $table->text('intro_content_en')->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->unsignedTinyInteger('sitemap_priority')->default(5);
            $table->timestamps();

            $table->index(['page_type', 'is_indexable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
    }
};
