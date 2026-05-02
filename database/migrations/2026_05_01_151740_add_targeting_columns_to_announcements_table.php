<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('announcements', function (Blueprint $table) {
            // الفئة المستهدفة: (student, parent, teacher, boss) إذا كان Null يعني للكل
            $table->string('target_role')->nullable()->after('type'); 
            
            // القسم المستهدف (إذا كان الإعلان لقسم معين)
            $table->unsignedBigInteger('department_id')->nullable()->after('target_role');
            
            // السنة الدراسية (إذا كان الإعلان لسنة معينة)
            $table->string('academic_year')->nullable()->after('department_id');
        });
    }

    public function down()
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['target_role', 'department_id', 'academic_year']);
        });
    }
};
