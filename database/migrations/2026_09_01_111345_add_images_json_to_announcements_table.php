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
        Schema::table('announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('announcements', 'images')) {
                $table->json('images')->nullable()->after('image');
            }
        });

        // Migrate existing single images into images JSON array
        try {
            $announcements = \Illuminate\Support\Facades\DB::table('announcements')->get();
            foreach ($announcements as $a) {
                $img = $a->image ?? $a->image_path ?? null;
                if ($img && empty($a->images)) {
                    \Illuminate\Support\Facades\DB::table('announcements')
                        ->where('announcement_id', $a->announcement_id ?? $a->id)
                        ->update(['images' => json_encode([$img])]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore if already migrated
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'images')) {
                $table->dropColumn('images');
            }
        });
    }
};
