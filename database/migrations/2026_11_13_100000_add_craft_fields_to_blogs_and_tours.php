<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->unsignedBigInteger('craft_id')->nullable()->unique()->after('id');
            $table->timestamp('published_at')->nullable()->index();
            $table->json('tags')->nullable();
        });

        Schema::table('tours', function (Blueprint $table) {
            $table->unsignedBigInteger('craft_id')->nullable()->unique()->after('id');
            $table->timestamp('published_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['craft_id', 'published_at', 'tags']);
        });
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['craft_id', 'published_at']);
        });
    }
};
