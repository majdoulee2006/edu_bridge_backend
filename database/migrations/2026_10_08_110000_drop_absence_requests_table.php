<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * حذف جدول absence_requests القديم بعد أن صار leave_requests الجدول الوحيد لإجازات الطلاب.
 *
 * حارس أمان: إن وُجد في الجدول القديم سجل لم يُنسخ إلى leave_requests (المايغريشن
 * 2026_10_08_100000 تنسخه) تتوقف المايغريشن بخطأ واضح ولا يُحذف شيء.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('absence_requests')) {
            return;
        }

        if (Schema::hasTable('leave_requests')) {
            $notCopied = DB::table('absence_requests')
                ->join('students', 'absence_requests.student_id', '=', 'students.student_id')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('leave_requests')
                        ->whereColumn('leave_requests.student_id', 'students.user_id')
                        ->whereColumn('leave_requests.date', 'absence_requests.date')
                        ->whereColumn('leave_requests.reason', 'absence_requests.reason');
                })
                ->count();

            if ($notCopied > 0) {
                throw new RuntimeException(
                    "absence_requests still has {$notCopied} row(s) that were not copied to leave_requests. " .
                    'Run the migration 2026_10_08_100000_copy_absence_requests_into_leave_requests first; nothing was dropped.'
                );
            }
        }

        Schema::drop('absence_requests');
    }

    public function down(): void
    {
        // يعيد بنية الجدول فارغاً فقط (البيانات انتقلت إلى leave_requests)
        if (Schema::hasTable('absence_requests')) {
            return;
        }

        Schema::create('absence_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedBigInteger('student_id');
            $table->date('date');
            $table->text('reason');
            $table->string('document')->nullable();
            $table->string('status', 50)->default('pending_parent');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();
        });
    }
};
