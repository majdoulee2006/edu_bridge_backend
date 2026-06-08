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
        Schema::table('users', function (Blueprint $table) {
            // إضافة عمود الصورة، وسمحنا يكون فارغ (nullable) لأنو مو كل الطلاب عندهم صور
            // ويفضل نحطه بعد الإيميل عشان يكون ترتيب الجدول منطقي
            $table->string('avatar')->nullable()->after('email'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // في حال عملنا تراجع (rollback)، نحذف العمود
            $table->dropColumn('avatar');
        });
    }
};