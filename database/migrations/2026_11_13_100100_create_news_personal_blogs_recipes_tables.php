<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('craft_id')->nullable()->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('active')->default(true)->index();
            $table->json('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('personal_blogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('craft_id')->nullable()->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('active')->default(true)->index();
            $table->string('author_name')->nullable();
            $table->string('author_slug')->nullable();
            $table->text('author_resume')->nullable();
            $table->string('category_name')->nullable();
            $table->string('category_slug')->nullable()->index();
            $table->json('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('craft_id')->nullable()->unique();
            $table->foreignId('recipe_category_id')->nullable()->constrained('recipe_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('active')->default(true)->index();
            $table->json('content')->nullable();
            $table->json('tags')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('country_news', function (Blueprint $table) {
            $table->unsignedBigInteger('news_id');
            $table->unsignedBigInteger('country_id');
            $table->primary(['news_id', 'country_id']);
            $table->foreign('news_id')->references('id')->on('news')->cascadeOnDelete();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
        });

        Schema::create('country_personal_blog', function (Blueprint $table) {
            $table->unsignedBigInteger('personal_blog_id');
            $table->unsignedBigInteger('country_id');
            $table->primary(['personal_blog_id', 'country_id']);
            $table->foreign('personal_blog_id')->references('id')->on('personal_blogs')->cascadeOnDelete();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_personal_blog');
        Schema::dropIfExists('country_news');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('personal_blogs');
        Schema::dropIfExists('news');
    }
};
