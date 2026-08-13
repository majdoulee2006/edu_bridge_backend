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

            \App\Models\UserActivity::log('معالجة طلب إجازة', "قام رئيس القسم بتحديث حالة طلب الإجازة رقم {$id} إلى: {$request->status}");

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

    /**
     * 1️⃣ جلب بيانات الفلترة لإنشاء استدعاء (الدورات، السنوات، والطلاب)
     */
    public function getAppointmentsMetadata(Request $request)
    {
        try {
            $user = auth()->user();
            $dept = $user ? $user->department : null;

            $head = DB::table('heads')->where('user_id', auth()->id())->first();

            // 1. الدورات/البرامج الخاصة بقسم رئيس القسم فقط
            $courses = collect();
            if ($head && !empty($head->department_id)) {
                $courses = DB::table('programs')
                    ->where('department_id', $head->department_id)
                    ->select('id as course_id', 'name as title')
                    ->get();
            }

            if ($courses->isEmpty() && !empty($dept)) {
                $courses = DB::table('programs')
                    ->join('departments', 'programs.department_id', '=', 'departments.department_id')
                    ->where('departments.name', 'LIKE', '%' . $dept . '%')
                    ->select('programs.id as course_id', 'programs.name as title')
                    ->get();
            }

            if ($courses->isEmpty()) {
                $courses = DB::table('programs')->select('id as course_id', 'name as title')->get();
            }

            // 2. قائمة السنوات الدراسية لنظام المعهد (سنتين فقط)
            $years = [
                'السنة الأولى',
                'السنة الثانية',
            ];

            // 3. جلب الطلاب مع بيانات القسم واسم البرنامج المرتبط واسم ولي الأمر
            $studentsQuery = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('parent_students', 'students.student_id', '=', 'parent_students.student_id')
                ->leftJoin('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
                ->leftJoin('users as parent_users', 'parents.user_id', '=', 'parent_users.user_id')
                ->select(
                    'students.student_id',
                    'users.full_name as student_name',
                    'students.program_id',
                    'programs.name as program_name',
                    'users.department',
                    'students.level as raw_level',
                    'students.student_code',
                    'parent_users.full_name as parent_name',
                    'parent_users.user_id as parent_user_id'
                );

            if ($head && !empty($head->department_id)) {
                $deptName = DB::table('departments')->where('department_id', $head->department_id)->value('name');
                if ($deptName) {
                    $studentsQuery->where(function($q) use ($deptName, $head) {
                        $q->where('users.department', 'LIKE', '%' . $deptName . '%')
                          ->orWhere('programs.department_id', $head->department_id)
                          ->orWhereNull('students.program_id');
                    });
                }
            } elseif (!empty($dept)) {
                $studentsQuery->where('users.department', 'LIKE', '%' . $dept . '%');
            }

            $studentsRaw = $studentsQuery->get();

            // خيار الاحتياط النهائي لجلب كافة الطلاب إن كانت الشروط مشددة جداً
            if ($studentsRaw->isEmpty()) {
                $studentsRaw = DB::table('students')
                    ->join('users', 'students.user_id', '=', 'users.user_id')
                    ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                    ->leftJoin('parent_students', 'students.student_id', '=', 'parent_students.student_id')
                    ->leftJoin('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
                    ->leftJoin('users as parent_users', 'parents.user_id', '=', 'parent_users.user_id')
                    ->select(
                        'students.student_id',
                        'users.full_name as student_name',
                        'students.program_id',
                        'programs.name as program_name',
                        'users.department',
                        'students.level as raw_level',
                        'students.student_code',
                        'parent_users.full_name as parent_name',
                        'parent_users.user_id as parent_user_id'
                    )
                    ->get();
            }

            // تجهيز قائمة الطلاب وتوحيد صيغة السنة والدورات
            $students = $studentsRaw->map(function ($st) {
                $lvl = trim((string)$st->raw_level);
                if (in_array($lvl, ['1', 'الأولى', 'السنة الأولى', 'السنة الاولى', 'first'])) {
                    $year = 'السنة الأولى';
                } elseif (in_array($lvl, ['2', 'الثانية', 'السنة الثانية', 'second'])) {
                    $year = 'السنة الثانية';
                } else {
                    $year = !empty($lvl) ? $lvl : 'السنة الأولى';
                }

                return [
                    'student_id'   => $st->student_id,
                    'student_name' => $st->student_name,
                    'program_id'   => $st->program_id,
                    'program_name' => !empty($st->program_name) ? $st->program_name : ($st->department ?? ''),
                    'department'   => $st->department ?? '',
                    'year'         => $year,
                    'parent_name'  => $st->parent_name ?? 'غير مسجل',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'courses'  => $courses,
                    'years'    => $years,
                    'students' => $students,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 2️⃣ جلب جميع الاستدعاءات الصادرة والواردة لرئيس القسم مع الفلترة
     */
    public function getSummons(Request $request)
    {
        try {
            $userId = auth()->id();
            $status = $request->query('status', 'all');

            $query = DB::table('parent_summons')
                ->join('students', 'parent_summons.student_id', '=', 'students.student_id')
                ->join('users as student_users', 'students.user_id', '=', 'student_users.user_id')
                ->leftJoin('users as parent_users', 'parent_summons.parent_user_id', '=', 'parent_users.user_id')
                ->leftJoin('users as sender_users', 'parent_summons.sender_user_id', '=', 'sender_users.user_id')
                ->leftJoin('departments', 'students.department_id', '=', 'departments.department_id')
                ->select(
                    'parent_summons.*',
                    'student_users.full_name as student_name',
                    'students.student_code',
                    'students.level as year',
                    'departments.name_ar as department_name',
                    'parent_users.full_name as parent_name',
                    'sender_users.full_name as teacher_name'
                );

            if ($status === 'pending') {
                $query->whereIn('parent_summons.status', ['pending_hod', 'pending_affairs']);
            } else if ($status === 'completed') {
                $query->whereIn('parent_summons.status', ['sent', 'approved', 'rejected', 'completed', 'cancelled']);
            }

            $summons = $query->orderBy('parent_summons.created_at', 'desc')->get();

            return response()->json(['success' => true, 'data' => $summons]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * تحويل مباشر لطلب استدعاء المعلم من رئيس القسم إلى موظفي الشؤون
     */
    public function forwardSummonToAffairs(Request $request, $id)
    {
        $summon = DB::table('parent_summons')->where('summon_id', $id)->first();
        if (!$summon) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        DB::table('parent_summons')->where('summon_id', $id)->update([
            'status'     => 'pending_affairs',
            'updated_at' => now(),
        ]);

        $studentName = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $summon->student_id)
            ->value('users.full_name') ?? 'طالب';

        $title = 'تحويل طلب استدعاء ولي أمر للشؤون';
        $msg   = 'قام رئيس القسم بتحويل طلب استدعاء ولي أمر الطالب: ' . $studentName . ' لاستكمال الإجراءات وتحديد الموعد.';

        // إشعار موظفي الشؤون (role_id = 6)
        $affairsUsers = User::where('role_id', 6)->get();
        foreach ($affairsUsers as $affUser) {
            DB::table('notifications')->insert([
                'user_id'    => $affUser->user_id,
                'sender_id'  => auth()->id(),
                'title'      => $title,
                'message'    => $msg,
                'type'       => 'summon_request',
                'category'   => 'administrative',
                'related_id' => $id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \App\Services\FcmService::sendToUser($affUser->user_id, $title, $msg, [
                'type' => 'summon_request', 'related_id' => (string) $id, 'screen' => 'affairs_appointments'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحويل الطلب المباشر للشؤون بنجاح لإكمال الإجراءات وإشعار ولي الأمر.'
        ]);
    }

    /**
     * 3️⃣ إنشاء وإرسال استدعاء جديد لولي أمر طالب
     */
    public function storeSummon(Request $request)
    {
        $request->validate([
            'student_id'   => 'required|exists:students,student_id',
            'reason_title' => 'required|string|max:255',
            'details'      => 'required|string',
            'summon_date'  => 'nullable|string',
        ]);

        try {
            $student = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('students.student_id', $request->student_id)
                ->first();

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'الطالب غير موجود'], 404);
            }

            // جلب ولي أمر الطالب
            $parentUserId = DB::table('parent_students')
                ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
                ->where('parent_students.student_id', $request->student_id)
                ->value('parents.user_id');

            if (!$parentUserId) {
                return response()->json([
                    'success' => false,
                    'message' => 'تعذر العثور على حساب ولي أمر مرتبط بهذا الطالب في النظام.'
                ], 422);
            }

            $id = DB::table('parent_summons')->insertGetId([
                'sender_user_id' => auth()->id(),
                'student_id'     => $request->student_id,
                'parent_user_id' => $parentUserId,
                'reason_title'   => $request->reason_title,
                'details'        => $request->details,
                'summon_date'    => $request->summon_date ? date('Y-m-d H:i:s', strtotime($request->summon_date)) : null,
                'status'         => 'sent',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $title = 'استدعاء رسمي من رئيس القسم';
            $message = "لديك استدعاء رسميا بخصوص الطالب ({$student->full_name}) - الموضوع: " . $request->reason_title;

            // إرسال إشعار لولي الأمر بجدول Notifications
            DB::table('notifications')->insert([
                'user_id'    => $parentUserId,
                'sender_id'  => auth()->id(),
                'title'      => $title,
                'message'    => $message,
                'type'       => 'summon',
                'category'   => 'administrative',
                'related_id' => $id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // إرسال FCM Push Notification
            \App\Services\FcmService::sendToUser($parentUserId, $title, $message, [
                'type'       => 'summon',
                'related_id' => (string)$id,
            ]);

            \App\Models\UserActivity::log('إرسال استدعاء لولي أمر', "قام رئيس القسم بإرسال استدعاء لولي أمر الطالب: {$student->full_name} - السبب: {$request->reason_title}");

            return response()->json(['success' => true, 'message' => 'تم إرسال الاستدعاء بنجاح', 'id' => $id], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 4️⃣ جلب المواعيد الواردة الموجهة لرئيس القسم مع الدعم للفلترة
     */
    public function getMeetingRequests(Request $request)
    {
        try {
            $status = $request->query('status', 'all');
            $query = DB::table('parent_meeting_requests')
                ->join('users as parent_users', 'parent_meeting_requests.parent_user_id', '=', 'parent_users.user_id')
                ->leftJoin('students', 'parent_meeting_requests.student_id', '=', 'students.student_id')
                ->leftJoin('users as student_users', 'students.user_id', '=', 'student_users.user_id')
                ->select(
                    'parent_meeting_requests.*',
                    'parent_users.full_name as parent_name',
                    'parent_users.phone as parent_phone',
                    'student_users.full_name as student_name'
                );

            $query->where(function($q) {
                $q->where('parent_meeting_requests.target_role', 'head')
                  ->orWhere('parent_meeting_requests.target_role', 'hod');
            });

            if ($status === 'pending') {
                $query->where('parent_meeting_requests.status', 'pending');
            } else if ($status === 'completed') {
                $query->whereIn('parent_meeting_requests.status', ['approved', 'rejected', 'completed', 'cancelled']);
            }

            $meetings = $query->orderBy('parent_meeting_requests.created_at', 'desc')->get();

            return response()->json(['success' => true, 'data' => $meetings]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 5️⃣ الرد على طلب الموعد (قبول / رفض مع ملاحظات وتحديد موعد)
     */
    public function respondToMeetingRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'status'         => 'required|string|in:approved,rejected',
            'admin_response' => 'nullable|string',
            'scheduled_at'   => 'nullable|string',
        ]);

        $meeting = DB::table('parent_meeting_requests')->where('id', $id)->first();
        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        $scheduledAt = $validated['scheduled_at'] ? date('Y-m-d H:i:s', strtotime($validated['scheduled_at'])) : null;

        DB::table('parent_meeting_requests')->where('id', $id)->update([
            'status'         => $validated['status'],
            'admin_response' => $validated['admin_response'] ?? null,
            'scheduled_at'   => $scheduledAt,
            'updated_at'     => now(),
        ]);

        // 🔔 إرسال إشعار فوري لولي الأمر بجدول Notifications و FCM
        $parentUserId = $meeting->parent_user_id;
        if ($parentUserId) {
            $statusLabel = $validated['status'] === 'approved' ? 'قبول' : 'رفض';
            $title = "تم الرد على طلب الموعد من رئيس القسم";
            $notes = $validated['admin_response'] ? " - الملاحظات: " . $validated['admin_response'] : "";
            $timeInfo = $scheduledAt ? " - الموعد المحدد: " . $scheduledAt : "";
            $message = "تم {$statusLabel} طلب الموعد الخاص بك{$notes}{$timeInfo}";

            DB::table('notifications')->insert([
                'user_id'    => $parentUserId,
                'sender_id'  => auth()->id(),
                'title'      => $title,
                'message'    => $message,
                'type'       => 'meeting_request',
                'category'   => 'administrative',
                'related_id' => $id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser($parentUserId, $title, $message, [
                'type'       => 'meeting_request',
                'related_id' => (string)$id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الرد وإشعاره لولي الأمر بنجاح'
        ]);
    }
}
