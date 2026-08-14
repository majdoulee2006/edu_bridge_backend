<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'deleted_for_sender')) {
                $table->boolean('deleted_for_sender')->default(false)->after('is_delivered');
            }
            if (!Schema::hasColumn('messages', 'deleted_for_receiver')) {
                $table->boolean('deleted_for_receiver')->default(false)->after('deleted_for_sender');
            }
            if (!Schema::hasColumn('messages', 'deleted_for_everyone')) {
                $table->boolean('deleted_for_everyone')->default(false)->after('deleted_for_receiver');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['deleted_for_sender', 'deleted_for_receiver', 'deleted_for_everyone']);
        });
    }
};
