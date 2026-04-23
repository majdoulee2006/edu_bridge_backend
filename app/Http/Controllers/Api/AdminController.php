<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Parents;
use App\Models\Course;
use App\Models\Department;
use App\Models\Semester;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Exam;

class AdminController extends Controller
{
    /**
     * لوحة تحكم الأدمن - Dashboard
     * GET /api/admin/dashboard
     */
    public function dashboard(Request $request)
    {
        $statistics = [
            'total_students' => User::where('role', 'student')->count(),
            'total_teachers' => User::where('role', 'teacher')->count(),
            'total_parents' => User::where('role', 'parent')->count(),
            'total_courses' => Course::count(),
            'total_departments' => Department::count(),
            'active_semester' => Semester::where('is_active', true)->first(),
            'recent_users' => User::latest()->limit(10)->get()->map(function($user) {
                return [
                    'id' => $user->user_id,
                    'name' => $user->full_name,
                    'role' => $user->role,
                    'created_at' => $user->created_at->diffForHumans(),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ], 200);
    }

    // ========== إدارة المستخدمين ==========

    /**
     * جلب جميع المستخدمين
     * GET /api/admin/users
     */
    public function getUsers(Request $request)
    {
        $users = User::with(['student', 'teacher', 'parents'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users
        ], 200);
    }

    /**
     * جلب مستخدم محدد
     * GET /api/admin/users/{userId}
     */
    public function getUser(Request $request, $userId)
    {
        $user = User::with(['student', 'teacher', 'parents'])->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ], 200);
    }

    /**
     * إنشاء مستخدم جديد
     * POST /api/admin/users
     */
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,teacher,student,parent,head',
            'department' => 'nullable|string',
            'academic_year' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'department' => $request->department,
            'academic_year' => $request->academic_year,
            'status' => 'active',
        ]);

        // إنشاء السجل في الجدول المخصص حسب الدور
        if ($request->role == 'student') {
            Student::create([
                'user_id' => $user->user_id,
                'student_code' => $request->username,
                'level' => $request->academic_year,
            ]);
        } elseif ($request->role == 'teacher') {
            Teacher::create([
                'user_id' => $user->user_id,
                'specialization' => $request->department,
            ]);
        } elseif ($request->role == 'parent') {
            Parents::create([
                'user_id' => $user->user_id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المستخدم بنجاح',
            'data' => $user
        ], 201);
    }

    /**
     * تحديث مستخدم
     * PUT /api/admin/users/{userId}
     */
    public function updateUser(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId . ',user_id',
            'phone' => 'sometimes|string|max:20',
            'department' => 'nullable|string',
            'academic_year' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['full_name', 'email', 'phone', 'department', 'academic_year', 'status']));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستخدم بنجاح',
            'data' => $user
        ], 200);
    }

    /**
     * حذف مستخدم
     * DELETE /api/admin/users/{userId}
     */
    public function deleteUser(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        // لا يمكن حذف الأدمن الرئيسي
        if ($user->username == 'admin_main') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف حساب المدير الرئيسي'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح'
        ], 200);
    }

    // ========== إدارة الدورات ==========

    /**
     * جلب جميع الدورات
     * GET /api/admin/courses
     */
    public function getCourses(Request $request)
    {
        $courses = Course::with(['teachers.user', 'students', 'semester'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $courses
        ], 200);
    }

    /**
     * جلب دورة محددة
     * GET /api/admin/courses/{courseId}
     */
    public function getCourse(Request $request, $courseId)
    {
        $course = Course::with(['teachers.user', 'students.user', 'semester', 'lessons', 'schedules'])
            ->find($courseId);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'الدورة غير موجودة'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $course
        ], 200);
    }

    /**
     * إنشاء دورة جديدة
     * POST /api/admin/courses
     */
    public function createCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'required|string',
            'semester_id' => 'nullable|exists:semesters,semester_id',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:teachers,teacher_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $course = Course::create([
            'title' => $request->title,
            'description' => $request->description,
            'level' => $request->level,
            'semester_id' => $request->semester_id,
        ]);

        // ربط المدرسين بالدورة
        if ($request->has('teacher_ids')) {
            $course->teachers()->attach($request->teacher_ids);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الدورة بنجاح',
            'data' => $course
        ], 201);
    }

    /**
     * تحديث دورة
     * PUT /api/admin/courses/{courseId}
     */
    public function updateCourse(Request $request, $courseId)
    {
        $course = Course::find($courseId);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'الدورة غير موجودة'
            ], 404);
        }

        $course->update($request->only(['title', 'description', 'level', 'semester_id']));

        // تحديث المدرسين
        if ($request->has('teacher_ids')) {
            $course->teachers()->sync($request->teacher_ids);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الدورة بنجاح',
            'data' => $course
        ], 200);
    }

    /**
     * حذف دورة
     * DELETE /api/admin/courses/{courseId}
     */
    public function deleteCourse(Request $request, $courseId)
    {
        $course = Course::find($courseId);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'الدورة غير موجودة'
            ], 404);
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الدورة بنجاح'
        ], 200);
    }

    // ========== إدارة الفصول الدراسية ==========

    /**
     * جلب جميع الفصول الدراسية
     * GET /api/admin/semesters
     */
    public function getSemesters(Request $request)
    {
        $semesters = Semester::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $semesters
        ], 200);
    }

    /**
     * إنشاء فصل دراسي جديد
     * POST /api/admin/semesters
     */
    public function createSemester(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // إذا كان الفصل الجديد نشطاً، قم بتعطيل الفصول النشطة الأخرى
        if ($request->is_active) {
            Semester::where('is_active', true)->update(['is_active' => false]);
        }

        $semester = Semester::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الفصل الدراسي بنجاح',
            'data' => $semester
        ], 201);
    }

    /**
     * تحديث فصل دراسي
     * PUT /api/admin/semesters/{semesterId}
     */
    public function updateSemester(Request $request, $semesterId)
    {
        $semester = Semester::find($semesterId);

        if (!$semester) {
            return response()->json([
                'success' => false,
                'message' => 'الفصل الدراسي غير موجود'
            ], 404);
        }

        // إذا تم تفعيل هذا الفصل، قم بتعطيل الفصول الأخرى
        if ($request->is_active && !$semester->is_active) {
            Semester::where('is_active', true)->update(['is_active' => false]);
        }

        $semester->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الفصل الدراسي بنجاح',
            'data' => $semester
        ], 200);
    }

    /**
     * حذف فصل دراسي
     * DELETE /api/admin/semesters/{semesterId}
     */
    public function deleteSemester(Request $request, $semesterId)
    {
        $semester = Semester::find($semesterId);

        if (!$semester) {
            return response()->json([
                'success' => false,
                'message' => 'الفصل الدراسي غير موجود'
            ], 404);
        }

        $semester->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الفصل الدراسي بنجاح'
        ], 200);
    }

    // ========== إدارة الأقسام ==========

    /**
     * جلب جميع الأقسام
     * GET /api/admin/departments
     */
    public function getDepartments(Request $request)
    {
        $departments = Department::withCount('courses')->get();

        return response()->json([
            'success' => true,
            'data' => $departments
        ], 200);
    }

    /**
     * إنشاء قسم جديد
     * POST /api/admin/departments
     */
    public function createDepartment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $department = Department::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء القسم بنجاح',
            'data' => $department
        ], 201);
    }

    /**
     * تحديث قسم
     * PUT /api/admin/departments/{departmentId}
     */
    public function updateDepartment(Request $request, $departmentId)
    {
        $department = Department::find($departmentId);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'القسم غير موجود'
            ], 404);
        }

        $department->update($request->only(['name', 'description']));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث القسم بنجاح',
            'data' => $department
        ], 200);
    }

    /**
     * حذف قسم
     * DELETE /api/admin/departments/{departmentId}
     */
    public function deleteDepartment(Request $request, $departmentId)
    {
        $department = Department::find($departmentId);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'القسم غير موجود'
            ], 404);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف القسم بنجاح'
        ], 200);
    }

    // ========== التقارير ==========

    /**
     * تقرير الحضور
     * GET /api/admin/reports/attendance
     */
    public function attendanceReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'nullable|exists:courses,course_id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Attendance::with(['student.user', 'lesson.course']);

        if ($request->course_id) {
            $query->whereHas('lesson', function($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        if ($request->from_date) {
            $query->where('attendance_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->where('attendance_date', '<=', $request->to_date);
        }

        $attendances = $query->get();

        $statistics = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
            'data' => $attendances
        ], 200);
    }

    /**
     * تقرير العلامات
     * GET /api/admin/reports/grades
     */
    public function gradesReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'nullable|exists:courses,course_id',
            'exam_id' => 'nullable|exists:exams,exam_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Grade::with(['student.user', 'exam.course']);

        if ($request->exam_id) {
            $query->where('exam_id', $request->exam_id);
        } elseif ($request->course_id) {
            $query->whereHas('exam', function($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        $grades = $query->get();

        $statistics = [
            'total' => $grades->count(),
            'average' => round($grades->avg('score'), 1),
            'max' => $grades->max('score'),
            'min' => $grades->min('score'),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
            'data' => $grades
        ], 200);
    }

    /**
     * تقرير الطلاب
     * GET /api/admin/reports/students
     */
    public function studentsReport(Request $request)
    {
        $students = User::where('role', 'student')
            ->with(['student.courses', 'student.attendances', 'student.grades'])
            ->get()
            ->map(function($student) {
                $attendances = $student->student->attendances;
                $grades = $student->student->grades;

                return [
                    'id' => $student->user_id,
                    'name' => $student->full_name,
                    'student_code' => $student->student->student_code ?? null,
                    'total_courses' => $student->student->courses->count(),
                    'attendance_rate' => $attendances->count() > 0
                        ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 1)
                        : 0,
                    'average_grade' => round($grades->avg('score'), 1),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $students
        ], 200);
    }
}
