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
            // 🌟 إضافة الحقول الخاصة بالعذر
            $table->text('excuse_text')->nullable()->after('attendance_date');
            $table->string('excuse_attachment')->nullable()->after('excuse_text');
            
            // حالة العذر (لا يوجد، قيد المراجعة، مقبول، مرفوض)
            $table->enum('excuse_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('excuse_attachment');
        });
    }

    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['excuse_text', 'excuse_attachment', 'excuse_status']);
        });
    }

    
};
