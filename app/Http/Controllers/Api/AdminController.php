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
use App\Models\Role;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $studentRoleId = Role::where('name', 'student')->value('role_id');
        $teacherRoleId = Role::where('name', 'teacher')->value('role_id');
        $parentRoleId = Role::where('name', 'parent')->value('role_id');

        $announcements = \App\Models\Announcement::with('user')->latest()->limit(10)->get()->map(function($a) {
            return [
                'announcement_id' => $a->announcement_id,
                'title' => $a->title,
                'content' => $a->content,
                'type' => $a->type ?? 'general',
                'category' => $a->category ?? 'إعلان',
                'author_name' => $a->user ? $a->user->full_name : 'الإدارة',
                'created_at' => $a->created_at ? $a->created_at->diffForHumans() : '',
                'image_url' => $a->image ? asset('storage/' . $a->image) : null,
            ];
        });

        $statistics = [
            'total_students' => User::where('role_id', $studentRoleId)->count(),
            'total_teachers' => User::where('role_id', $teacherRoleId)->count(),
            'total_parents' => User::where('role_id', $parentRoleId)->count(),
            'total_courses' => Course::count(),
            'total_departments' => Department::count(),
            'active_semester' => Semester::where('is_active', true)->first(),
            'announcements' => $announcements,
            'recent_users' => User::latest()->limit(10)->get()->map(function($user) {
                return [
                    'id' => $user->user_id,
                    'name' => $user->full_name,
                    'role' => $user->role ?? 'unknown',
                    'created_at' => $user->created_at ? $user->created_at->diffForHumans() : null,
                ];
            }),
        ];

        return response()->json(['success' => true, 'data' => $statistics], 200);
    }

    public function getUsers(Request $request)
    {
        $query = User::with(['student', 'teacher', 'parent']);

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        } elseif ($request->filled('role')) {
            $roleStr = $request->role;
            $roleObj = \DB::table('roles')->where('name', $roleStr)->orWhere('role_name', $roleStr)->first();
            if ($roleObj) {
                $query->where('role_id', $roleObj->role_id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('all')) {
            $users = $query->latest()->get();
            return response()->json(['success' => true, 'data' => $users], 200);
        }

        $users = $query->latest()->paginate(100);

        return response()->json(['success' => true, 'data' => $users], 200);
    }

    public function getUser(Request $request, $userId)
    {
        $user = User::with(['student', 'teacher', 'parent'])->find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
        }

        return response()->json(['success' => true, 'data' => $user], 200);
    }

    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,teacher,student,parent,head,affairs',
            'academic_year' => 'nullable|string',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'gender' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'department' => 'nullable|string',
            'branch' => 'nullable|string',
            'telegram_chat_id' => 'nullable|string',
            'course_ids' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $role = Role::where('name', $request->role)->first();
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'الدور غير موجود'], 400);
        }

        $userData = [
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $role->role_id,
            'academic_year' => $request->academic_year,
            'status' => 'active',
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'department' => $request->department,
            'branch' => $request->branch,
            'telegram_chat_id' => $request->telegram_chat_id,
        ];

        $user = User::create($userData);

        if ($request->role == 'student') {
            $student = Student::create([
                'user_id' => $user->user_id,
                'student_code' => $request->username,
                'level' => $request->academic_year,
            ]);
            Student::autoAssignAdvisor($student->student_id);
        } elseif ($request->role == 'teacher') {
            $teacher = Teacher::create([
                'user_id' => $user->user_id,
                'specialization' => $request->input('specialization', $request->department ?? 'عام'),
            ]);

            if ($request->has('course_ids')) {
                foreach ($request->course_ids as $courseId) {
                    \App\Models\CourseTeacher::create([
                        'course_id' => $courseId,
                        'teacher_id' => $teacher->teacher_id,
                        'role' => 'main',
                    ]);
                }
            }
        } elseif ($request->role == 'parent') {
            Parents::create(['user_id' => $user->user_id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المستخدم بنجاح',
            'data' => $user->load('role')
        ], 201);
    }

    public function updateUser(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId . ',user_id',
            'phone' => 'sometimes|string|max:20',
            'academic_year' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user->update($request->only(['full_name', 'email', 'phone', 'academic_year', 'status']));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث المستخدم بنجاح', 'data' => $user], 200);
    }

    public function deleteUser(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
        }

        if ($user->username == 'admin') {
            return response()->json(['success' => false, 'message' => 'لا يمكن حذف حساب المدير الرئيسي'], 403);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف المستخدم بنجاح'], 200);
    }

    // ========== Courses ==========

    public function getCourses(Request $request)
    {
        $courses = Course::with(['teachers.user', 'students', 'semester'])
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $courses], 200);
    }

    public function getCourse(Request $request, $courseId)
    {
        $course = Course::with(['teachers.user', 'students.user', 'semester', 'lessons', 'schedules'])
            ->find($courseId);

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'الدورة غير موجودة'], 404);
        }

        return response()->json(['success' => true, 'data' => $course], 200);
    }

    public function getSemestersSubjects(Request $request)
    {
        $selectedDept    = $request->query('department_id');
        $selectedProgram = $request->query('program_id');
        $selectedYear    = $request->query('year');
        $selectedSemester= $request->query('semester_id');

        $coursesQuery = \DB::table('courses')
            ->leftJoin('course_teachers', 'courses.course_id', '=', 'course_teachers.course_id')
            ->leftJoin('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('courses.*', 'users.full_name as teacher_name');

        if ($selectedSemester) {
            $coursesQuery->where('courses.semester_id', $selectedSemester);
        }

        if ($selectedYear) {
            $coursesQuery->where('courses.year', $selectedYear);
        }

        if ($selectedProgram) {
            $courseIds = \DB::table('course_program')
                ->where('program_id', $selectedProgram)
                ->pluck('course_id');
            $coursesQuery->whereIn('courses.course_id', $courseIds);
        } elseif ($selectedDept) {
            $programIds = \DB::table('programs')
                ->where('department_id', $selectedDept)
                ->pluck('id');
            $courseIds = \DB::table('course_program')
                ->whereIn('program_id', $programIds)
                ->pluck('course_id');
            $coursesQuery->whereIn('courses.course_id', $courseIds);
        }

        $courses = $coursesQuery->get();

        foreach ($courses as $course) {
            $lessons = \DB::table('lessons')
                ->where('course_id', $course->course_id)
                ->select('lesson_id', 'title', 'description', 'file_path', 'file_name', 'file_type', 'content_url', 'created_at')
                ->get();
            $course->lessons_list = $lessons;

            $semInfo = \DB::table('semesters')
                ->where('semester_id', $course->semester_id)
                ->first();
            $course->semester_name = $semInfo ? $semInfo->name : 'غير محدد';
        }

        $programs = \DB::table('programs')->get();
        $departments = \DB::table('departments')->get();
        $semesters = \DB::table('semesters')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'courses' => $courses,
                'programs' => $programs,
                'departments' => $departments,
                'semesters' => $semesters,
            ]
        ], 200);
    }

    public function createCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|string',
            'year' => 'nullable|string',
            'credit_hours' => 'nullable',
            'semester_id' => 'nullable',
            'program_id' => 'nullable',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:teachers,teacher_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $courseData = [
            'title' => $request->title,
            'description' => $request->description,
            'level' => $request->input('level', 'المستوى الأول'),
        ];
        if ($request->filled('year')) $courseData['year'] = $request->year;
        if ($request->filled('semester_id')) $courseData['semester_id'] = $request->semester_id;
        if ($request->filled('hours')) $courseData['hours'] = $request->hours;
        if ($request->filled('credit_hours')) $courseData['hours'] = $request->credit_hours;

        $course = Course::create($courseData);

        if ($request->filled('program_id')) {
            \DB::table('course_program')->insert([
                'course_id' => $course->course_id,
                'program_id' => $request->program_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($request->has('teacher_ids')) {
            foreach ($request->teacher_ids as $teacherId) {
                \App\Models\CourseTeacher::create([
                    'course_id' => $course->course_id,
                    'teacher_id' => $teacherId,
                    'role' => 'main',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المادة بنجاح',
            'data' => $course
        ], 201);
    }

    public function getAssignHodData(Request $request)
    {
        $departments = \DB::table('departments')->get();
        foreach ($departments as $dept) {
            $head = \DB::table('heads')
                ->join('users', 'heads.user_id', '=', 'users.user_id')
                ->where('heads.department_id', $dept->department_id)
                ->select('users.user_id', 'users.full_name', 'users.email', 'users.phone')
                ->first();
            $dept->current_hod_name = $head ? $head->full_name : 'غير مخصص حالياً';
            $dept->current_hod_user_id = $head ? $head->user_id : null;
        }

        $teachers = \DB::table('users')
            ->whereIn('role_id', [2, 5])
            ->where('status', 'active')
            ->select('user_id', 'full_name', 'department')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'departments' => $departments,
                'teachers' => $teachers,
            ]
        ]);
    }

    public function assignHodExisting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'required|exists:departments,department_id',
            'user_id' => 'required|exists:users,user_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $dept = \DB::table('departments')->where('department_id', $request->department_id)->first();
        $user = \DB::table('users')->where('user_id', $request->user_id)->first();

        $oldHead = \DB::table('heads')->where('department_id', $request->department_id)->first();
        if ($oldHead) {
            \DB::table('users')->where('user_id', $oldHead->user_id)->update([
                'role_id' => 2,
                'department' => null,
                'updated_at' => now(),
            ]);
            \DB::table('heads')->where('department_id', $request->department_id)->delete();
        }

        \DB::table('users')->where('user_id', $request->user_id)->update([
            'role_id' => 5,
            'department' => $dept->name,
            'updated_at' => now(),
        ]);

        \DB::table('heads')->insert([
            'user_id' => $request->user_id,
            'department_id' => $request->department_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم تعيين رئيس القسم بنجاح']);
    }

    public function assignHodNew(Request $request)
    {
        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }
        $request->merge(['full_name' => $fullName]);

        $validator = Validator::make($request->all(), [
            'department_id' => 'required|exists:departments,department_id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email|max:255',
            'username' => 'required|string|unique:users,username|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $dept = \DB::table('departments')->where('department_id', $request->department_id)->first();

        $oldHead = \DB::table('heads')->where('department_id', $request->department_id)->first();
        if ($oldHead) {
            \DB::table('users')->where('user_id', $oldHead->user_id)->update([
                'role_id' => 2,
                'department' => null,
                'updated_at' => now(),
            ]);
            \DB::table('heads')->where('department_id', $request->department_id)->delete();
        }

        $userId = \DB::table('users')->insertGetId([
            'role_id' => 5,
            'full_name' => $fullName,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => \Hash::make($request->password),
            'department' => $dept->name,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('heads')->insert([
            'user_id' => $userId,
            'department_id' => $request->department_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم إنشاء حساب رئيس القسم بنجاح']);
    }

    public function updateCourse(Request $request, $courseId)
    {
        $course = Course::find($courseId);

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'الدورة غير موجودة'], 404);
        }

        $course->update($request->only(['title', 'description', 'level', 'semester_id']));

        if ($request->has('teacher_ids')) {
            \App\Models\CourseTeacher::where('course_id', $courseId)->delete();
            foreach ($request->teacher_ids as $teacherId) {
                \App\Models\CourseTeacher::create([
                    'course_id' => $course->course_id,
                    'teacher_id' => $teacherId,
                    'role' => 'main',
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث الدورة بنجاح', 'data' => $course], 200);
    }

    public function deleteCourse(Request $request, $courseId)
    {
        $course = Course::find($courseId);

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'الدورة غير موجودة'], 404);
        }

        $course->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الدورة بنجاح'], 200);
    }

    // ========== Semesters ==========

    public function getSemesters(Request $request)
    {
        $semesters = Semester::latest()->get();
        return response()->json(['success' => true, 'data' => $semesters], 200);
    }

    public function createSemester(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->is_active) {
            Semester::where('is_active', true)->update(['is_active' => false]);
        }

        $semester = Semester::create($request->all());

        return response()->json(['success' => true, 'message' => 'تم إنشاء الفصل الدراسي بنجاح', 'data' => $semester], 201);
    }

    public function updateSemester(Request $request, $semesterId)
    {
        $semester = Semester::find($semesterId);

        if (!$semester) {
            return response()->json(['success' => false, 'message' => 'الفصل الدراسي غير موجود'], 404);
        }

        if ($request->is_active && !$semester->is_active) {
            Semester::where('is_active', true)->update(['is_active' => false]);
        }

        $semester->update($request->all());

        return response()->json(['success' => true, 'message' => 'تم تحديث الفصل الدراسي بنجاح', 'data' => $semester], 200);
    }

    public function deleteSemester(Request $request, $semesterId)
    {
        $semester = Semester::find($semesterId);

        if (!$semester) {
            return response()->json(['success' => false, 'message' => 'الفصل الدراسي غير موجود'], 404);
        }

        $semester->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الفصل الدراسي بنجاح'], 200);
    }

    // ========== Departments ==========

    public function getDepartments(Request $request)
    {
        $departments = Department::withCount('courses')->get();
        return response()->json(['success' => true, 'data' => $departments], 200);
    }

    public function createDepartment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $department = Department::create($request->all());

        return response()->json(['success' => true, 'message' => 'تم إنشاء القسم بنجاح', 'data' => $department], 201);
    }

    public function updateDepartment(Request $request, $departmentId)
    {
        $department = Department::find($departmentId);

        if (!$department) {
            return response()->json(['success' => false, 'message' => 'القسم غير موجود'], 404);
        }

        $department->update($request->only(['name', 'description']));

        return response()->json(['success' => true, 'message' => 'تم تحديث القسم بنجاح', 'data' => $department], 200);
    }

    public function deleteDepartment(Request $request, $departmentId)
    {
        $department = Department::find($departmentId);

        if (!$department) {
            return response()->json(['success' => false, 'message' => 'القسم غير موجود'], 404);
        }

        $department->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف القسم بنجاح'], 200);
    }

    // ========== Reports ==========

    public function attendanceReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'nullable|exists:courses,course_id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
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

        return response()->json(['success' => true, 'statistics' => $statistics, 'data' => $attendances], 200);
    }

    public function gradesReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'nullable|exists:courses,course_id',
            'exam_id' => 'nullable|exists:exams,exam_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
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

        return response()->json(['success' => true, 'statistics' => $statistics, 'data' => $grades], 200);
    }

    public function studentsReport(Request $request)
    {
        $studentRoleId = Role::where('name', 'student')->value('role_id');

        $students = User::where('role_id', $studentRoleId)
            ->with(['student.courses', 'student.attendances', 'student.grades'])
            ->get()
            ->map(function($user) {
                $student = $user->student;
                if (!$student) return null;

                $attendances = $student->attendances;
                $grades = $student->grades;

                return [
                    'id' => $user->user_id,
                    'name' => $user->full_name,
                    'student_code' => $student->student_code ?? null,
                    'total_courses' => $student->courses->count(),
                    'attendance_rate' => $attendances->count() > 0
                        ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 1)
                        : 0,
                    'average_grade' => round($grades->avg('score'), 1),
                ];
            })->filter();

        return response()->json(['success' => true, 'data' => $students->values()], 200);
    }

    public function getReportsLog(Request $request)
    {
        $savedReports = \DB::table('admin_generated_reports')->orderByDesc('created_at')->get();
        return response()->json(['success' => true, 'data' => $savedReports]);
    }

    public function viewReportById(Request $request, $id)
    {
        $report = \DB::table('admin_generated_reports')->where('id', $id)->first();
        if (!$report) {
            return response()->json(['success' => false, 'message' => 'التقرير غير موجود'], 404);
        }

        $data = $this->fetchReportData($report);
        return response()->json(['success' => true, 'report' => $report, 'data' => $data]);
    }

    public function exportReportById(Request $request, $id)
    {
        $report = \DB::table('admin_generated_reports')->where('id', $id)->first();
        if (!$report) {
            return response()->json(['success' => false, 'message' => 'التقرير غير موجود'], 404);
        }

        $format = $request->query('format', 'excel'); // 'excel' or 'pdf'
        $data = $this->fetchReportData($report);
        
        $currentYear = date('Y');
        $dateLabel = date('Y-m-d H:i');
        
        if ($report->report_type === 'attendance') {
            $reportTitle = "تقرير حضور {$currentYear}";
            $filename    = "{$reportTitle}_{$dateLabel}." . ($format == 'pdf' ? 'pdf' : 'xls');
            $rowsHtml = '';
            $i = 1;
            foreach ($data as $row) {
                $rate = $row->total_sessions > 0 ? round(($row->present_count / $row->total_sessions) * 100) : 0;
                $rowsHtml .= "<tr>
                    <td style='text-align:center;'>{$i}</td>
                    <td style='font-weight:bold;color:#0f172a;'>{$row->student_name}</td>
                    <td style='color:#2563eb;font-weight:bold;'>{$row->department_name}</td>
                    <td style='color:#475569;'>{$row->program_name}</td>
                    <td style='color:#0f172a;'>{$row->course_title}</td>
                    <td style='text-align:center;'>{$row->semester_name}</td>
                    <td style='color:#15803d;font-weight:bold;text-align:center;'>{$row->present_count}</td>
                    <td style='color:#b91c1c;font-weight:bold;text-align:center;'>{$row->absent_count}</td>
                    <td style='text-align:center;font-weight:bold;'>{$row->total_sessions}</td>
                    <td style='font-weight:bold;text-align:center;'>{$rate}%</td>
                </tr>";
                $i++;
            }
            $headersHtml = "<tr>
                <th style='width:40px;text-align:center;'>#</th>
                <th>اسم الطالب</th>
                <th>القسم</th>
                <th>الدورة / البرنامج</th>
                <th>المادة الدراسية</th>
                <th style='text-align:center;'>الفصل</th>
                <th style='text-align:center;'>حاضر</th>
                <th style='text-align:center;'>غائب</th>
                <th style='text-align:center;'>الإجمالي</th>
                <th style='text-align:center;'>نسبة الحضور</th>
            </tr>";
        } else {
            $reportTitle = "تقرير أداء ودرجات {$currentYear}";
            $filename    = "{$reportTitle}_{$dateLabel}." . ($format == 'pdf' ? 'pdf' : 'xls');
            $rowsHtml = '';
            $i = 1;
            foreach ($data as $row) {
                $rowsHtml .= "<tr>
                    <td style='text-align:center;'>{$i}</td>
                    <td style='font-weight:bold;color:#0f172a;'>{$row->student_name}</td>
                    <td style='color:#2563eb;font-weight:bold;'>{$row->department_name}</td>
                    <td style='color:#475569;'>{$row->program_name}</td>
                    <td style='color:#0f172a;'>{$row->course_title}</td>
                    <td style='text-align:center;'>{$row->semester_name}</td>
                    <td style='font-weight:bold;text-align:center;'>{$row->grade}</td>
                </tr>";
                $i++;
            }
            $headersHtml = "<tr>
                <th style='width:40px;text-align:center;'>#</th>
                <th>اسم الطالب</th>
                <th>القسم</th>
                <th>الدورة / البرنامج</th>
                <th>المادة الدراسية</th>
                <th style='text-align:center;'>الفصل</th>
                <th style='text-align:center;'>الدرجة</th>
            </tr>";
        }

        $htmlContent = "
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; direction: rtl; }
                .report-header { text-align: center; margin-bottom: 30px; }
                .report-header h2 { color: #0f172a; margin-bottom: 5px; }
                .report-header p { color: #475569; margin: 2px 0; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
                th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: right; }
                th { background-color: #f8fafc; color: #334155; font-weight: bold; }
                tr:nth-child(even) { background-color: #f8fafc; }
            </style>
        </head>
        <body>
            <div class='report-header'>
                <h2>{$reportTitle}</h2>
                <p><strong>نوع التقرير:</strong> " . ($report->report_type === 'attendance' ? 'حضور وغياب' : 'أداء طلاب') . "</p>
                <p><strong>القسم:</strong> {$report->department_name}</p>
                <p><strong>الدورة:</strong> {$report->program_name}</p>
                <p><strong>تاريخ التوليد:</strong> {$dateLabel}</p>
            </div>
            <table>
                <thead>{$headersHtml}</thead>
                <tbody>{$rowsHtml}</tbody>
            </table>
        </body>
        </html>";

        if ($format == 'pdf') {
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'autoScriptToLang' => true, 'autoLangToFont' => true]);
            $mpdf->SetDirectionality('rtl');
            $mpdf->WriteHTML($htmlContent);
            return response($mpdf->Output('', 'S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } else {
            return response($htmlContent, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        }
    }

    private function fetchReportData($report)
    {
        if ($report->report_type === 'attendance') {
            $query = \DB::table('attendance')
                ->join('students', 'attendance.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
                ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name as student_name',
                    \DB::raw("COALESCE(departments.name, 'عام') as department_name"),
                    \DB::raw("COALESCE(programs.name, 'عام') as program_name"),
                    'courses.title as course_title',
                    \DB::raw("COALESCE(semesters.name, 'عام') as semester_name"),
                    \DB::raw("COUNT(*) as total_sessions"),
                    \DB::raw("SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    \DB::raw("SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END) as absent_count")
                )
                ->groupBy('users.full_name', 'departments.name', 'programs.name', 'courses.title', 'semesters.name');

            if ($report->semester_id) $query->where('courses.semester_id', $report->semester_id);
            if ($report->from_date)   $query->where('attendance.attendance_date', '>=', $report->from_date);
            if ($report->to_date)     $query->where('attendance.attendance_date', '<=', $report->to_date);
            if ($report->program_id) {
                $courseIds = \DB::table('course_program')->where('program_id', $report->program_id)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            } elseif ($report->department_id) {
                $programIds = \DB::table('programs')->where('department_id', $report->department_id)->pluck('id');
                $courseIds  = \DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            }

            return $query->orderBy('departments.name')->orderBy('users.full_name')->get();

        } else {
            $query = \DB::table('grades')
                ->join('students', 'grades.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->join('courses', 'exams.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name as student_name',
                    \DB::raw("COALESCE(departments.name, 'عام') as department_name"),
                    \DB::raw("COALESCE(programs.name, 'عام') as program_name"),
                    'courses.title as course_title',
                    'grades.score as grade',
                    \DB::raw("COALESCE(semesters.name, 'عام') as semester_name")
                );

            if ($report->semester_id) $query->where('courses.semester_id', $report->semester_id);
            if ($report->program_id) {
                $courseIds = \DB::table('course_program')->where('program_id', $report->program_id)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            } elseif ($report->department_id) {
                $programIds = \DB::table('programs')->where('department_id', $report->department_id)->pluck('id');
                $courseIds = \DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            }

            return $query->limit(500)->get();
        }
    }

    public function createAnnouncement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'           => 'required|string|max:255',
            'content'         => 'required|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url'        => 'nullable|url|max:500',
            'target_audience' => 'nullable|in:all,students,teachers,department',
            'department_id'   => 'nullable|exists:departments,department_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $announcement = \App\Models\Announcement::create([
            'user_id'         => auth()->id(),
            'title'           => $request->title,
            'content'         => $request->content,
            'image'           => $imagePath,
            'link_url'        => $request->input('link_url'),
            'target_audience' => $request->input('target_audience', 'all'),
            'type'            => 'general',
        ]);

        // Notifications & FCM
        $target = $request->input('target_audience', 'all');
        $roleIds = match($target) { 'students'=>[3], 'teachers'=>[2], 'department'=>[2,3], default=>[2,3] };
        $query = User::whereIn('role_id', $roleIds)->where('status','active');

        if ($target === 'department' && $request->filled('department_id')) {
            $deptName = Department::where('department_id', $request->department_id)->value('name');
            if ($deptName) $query->where('department', $deptName);
        }
        $userIds = $query->pluck('user_id');
        $now = now();
        $rows = $userIds->map(fn($uid) => [
            'user_id' => $uid,
            'sender_id' => auth()->id(),
            'title' => 'إعلان جديد من الإدارة',
            'message' => $request->title,
            'type' => 'announcement',
            'category' => 'administrative',
            'related_id' => $announcement->announcement_id,
            'is_read' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (!empty($rows)) {
            \DB::table('notifications')->insert($rows);
            foreach ($userIds as $uid) {
                try {
                    \App\Services\FcmService::sendToUser($uid, 'إعلان جديد من الإدارة', $request->title, ['type'=>'announcement']);
                } catch (\Exception $e) {
                    \Log::error('FCM Error: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم نشر الإعلان بنجاح!',
            'data' => $announcement
        ], 201);
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = \App\Models\Announcement::find($id);
        if (!$announcement) {
            return response()->json(['success' => false, 'message' => 'الإعلان غير موجود'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'           => 'required|string|max:255',
            'content'         => 'required|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url'        => 'nullable|url|max:500',
            'target_audience' => 'nullable|in:all,students,teachers,department',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $updateData = [
            'title'   => $request->title,
            'content' => $request->content,
        ];

        if ($request->has('link_url')) {
            $updateData['link_url'] = $request->link_url;
        }

        if ($request->has('target_audience')) {
            $updateData['target_audience'] = $request->target_audience;
        }

        if ($request->hasFile('image')) {
            $updateData['image'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعلان بنجاح!',
            'data'    => $announcement,
        ], 200);
    }

    public function deleteAnnouncement(Request $request, $id)
    {
        $announcement = \App\Models\Announcement::find($id);
        if (!$announcement) {
            return response()->json(['success' => false, 'message' => 'الإعلان غير موجود'], 404);
        }

        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإعلان بنجاح!',
        ], 200);
    }

    public function sendBroadcast(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_type' => 'required|in:all,departments,individuals',
            'subject'        => 'required|string|max:255',
            'message'        => 'required|string',
            'target_departments' => 'nullable|array',
            'target_users' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $recipientType = $request->recipient_type;
        $recipientsQuery = \DB::table('users')->where('user_id', '!=', auth()->id());

        if ($recipientType === 'departments') {
            if (!$request->filled('target_departments')) {
                return response()->json(['success' => false, 'message' => 'يرجى تحديد الأقسام المستهدفة.'], 400);
            }
            $deptNames = \DB::table('departments')
                ->whereIn('department_id', $request->target_departments)
                ->pluck('name')
                ->toArray();
            
            $recipientsQuery->whereIn('department', $deptNames);
        } elseif ($recipientType === 'individuals') {
            if (!$request->filled('target_users')) {
                return response()->json(['success' => false, 'message' => 'يرجى تحديد الأفراد المستهدفين.'], 400);
            }
            $recipientsQuery->whereIn('user_id', $request->target_users);
        }

        $recipientIds = $recipientsQuery->pluck('user_id')->toArray();

        if (empty($recipientIds)) {
            return response()->json(['success' => false, 'message' => 'لم يتم العثور على مستخدمين لإرسال التعميم إليهم.'], 400);
        }

        $fullMessage = "📌 [ " . $request->subject . " ]\n\n" . $request->message;
        $notifTitle = 'تعميم إداري: ' . $request->subject;
        $notifMsg   = 'تلقيت تعميماً إدارياً جديداً من الإدارة العامة.';

        $now = now();
        foreach ($recipientIds as $receiverId) {
            \DB::table('messages')->insert([
                'sender_id'   => auth()->id(),
                'receiver_id' => $receiverId,
                'message'     => $fullMessage,
                'is_read'     => false,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            \DB::table('notifications')->insert([
                'user_id'    => $receiverId,
                'sender_id'  => auth()->id(),
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'message',
                'category'   => 'administrative',
                'is_read'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            try {
                \App\Services\FcmService::sendToUser($receiverId, $notifTitle, $notifMsg, ['type' => 'message']);
            } catch (\Exception $e) {
                // Ignore FCM errors
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال التعميم الإداري بنجاح إلى (' . count($recipientIds) . ') مستخدم!'
        ], 200);
    }

    public function getPendingAccounts()
    {
        $pendingUsers = User::where('status', 'inactive')
            ->with(['role'])
            ->orderByDesc('created_at')
            ->get();
        return response()->json(['success' => true, 'data' => $pendingUsers], 200);
    }

    public function approveAccount($id)
    {
        \DB::table('users')
            ->where('user_id', $id)
            ->update(['status' => 'active', 'updated_at' => now()]);

        // ---- Link children to parents on approval ----
        $user = \DB::table('users')->where('user_id', $id)->first();
        if ($user && $user->role_id == 4 && !empty($user->children_ids)) {
            $childrenIds = is_string($user->children_ids) ? json_decode($user->children_ids, true) : $user->children_ids;
            if (is_array($childrenIds)) {
                $parent = \DB::table('parents')->where('user_id', $id)->first();
                if ($parent) {
                    foreach ($childrenIds as $universityId) {
                        $student = \DB::table('students')
                            ->where('student_code', $universityId)
                            ->select('student_id')
                            ->first();
                        if ($student) {
                            \DB::table('parent_students')->insertOrIgnore([
                                'parent_id'    => $parent->parent_id,
                                'student_id'   => $student->student_id,
                                'relationship' => 'والد / ولي أمر',
                                'created_at'   => now(),
                                'updated_at'   => now(),
                            ]);
                        }
                    }
                }
            }
        }

        // Add welcome notification
        \DB::table('notifications')->insert([
            'user_id'    => $id,
            'title'      => 'تم تفعيل الحساب',
            'message'    => 'تهانينا! قامت الإدارة بتفعيل حسابك بنجاح. يمكنك الآن استخدام كافة الميزات.',
            'type'       => 'system',
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        try {
            \App\Services\FcmService::sendToUser(
                $id,
                'تم تفعيل الحساب',
                'تهانينا! قامت الإدارة بتفعيل حسابك بنجاح. يمكنك الآن استخدام كافة الميزات.',
                ['type' => 'system']
            );
        } catch (\Exception $e) {
            // Ignore FCM error
        }

        return response()->json(['success' => true, 'message' => 'تم قبول وتفعيل حساب المستخدم بنجاح!'], 200);
    }

    public function rejectAccount($id)
    {
        $usr = \DB::table('users')->where('user_id', $id)->first();
        if ($usr) {
            if ($usr->role_id == 3) {
                \DB::table('students')->where('user_id', $id)->delete();
            } elseif ($usr->role_id == 2) {
                \DB::table('teachers')->where('user_id', $id)->delete();
            } elseif ($usr->role_id == 5) {
                \DB::table('heads')->where('user_id', $id)->delete();
            } elseif ($usr->role_id == 4) {
                $parent = \DB::table('parents')->where('user_id', $id)->first();
                if ($parent) {
                    \DB::table('parent_students')->where('parent_id', $parent->parent_id)->delete();
                    \DB::table('parents')->where('parent_id', $parent->parent_id)->delete();
                }
            }
            \DB::table('users')->where('user_id', $id)->delete();
        }

        return response()->json(['success' => true, 'message' => 'تم رفض وحذف طلب الحساب بنجاح.'], 200);
    }
}
