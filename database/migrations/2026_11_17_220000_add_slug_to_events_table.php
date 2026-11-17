<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });

        DB::table('events')->select('id', 'title')->chunkById(200, function ($events) {
            foreach ($events as $event) {
                $base = rtrim(Str::substr(Str::slug($event->title), 0, 240), '-') ?: 'event';
                $slug = $base;
                $suffix = 1;

                while (DB::table('events')->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $suffix++;
                }

                DB::table('events')->where('id', $event->id)->update(['slug' => $slug]);
            }
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
