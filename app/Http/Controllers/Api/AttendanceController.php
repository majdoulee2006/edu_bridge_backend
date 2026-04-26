<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\LeaveRequest;

class AttendanceController extends Controller
{
    // 🌟 1. جلب سجل الحضور والغياب للطالب الحالي
    public function getAttendanceHistory()
    {
        $studentId = auth()->user()->user_id;

        // جلب السجل مع اسم المادة (من علاقة lesson إذا كنتِ رابطتيها)
        // إذا مافي علاقة lesson، فيك تجيبي البيانات مباشرة
        $attendances = Attendance::with('lesson')
            ->where('student_id', $studentId)
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->attendance_id,
                    'subject' => $record->lesson->subject_name ?? 'مادة عامة', // عدليها حسب اسم عمود المادة بجدول lessons
                    'date' => $record->attendance_date,
                    'status' => $record->status, // present, absent, late
                    'excuse_status' => $record->excuse_status, // none, pending, approved, rejected
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $attendances
        ]);
    }

    // 🌟 2. تقديم طلب إجازة (مستقبلية)
    public function submitLeaveRequest(Request $request)
    {
        $request->validate([
            'type' => 'required|in:full_day,hourly',
            'date' => 'required|date',
            'reason' => 'required|string',
        ]);

        $studentId = auth()->user()->user_id;

        $leaveRequest = LeaveRequest::create([
            'student_id' => $studentId,
            'type' => $request->type,
            'date' => $request->date,
            'reason' => $request->reason,
            'status' => 'pending', // الطلب دائماً يبدأ قيد المراجعة
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال طلب الإجازة بنجاح، وهو قيد المراجعة.',
            'data' => $leaveRequest
        ], 201);
    }

    // 🌟 3. تقديم عذر لغياب غير مبرر (سابق)
    public function submitExcuse(Request $request, $attendance_id)
    {
        $request->validate([
            'excuse_text' => 'required|string',
            // يمكنك إضافة تحقق للملفات إذا أردتِ: 'excuse_attachment' => 'nullable|file|mimes:jpg,png,pdf|max:2048'
        ]);

        $studentId = auth()->user()->user_id;

        // البحث عن سجل الغياب الخاص بهذا الطالب
        $attendance = Attendance::where('attendance_id', $attendance_id)
            ->where('student_id', $studentId)
            ->where('status', 'absent')
            ->first();

        if (!$attendance) {
            return response()->json(['status' => 'error', 'message' => 'سجل الغياب غير موجود أو لا يخصك.'], 404);
        }

        // تحديث السجل بالعذر
        $attendance->excuse_text = $request->excuse_text;
        $attendance->excuse_status = 'pending'; // تحويل حالة العذر لقيد المراجعة

        // 🌟 إذا كان هناك ملف مرفق (اختياري)
        if ($request->hasFile('excuse_attachment')) {
            $file = $request->file('excuse_attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/excuses'), $filename);
            $attendance->excuse_attachment = 'uploads/excuses/' . $filename;
        }

        $attendance->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال العذر بنجاح، بانتظار موافقة الإدارة.',
        ]);
    }
    // 🌟 4. دالة للمدرب: توليد باركود جديد لجلسة تفقد
    public function generateQrToken(Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|exists:lessons,lesson_id', // تأكدي من اسم الحقل
            'duration_minutes' => 'integer|min:1|max:60' // مدة صلاحية الباركود (افتراضي 5 دقائق)
        ]);

        $duration = $request->duration_minutes ?? 5;

        // توليد توكن عشوائي وفريد
        $qrToken = 'QR_' . strtoupper(uniqid()) . '_' . bin2hex(random_bytes(4));

        $session = \App\Models\AttendanceSession::create([
            'lesson_id' => $request->lesson_id,
            'qr_token' => $qrToken,
            'expires_at' => now()->addMinutes($duration),
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم بدء جلسة التفقد بنجاح.',
            'data' => [
                'qr_token' => $qrToken,
                'expires_at' => $session->expires_at->format('Y-m-d H:i:s'),
                'expires_in_seconds' => $duration * 60,
            ]
        ]);
    }

    // 🌟 5. دالة للطالب: مسح الباركود وتسجيل الحضور
    public function scanQrAndAttend(Request $request)
    {
        $request->validate([
            'qr_token' => 'required|string',
        ]);

        $studentId = auth()->user()->user_id;

        // البحث عن الجلسة بواسطة التوكن
        $session = \App\Models\AttendanceSession::where('qr_token', $request->qr_token)
            ->where('is_active', true)
            ->where('expires_at', '>', now()) // التأكد من أن الوقت لم ينتهِ
            ->first();

        if (!$session) {
            return response()->json([
                'status' => 'error',
                'message' => 'الباركود غير صالح أو انتهت صلاحيته.'
            ], 400);
        }

        // التحقق مما إذا كان الطالب قد سجل حضوره مسبقاً في هذه الجلسة
        $existingAttendance = Attendance::where('student_id', $studentId)
            ->where('lesson_id', $session->lesson_id)
            ->whereDate('attendance_date', today())
            ->first();

        if ($existingAttendance && $existingAttendance->status === 'present') {
            return response()->json([
                'status' => 'error',
                'message' => 'لقد قمت بتسجيل حضورك مسبقاً.'
            ], 400);
        }

        // تسجيل الحضور أو تحديث السجل إذا كان موجوداً كغائب
        if ($existingAttendance) {
            $existingAttendance->update(['status' => 'present', 'excuse_status' => 'none']);
        } else {
            Attendance::create([
                'student_id' => $studentId,
                'lesson_id' => $session->lesson_id,
                'status' => 'present',
                'attendance_date' => today(),
                'excuse_status' => 'none',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الحضور بنجاح!',
        ]);
    }
}