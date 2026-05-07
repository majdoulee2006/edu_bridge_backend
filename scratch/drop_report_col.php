<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (Schema::hasColumn('performance_reports', 'report_type')) {
    Schema::table('performance_reports', function (Blueprint $table) {
        $table->dropColumn('report_type');
    });
    echo "Column dropped.\n";
} else {
    echo "Column does not exist.\n";
}
