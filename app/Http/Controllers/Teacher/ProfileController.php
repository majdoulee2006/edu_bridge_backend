<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Teacher;

class ProfileController extends Controller
{
    /**
     * عرض صفحة الملف الشخصي
     */
    public function show()
    {
        $user = Auth::user();
        // جلب بيانات المعلم المرتبطة
        $teacherData = Teacher::where('user_id', $user->user_id)->first();

        return view('teacher.profile', compact('user', 'teacherData'));
    }

    /**
     * تحديث البيانات (الإيميل والهاتف)
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // التحقق من البيانات
        $validator = Validator::make($request->all(), [
            // تأكدنا هنا أن الاستثناء يخص user_id لكي لا يعطيكِ خطأ أن الإيميل موجود مسبقاً عند تحديث بياناتكِ نفسها
            'email' => 'sometimes|required|email|unique:users,email,' . $user->user_id . ',user_id',
            'phone' => 'sometimes|nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'البيانات غير صالحة أو الإيميل مستخدم مسبقاً',
                'errors' => $validator->errors()
            ]);
        }

        // استخدام التحديث الصريح عبر الـ Model لضمان استهداف user_id الصحيح
        $updated = User::where('user_id', $user->user_id)->update($request->only(['email', 'phone']));

        if ($updated) {
            return response()->json([
                'success' => true, 
                'message' => 'تم تحديث البيانات بنجاح ✨'
            ]);
        }

        return response()->json([
            'success' => false, 
            'message' => 'لم يتم إجراء أي تعديلات أو حدث خطأ أثناء التحديث'
        ]);
    }

    /**
     * تغيير كلمة المرور
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'كلمة المرور الجديدة يجب أن تكون 8 محارف على الأقل'
            ]);
        }

        // التأكد من صحة كلمة المرور القديمة
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false, 
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ]);
        }

        // تحديث كلمة المرور الجديدة باستخدام الـ Model والـ user_id الصحيح
        User::where('user_id', $user->user_id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'تم تغيير كلمة المرور بنجاح 🛡️'
        ]);
    }
}