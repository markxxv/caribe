<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('property_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('property_countries')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();

            $table->unique(['country_id', 'slug']);
        });

        Schema::create('property_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('property_regions')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();

            $table->unique(['region_id', 'slug']);
        });

        Schema::create('property_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('property_cities')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();

            $table->unique(['city_id', 'slug']);
        });

        Schema::create('property_realestates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            $table->string('property_type')->nullable()->index();
            $table->string('operation')->nullable()->index();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('expenses', 15, 2)->nullable();
            $table->string('expenses_currency', 3)->nullable();

            // Zonaprop's listing "m² tot." can refer to the lot: keep only distinct areas.
            $table->decimal('land_area', 12, 2)->nullable();
            $table->decimal('covered_area', 12, 2)->nullable();
            $table->decimal('semi_covered_area', 12, 2)->nullable();
            $table->unsignedTinyInteger('rooms')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('toilets')->nullable();
            $table->unsignedTinyInteger('parking_spaces')->nullable();
            $table->string('parking_type')->nullable();
            $table->unsignedTinyInteger('floors')->nullable();
            $table->unsignedSmallInteger('floor_number')->nullable();
            $table->unsignedSmallInteger('construction_year')->nullable();
            $table->string('condition')->nullable();
            $table->string('orientation')->nullable();
            $table->decimal('land_front', 10, 2)->nullable();
            $table->decimal('land_depth', 10, 2)->nullable();

            $table->foreignId('country_id')->nullable()->constrained('property_countries')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('property_regions')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('property_cities')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('property_districts')->nullOnDelete();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->json('features')->nullable();
            $table->json('amenities')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->boolean('newbuild')->default(false)->index();
            $table->boolean('sold')->default(false)->index();

            $table->string('source_platform', 64)->nullable()->index();
            $table->string('source_url', 512)->nullable()->unique();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_realestates');
        Schema::dropIfExists('property_districts');
        Schema::dropIfExists('property_cities');
        Schema::dropIfExists('property_regions');
        Schema::dropIfExists('property_countries');
    }
};
