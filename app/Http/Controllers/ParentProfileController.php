<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class ParentProfileController extends Controller{
// 1. جلب الملف الشخصي الكامل للأب
    public function getFullProfile($id)
    {
        $parent = StudentParent::with('user')->where('parent_id', $id)->first();

        if ($parent && $parent->user) {
            return response()->json([
                'full_name'  => $parent->user->full_name,
                'email'      => $parent->user->email,
                'phone'      => $parent->phone_number ?? '050 123 4567',
                'address'    => $parent->address ?? 'غير محدد',
                'gender'     => $parent->user->gender ?? 'ذكر',
                'birth_date' => $parent->user->birth_date ?? '2002-05-15',
                'department' => $parent->user->department ?? 'IT',
            ]);
        }
        return response()->json(['message' => 'لم يتم العثور على البيانات'], 404);
    }

    // 2. جلب إشعارات الأب
    public function getNotifications($id)
    {
        $notifications = Notification::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    // 3. ربط طالب جديد بالأب (عن طريق الكود)
    public function linkStudent(Request $request)
    {
        $student = Student::where('student_code', $request->student_code)->first();
        if ($student) {
            $student->parent_id = 10; // يمكن استبداله بـ Auth::id() لاحقاً
            $student->save();
            return response()->json(['message' => 'success']);
        }
        return response()->json(['message' => 'not found'], 404);
    }

    // 4. جلب أبناء الأب للتجربة
    public function getChildren($id)
    {
        return Student::where('parent_id', $id)->with('user')->get();
    }

    // 5. جلب اسم الأب فقط
    public function getParentName($id)
    {
        $parent = DB::table('parents')
            ->join('users', 'parents.user_id', '=', 'users.user_id')
            ->where('parents.parent_id', $id)
            ->select('users.full_name')
            ->first();

        return $parent
            ? response()->json(['full_name' => $parent->full_name])
            : response()->json(['full_name' => 'اسم غير موجود'], 404);
    }

    // 6. إرسال إشعار تجريبي
    public function sendTestNotification()
    {
        return Notification::create([
            'user_id' => 10,
            'title'   => 'تنبيه غياب',
            'message' => 'نحيطكم علماً بأن الطالب أحمد قد غاب عن حصة الرياضيات اليوم.',
            'type'    => 'attendance',
        ]);
    }}
