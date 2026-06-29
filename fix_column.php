<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->handleRequest(\Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (!Schema::hasColumn('university_ids', 'telegram_chat_id')) {
    Schema::table('university_ids', function (Blueprint $table) {
        $table->string('telegram_chat_id')->nullable()->after('role');
    });
    echo "✅ Column 'telegram_chat_id' added!\n";
} else {
    echo "Column already exists.\n";
}
