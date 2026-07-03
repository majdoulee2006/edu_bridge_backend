<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // conversation lookup (sender↔receiver pair)
            $table->index(['sender_id', 'receiver_id'], 'idx_messages_conversation');
            // unread count per sender→receiver
            $table->index(['receiver_id', 'sender_id', 'is_read'], 'idx_messages_unread');
            // sorting / latest message per conversation
            $table->index(['sender_id', 'receiver_id', 'id'], 'idx_messages_latest');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_conversation');
            $table->dropIndex('idx_messages_unread');
            $table->dropIndex('idx_messages_latest');
        });
    }
};
