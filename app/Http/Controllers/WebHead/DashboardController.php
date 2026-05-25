<?php

namespace App\Http\Controllers\WebHead;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Exam;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Fetch the actually logged-in HOD Profile
        $user = auth()->user();

        if (!$user) {
            return redirect('/login')->withErrors(['msg' => 'يجب تسجيل الدخول أولاً.']);
        }



        // 2. Fetch Schedules with relations
        $schedules = Schedule::with(['course', 'teacher.user'])
            ->get()
            ->groupBy('day_of_week');

        // 3. Fetch Exams
        $exams = Exam::with(['course'])->get();


        // 4. Fetch Trainers (Teachers)
        $trainers = Teacher::with('user')->take(10)->get();

        // 5. Fetch Students
        $students = Student::with('user')->take(10)->get();

        // 6. Fetch Announcements for Home Tab
        $announcements = \App\Models\Announcement::latest()->take(5)->get();

        return view('dashboard', compact('user', 'schedules', 'exams', 'trainers', 'students', 'announcements'));
    }

    public function sendOtp(Request $request)
    {
        $user = auth()->user();
        $otp = rand(1000, 9999);

        \App\Models\Otp::updateOrCreate(
            ['email' => $user->email],
            [
                'token' => $otp,
                'expires_at' => now()->addMinutes(10)
            ]
        );

        // In a real app, we would send email here. For now, we just return success.
        return response()->json(['success' => true, 'msg' => 'تم إرسال الرمز (تجريبياً: ' . $otp . ')']);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $otpCode = $request->input('otp');
        $phone = $request->input('phone');
        $email = $request->input('email');

        $otpRecord = \App\Models\Otp::where('email', $user->email)
            ->where('token', $otpCode)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            return response()->json(['success' => false, 'msg' => 'الرمز غير صحيح أو منتهي الصلاحية.']);
        }

        // Update User explicitly by ID to ensure it hits the DB
        $updated = \App\Models\User::where('user_id', $user->user_id)->update([
            'phone' => $phone,
            'email' => $email
        ]);

        if ($updated) {
            // Delete OTP after use
            $otpRecord->delete();
            return response()->json(['success' => true, 'msg' => 'تم تحديث البيانات بنجاح في قاعدة البيانات.']);
        }

        return response()->json(['success' => false, 'msg' => 'حدث خطأ أثناء محاولة التحديث.']);
    }
}



