<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Student;

class AttendanceController extends Controller
{
    private function getStudent()
    {
        $user = auth()->user();
        return Student::where('user_id', $user->user_id)->first();
    }

    // 🌟 1. جلب سجل الحضور والغياب للطالب الحالي
    public function getAttendanceHistory()
    {
        $student = $this->getStudent();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'سجل الطالب غير موجود.'], 404);
        }

        $allRecords = Attendance::with(['lesson.course'])
            ->where('student_id', $student->student_id)
            ->orderBy('attendance_date', 'desc')
            ->get();

        $totalCount  = $allRecords->count();
        $presentCount = $allRecords->whereIn('status', ['present', 'حاضر'])->count();
        $absentCount  = $allRecords->whereIn('status', ['absent', 'غائب'])->count();
        $lateCount    = $allRecords->whereIn('status', ['late', 'متأخر'])->count();
        $attendanceRate = $totalCount > 0 ? round(($presentCount / $totalCount) * 100, 1) : 100.0;

        $attendances = $allRecords->map(function ($record) {
            return [
                'id'            => $record->attendance_id,
                'subject'       => $record->lesson->title ?? $record->lesson->subject_name ?? $record->lesson->course->title ?? 'مادة عامة',
                'course_name'   => $record->lesson->course->title ?? $record->lesson->title ?? 'مادة عامة',
                'date'          => $record->attendance_date ? \Carbon\Carbon::parse($record->attendance_date)->format('Y-m-d') : null,
                'formatted_date'=> $record->attendance_date ? \Carbon\Carbon::parse($record->attendance_date)->format('Y-m-d h:i A') : '',
                'status'        => $record->status,
                'excuse_status' => $record->excuse_status ?? 'none',
                'notes'         => $record->notes ?? null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'summary' => [
                'total_records'   => $totalCount,
                'present_count'   => $presentCount,
                'absent_count'    => $absentCount,
                'late_count'      => $lateCount,
                'attendance_rate' => $attendanceRate,
            ],
            'data' => $attendances
        ]);
    }

    // 🌟 2. تقديم طلب إجازة (مستقبلية)
    public function submitLeaveRequest(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:full_day,hourly',
            'date'   => 'required|date',
            'reason' => 'required|string',
        ]);

        $student = $this->getStudent();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'سجل الطالب غير موجود.'], 404);
        }

        $leaveRequest = LeaveRequest::create([
            'student_id' => $student->student_id,
            'type'       => $request->type,
            'date'       => $request->date,
            'reason'     => $request->reason,
            'status'     => 'pending',
        ]);
        
        // إشعار رئيس القسم بطلب الإجازة
        try {
            $studentName = $student->user->full_name ?? 'طالب';
            
            // إيجاد رئيس القسم الخاص بالطالب (بناءً على التخصص أو رئيس القسم العام)
            $hodId = null;
            if ($student->program_id) {
                $departmentId = \App\Models\Program::where('id', $student->program_id)->value('department_id');
                if ($departmentId) {
                    $hodId = \App\Models\Head::where('department_id', $departmentId)->value('user_id');
                }
            }
            if (!$hodId) {
                $hodId = \App\Models\User::where('role_id', 5)->value('user_id');
            }
            
            if ($hodId) {
                \App\Models\Notification::create([
                    'user_id'    => $hodId,
                    'sender_id'  => auth()->id(),
                    'title'      => 'طلب إجازة جديد',
                    'message'    => "قام الطالب $studentName بتقديم طلب إجازة جديد، يرجى مراجعته.",
                    'type'       => 'leave_request',
                    'related_id' => $leaveRequest->id,
                    'is_read'    => false,
                ]);
            }
            
            // إشعار موظف الشؤون أيضاً
            $affairsUserIds = \App\Models\User::where('role_id', 6)->pluck('user_id');
            foreach ($affairsUserIds as $affairsId) {
                \App\Models\Notification::create([
                    'user_id'    => $affairsId,
                    'sender_id'  => auth()->id(),
                    'title'      => 'طلب إجازة جديد',
                    'message'    => "قام الطالب $studentName بتقديم طلب إجازة جديد، يرجى مراجعته.",
                    'type'       => 'leave_request',
                    'related_id' => $leaveRequest->id,
                    'is_read'    => false,
                ]);
            }
        } catch (\Exception $e) {}

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إرسال طلب الإجازة بنجاح، وهو قيد المراجعة.',
            'data'    => $leaveRequest
        ], 201);
    }

    // 🌟 3. تقديم عذر لغياب غير مبرر (سابق)
    public function submitExcuse(Request $request, $attendance_id)
    {
        $request->validate([
            'excuse_text' => 'required|string',
        ]);

        $student = $this->getStudent();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'سجل الطالب غير موجود.'], 404);
        }

        $attendance = Attendance::where('attendance_id', $attendance_id)
            ->where('student_id', $student->student_id)
            ->where('status', 'absent')
            ->first();

        if (!$attendance) {
            return response()->json(['status' => 'error', 'message' => 'سجل الغياب غير موجود أو لا يخصك.'], 404);
        }

        $attendance->excuse_text = $request->excuse_text;
        $attendance->excuse_status = 'pending';

        if ($request->hasFile('excuse_attachment')) {
            $file = $request->file('excuse_attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/excuses'), $filename);
            $attendance->excuse_attachment = 'uploads/excuses/' . $filename;
        }

        $attendance->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إرسال العذر بنجاح، بانتظار موافقة الإدارة.',
        ]);
    }

    // 🌟 4. دالة للمدرب: توليد باركود جديد لجلسة تفقد
    public function generateQrToken(Request $request)
    {
        $request->validate([
            'lesson_id'        => 'required|exists:lessons,lesson_id',
            'duration_minutes' => 'integer|min:1|max:60'
        ]);

        $duration = $request->duration_minutes ?? 5;
        $qrToken  = 'QR_' . strtoupper(uniqid()) . '_' . bin2hex(random_bytes(4));

        $session = \App\Models\AttendanceSession::create([
            'lesson_id'  => $request->lesson_id,
            'qr_token'   => $qrToken,
            'expires_at' => now()->addMinutes($duration),
            'is_active'  => true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم بدء جلسة التفقد بنجاح.',
            'data'    => [
                'qr_token'           => $qrToken,
                'expires_at'         => $session->expires_at->format('Y-m-d H:i:s'),
                'expires_in_seconds' => $duration * 60,
            ]
        ]);
    }

    // 🌟 5. دالة للطالب: مسح الباركود وتسجيل الحضور
    public function scanQrAndAttend(Request $request)
    {
        $request->validate([
            'qr_token'  => 'required|string',
            'device_id' => 'required|string|max:255',
        ]);

        $student = $this->getStudent();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'الطالب غير موجود'], 404);
        }

        // ── التحقق من مطابقة جهاز الطالب ────────────────────────────────────
        if ($student->is_device_locked && !empty($student->device_id) && $student->device_id !== $request->device_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، لا يمكنك تسجيل الحضور من هذا الجهاز لأنه غير مقترن بحسابك.',
            ], 403);
        }

        // ── التحقق من صلاحية QR ──────────────────────────────────────────
        $session = \App\Models\AttendanceSession::where('qr_token', $request->qr_token)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            return response()->json([
                'status'  => 'error',
                'message' => 'الباركود غير صالح أو انتهت صلاحيته.',
            ], 400);
        }

        // ── التحقق من الحضور المسبق ───────────────────────────────────────
        $existingAttendance = Attendance::where('student_id', $student->student_id)
            ->where('lesson_id', $session->lesson_id)
            ->whereDate('attendance_date', today())
            ->first();

        if ($existingAttendance && $existingAttendance->status === 'present') {
            return response()->json([
                'status'  => 'error',
                'message' => 'لقد قمت بتسجيل حضورك مسبقاً.',
            ], 400);
        }

        // ── تسجيل الحضور ─────────────────────────────────────────────────
        if ($existingAttendance) {
            $existingAttendance->update(['status' => 'present', 'excuse_status' => 'none']);
        } else {
            Attendance::create([
                'student_id'      => $student->student_id,
                'lesson_id'       => $session->lesson_id,
                'status'          => 'present',
                'attendance_date' => today(),
                'excuse_status'   => 'none',
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تسجيل الحضور بنجاح!',
        ]);
    }
}