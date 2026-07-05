<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class HODController extends Controller
{
    /**
     * جلب جميع طلبات الإجازة المعلقة (للطلاب والمدربين)
     */
    public function getLeaveRequests()
    {
        try {
            $requests = DB::table('leave_requests')
                ->leftJoin('students', 'leave_requests.student_id', '=', 'students.student_id')
                ->leftJoin('teachers', 'leave_requests.teacher_id', '=', 'teachers.teacher_id')
                ->leftJoin('users as student_users', 'students.user_id', '=', 'student_users.user_id')
                ->leftJoin('users as teacher_users', 'teachers.user_id', '=', 'teacher_users.user_id')
                ->select(
                    'leave_requests.*',
                    'student_users.full_name as student_name',
                    'teacher_users.full_name as teacher_name'
                )
                ->where('leave_requests.status', 'pending')
                ->get();

            return response()->json($requests);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * تحديث حالة طلب الإجازة (قبول/رفض)
     */
    public function updateLeaveStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        try {
            DB::table('leave_requests')
                ->where('id', $id)
                ->update(['status' => $request->status, 'updated_at' => now()]);

            return response()->json(['message' => 'Status updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * جلب قائمة المدربين والطلاب لإنشاء طلب تقرير
     */
    public function getStaffAndStudents()
    {
        try {
            $trainers = DB::table('teachers')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->select('teachers.teacher_id', 'users.full_name')
                ->get();

            $students = DB::table('students')
                ->join('users', 'students.user_id', '=', 'students.user_id')
                ->select('students.student_id', 'users.full_name')
                ->get();

            return response()->json([
                'trainers' => $trainers,
                'students' => $students
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * إنشاء طلب تقرير جديد مرسل للمدرب
     */
    public function storeReportRequest(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,teacher_id',
            'student_id' => 'required|exists:students,student_id',
            'report_type' => 'required|in:academic,behavioral',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::table('report_requests')->insert([
                'head_id' => auth()->id(),
                'teacher_id' => $request->teacher_id,
                'student_id' => $request->student_id,
                'report_type' => $request->report_type,
                'notes' => $request->notes,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Report request sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * جلب التقارير التي تم تسليمها لرئيس القسم
     */
    public function getReceivedReports()
    {
        try {
            $reports = DB::table('report_requests')
                ->join('performance_reports', 'report_requests.student_id', '=', 'performance_reports.student_id')
                ->join('students', 'report_requests.student_id', '=', 'students.student_id')
                ->join('users as student_users', 'students.user_id', '=', 'student_users.user_id')
                ->join('teachers', 'report_requests.teacher_id', '=', 'teachers.teacher_id')
                ->join('users as teacher_users', 'teachers.user_id', '=', 'teacher_users.user_id')
                ->select(
                    'report_requests.id as request_id',
                    'performance_reports.*',
                    'student_users.full_name as student_name',
                    'teacher_users.full_name as teacher_name'
                )
                ->where('report_requests.head_id', auth()->id())
                ->where('report_requests.status', 'completed')
                ->orderBy('performance_reports.created_at', 'desc')
                ->get();

            return response()->json($reports);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // إضافة حساب جديد (مدرب، طالب، أو ولي أمر)
    public function storeAccount(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:teacher,student,parent',
            'specialization' => 'required_if:role,teacher|string',
            'student_code' => 'required_if:role,student|string|unique:students,student_code',
            'level' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $rolesMap = [
                'teacher' => 2,
                'student' => 3,
                'parent' => 4
            ];

            $username = explode('@', $request->email)[0] . rand(10, 99);

            $userId = DB::table('users')->insertGetId([
                'full_name' => $request->full_name,
                'username' => $username,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'role_id' => $rolesMap[$request->role],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($request->role === 'teacher') {
                DB::table('teachers')->insert([
                    'user_id' => $userId,
                    'specialization' => $request->specialization,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($request->role === 'student') {
                $studentId = DB::table('students')->insertGetId([
                    'user_id' => $userId,
                    'student_code' => $request->student_code,
                    'level' => $request->level,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \App\Models\Student::autoAssignAdvisor($studentId);
            } elseif ($request->role === 'parent') {
                DB::table('parents')->insert([
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Account created successfully',
                'user_id' => $userId
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // جلب قائمة الحسابات حسب النوع (مدرب، طالب، أهل)
    public function getAccounts(Request $request)
    {
        $role = $request->query('role');
        
        try {
            $query = DB::table('users');

            if ($role === 'teacher') {
                $query->join('teachers', 'users.user_id', '=', 'teachers.user_id')
                      ->select('users.*', 'teachers.specialization', 'teachers.teacher_id');
            } elseif ($role === 'student') {
                $query->join('students', 'users.user_id', '=', 'students.user_id')
                      ->select('users.*', 'students.student_code', 'students.level', 'students.student_id');
            } elseif ($role === 'parent') {
                $query->join('parents', 'users.user_id', '=', 'parents.user_id')
                      ->select('users.*', 'parents.parent_id');
            } else {
                return response()->json(['message' => 'Invalid role'], 400);
            }

            $users = $query->orderBy('users.created_at', 'desc')->get();

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * جلب جميع المواد (الكورسات)
     */
    public function getCourses()
    {
        try {
            $courses = DB::table('courses')->select('course_id', 'title')->get();
            return response()->json($courses);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // جلب بيانات الملف الشخصي لرئيس القسم
    public function getProfile()
    {
        try {
            $user = auth()->user();
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // جلب الإعلانات الخاصة برئيس القسم أو الموجهة له
    public function getAnnouncements()
    {
        try {
            $userId = auth()->id();
            $announcements = DB::table('announcements')
                ->where('user_id', $userId)
                ->orWhere('target_audience', 'all')
                ->orWhere('target_audience', 'heads')
                ->orderBy('created_at', 'desc')
                ->get();

            // إضافة رابط الصورة الكامل
            $announcements->transform(function ($item) {
                if ($item->image_path) {
                    $item->image_url = asset('storage/' . $item->image_path);
                } else {
                    $item->image_url = null;
                }
                return $item;
            });

            return response()->json($announcements);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // إنشاء إعلان جديد (مع دعم الصور)
    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string',
            'target_audience' => 'required|in:all,teachers,students,parents',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // زيادة الحجم لـ 5MB
        ]);

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('announcements', 'public');
            }

            $content = $request->content;
            $title = $request->title ?? (mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : ''));

            $id = DB::table('announcements')->insertGetId([
                'user_id' => auth()->id(),
                'title' => $title,
                'category' => $request->category ?? 'عام',
                'content' => $content,
                'image_path' => $imagePath,
                'target_audience' => $request->target_audience,
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Announcement created successfully', 'id' => $id], 201);
        } catch (\Exception $e) {
            \Log::error('Announcement failed: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
