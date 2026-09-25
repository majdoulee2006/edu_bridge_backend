<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminStudentServiceController extends Controller
{
    /**
     * الخدمات الطلابية للإدارة
     */
    public function studentServices()
    {
        // جلب الطلبات التي وصلت للإدارة أو انتهت
        $requests = \App\Models\StudentRequest::with(['student.user', 'student.program.department'])
                    ->whereIn('status', ['pending_admin', 'completed'])
                    ->orderBy('created_at', 'desc')
                    ->get();
        return view('admin.student-services', compact('requests'));
    }

    public function processStudentService(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:approved,rejected',
            'notes' => 'required|string|max:1000' // قرار الإدارة يجب أن يحوي ملاحظات
        ]);

        $studentReq = \App\Models\StudentRequest::with('student.user')->findOrFail($id);

        $studentReq->admin_decision = $request->decision;
        $studentReq->admin_notes = $request->notes;

        // قرار الإدارة هو النهائي
        $studentReq->status = 'completed';

        $studentReq->save();

        $isTranscript = $studentReq->type === 'document' ||
                        str_contains($studentReq->details ?? '', 'كشف علامات') ||
                        str_contains($studentReq->details ?? '', 'كشف درجات');

        $studentName = $studentReq->student->user->full_name ?? 'الطالب';

        if ($request->decision === 'approved') {
            if ($isTranscript) {
                // 1. إرسال إشعار فوري لموظفي الشؤون لتوجيههم للمسار الأكاديمي مع فلترة وتحديد الطالب مباشرة
                $affairsUsers = \App\Models\User::where('role_id', 6)->where('status', 'active')->pluck('user_id');
                $academicPathUrl = route('affairs.course_weights') . '?student_id=' . $studentReq->student_id;

                foreach ($affairsUsers as $affairsUserId) {
                    \App\Models\Notification::create([
                        'user_id'    => $affairsUserId,
                        'sender_id'  => auth()->id(),
                        'title'      => "موافقة الإدارة على كشف علامات: {$studentName} 🎓",
                        'message'    => "وافقت إدارة المعهد على طلب كشف العلامات (#{$studentReq->id}) للطالب ({$studentName}). انقر هنا للانتقال إلى المسار الأكاديمي لإصدار ومشاركة كشف الدرجات الرقمي المعتمد مع الطالب.",
                        'type'       => 'transcript_approved',
                        'category'   => 'administrative',
                        'related_id' => $studentReq->student_id,
                        'is_read'    => false,
                    ]);
                }

                // 2. إشعار الطالب بموافقة الإدارة وأن النسخة قيد التوليد والمشاركة من قبل الشؤون
                \App\Models\Notification::create([
                    'user_id'    => $studentReq->student->user_id,
                    'sender_id'  => auth()->id(),
                    'title'      => 'الموافقة على طلب كشف العلامات 🎓',
                    'message'    => "وافقت إدارة المعهد على طلب استخراج كشف العلامات الخاص بك (#{$studentReq->id}). تم إحالة الطلب لشؤون الطلاب لإصدار النسخة الرقمية المعتمدة ومشاركتها معك فوراً داخل التطبيق.\nملاحظات الإدارة: {$request->notes}",
                    'type'       => 'student_service',
                    'category'   => 'administrative',
                    'related_id' => $studentReq->student_id,
                    'is_read'    => false,
                ]);
            } else {
                $adminMsg = "صدر القرار النهائي بشأن طلبك (#{$studentReq->id}) من قبل إدارة المعهد: (مقبول بنجاح ✅).\nملاحظات الإدارة: {$request->notes}";
                \App\Models\Notification::create([
                    'user_id'    => $studentReq->student->user_id,
                    'sender_id'  => auth()->id(),
                    'title'      => 'الموافقة على طلبك الإداري ✅',
                    'message'    => $adminMsg,
                    'type'       => 'student_service',
                    'category'   => 'administrative',
                    'related_id' => $studentReq->id,
                    'is_read'    => false,
                ]);
            }
        } else {
            // حالة الرفض
            $rejectTitle = $isTranscript ? 'قرار الإدارة بشأن طلب كشف العلامات ❌' : 'قرار الإدارة بشأن طلبك ❌';
            $rejectMsg = "صدر القرار النهائي بشأن طلبك (#{$studentReq->id}) من قبل إدارة المعهد: (مرفوض ❌).\nالسبب وملاحظات الإدارة: {$request->notes}";
            \App\Models\Notification::create([
                'user_id'    => $studentReq->student->user_id,
                'sender_id'  => auth()->id(),
                'title'      => $rejectTitle,
                'message'    => $rejectMsg,
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $studentReq->id,
                'is_read'    => false,
            ]);
        }

        return back()->with('success', 'تم اتخاذ القرار النهائي بنجاح وتم إرسال الإشعارات اللازمة للشؤون والطالب.');
    }
}
