<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::table('performance_reports', function (Blueprint $table) {
    if (!Schema::hasColumn('performance_reports', 'report_type')) {
        $table->enum('report_type', ['academic', 'behavioral'])->after('student_id')->default('academic');
        echo "Column 'report_type' added successfully.\n";
    } else {
        echo "Column 'report_type' already exists.\n";
    }
});
