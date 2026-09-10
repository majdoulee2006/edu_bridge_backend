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

        $studentReq = \App\Models\StudentRequest::findOrFail($id);

        $studentReq->admin_decision = $request->decision;
        $studentReq->admin_notes = $request->notes;

        // قرار الإدارة هو النهائي
        $studentReq->status = 'completed';

        $studentReq->save();

        $finalStatusText = $request->decision === 'approved' ? 'مقبول بنجاح ✅' : 'مرفوض ❌';
        $adminMsg = "صدر القرار النهائي بشأن طلبك (#{$studentReq->id}) من قبل إدارة المعهد: ($finalStatusText). يمكنك مراجعة تفاصيل وملاحظات القرار من صفحة الخدمات الطلابية.";

        // إرسال إشعار فوري للطالب بالقرار النهائي
        \App\Models\Notification::create([
            'user_id'  => $studentReq->student->user_id,
            'title'    => 'القرار النهائي بشأن طلبك',
            'message'  => $adminMsg,
            'type'     => 'student_service',
            'category' => 'administrative',
            'is_read'  => false,
        ]);

        return back()->with('success', 'تم اتخاذ القرار النهائي بنجاح وتم إغلاق الطلب وإرسال إشعار للطالب.');
    }
}
