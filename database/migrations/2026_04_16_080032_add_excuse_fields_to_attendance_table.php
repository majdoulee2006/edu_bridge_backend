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
        Schema::table('attendance', function (Blueprint $table) {
            // 🌟 إضافة الحقول الخاصة بالعذر بعد التحقق من عدم وجودها
            if (!Schema::hasColumn('attendance', 'excuse_text')) {
                $table->text('excuse_text')->nullable()->after('attendance_date');
            }
            if (!Schema::hasColumn('attendance', 'excuse_attachment')) {
                $table->string('excuse_attachment')->nullable()->after('excuse_text');
            }
            if (!Schema::hasColumn('attendance', 'excuse_status')) {
                $table->enum('excuse_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('excuse_attachment');
            }
        });
    }

    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('attendance', 'excuse_text')) {
                $columns[] = 'excuse_text';
            }
            if (Schema::hasColumn('attendance', 'excuse_attachment')) {
                $columns[] = 'excuse_attachment';
            }
            if (Schema::hasColumn('attendance', 'excuse_status')) {
                $columns[] = 'excuse_status';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    
};
