<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('deleted_for_everyone');
            }
            if (!Schema::hasColumn('messages', 'disappears_after')) {
                $table->integer('disappears_after')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('messages', 'is_forwarded')) {
                $table->boolean('is_forwarded')->default(false)->after('disappears_after');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'disappears_after', 'is_forwarded']);
        });
    }
};
