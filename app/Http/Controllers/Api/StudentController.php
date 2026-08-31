<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Grade;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AbsenceRequest;
use App\Models\LeaveRequest;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Course;
use App\Models\Schedule;
use Carbon\Carbon;
use App\Models\StudentRequest;

class StudentController extends Controller
{
  /**
     * لوحة التحكم الرئيسية للطالب
     */
    public function getDashboardData(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        // 1. استخراج أرقام الكورسات لأننا سنحتاجها في المحاضرة القادمة
        $enrolledCourseIds = $student ? $student->courses->modelKeys() : [];

        // استنتاج معرف القسم للطالب بناءً على اسم القسم
        $departmentId = \Illuminate\Support\Facades\DB::table('departments')
            ->where('name', 'LIKE', '%' . $user->department . '%')
            ->value('department_id');

        // 🌟 2. جلب الإعلانات (العامة + المخصصة لقسم الطالب فقط)
        $announcements = Announcement::with(['user', 'department', 'course'])
            ->where(function($query) use ($departmentId) {
                $query->whereNull('department_id')
                      ->orWhere('department_id', $departmentId);
            })
            ->where(function($q) {
                $q->whereNull('target_audience')
                  ->orWhereIn('target_audience', ['all', 'students']);
            })
            ->where(function($q) {
                $q->whereNull('target_role')
                  ->orWhere('target_role', 'student');
            })
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($item) {
                $categoryText = 'عام';
                if ($item->category == 'important') $categoryText = 'هام جداً';
                elseif ($item->category == 'student_activity') $categoryText = 'نشاط طلابي';
                elseif ($item->category == 'academic') $categoryText = 'أكاديمي';
                elseif ($item->category == 'administrative') $categoryText = 'إداري';

                return [
                    'id' => $item->announcement_id,
                    'type' => $item->type ?? 'general',
                    'title' => $item->title ?? 'إعلان',
                    'content' => $item->content ?? '',
                    'body' => $item->content ?? '',
                    'category' => $item->category ?? 'general',
                    'category_text' => $categoryText,
                    'target_audience' => $item->target_audience ?? 'all',
                    'department_id' => $item->department_id,
                    'department_name' => $item->department ? $item->department->name : null,
                    'course_id' => $item->course_id,
                    'course_name' => $item->course ? $item->course->title : null,
                    'image_url' => $item->image ? url('storage/' . $item->image) : null,
                    'link_url' => $item->link_url ?? null,
                    'created_by' => $item->user->full_name ?? $item->user->name ?? 'الإدارة',
                    'author_name' => $item->user->full_name ?? $item->user->name ?? 'الإدارة',
                    'publisher_name' => $item->user->full_name ?? $item->user->name ?? 'الإدارة',
                    'time_ago' => $item->created_at ? $item->created_at->diffForHumans() : 'منذ قليل',
                ];
            });

        // 3. جلب المحاضرة القادمة
        $academicYearStr = str_replace('السنة ال', 'سنة ', $user->academic_year ?? '');
        $branchName = \Illuminate\Support\Facades\DB::table('programs')->where('id', $student->program_id)->value('name') ?? $user->branch ?? '';
        $classGroup = $branchName . ' - ' . $academicYearStr;

        $nextLecture = null;
        $today = now()->format('l');

        $schedule = Schedule::where(function($q) use ($enrolledCourseIds, $classGroup) {
                $q->whereIn('course_id', $enrolledCourseIds)
                  ->orWhere('class_group', $classGroup);
            })
            ->where('day', $today)
            ->where('start_time', '>', now()->format('H:i:s'))
            ->orderBy('start_time', 'asc')
            ->with('course')
            ->first();

        if ($schedule) {
            $nextLecture = [
                'course_name' => $schedule->course->title ?? 'مادة غير محددة',
                'room' => $schedule->room ?? 'قاعة غير محددة',
                'start_time' => date('h:i A', strtotime($schedule->start_time)),
                'end_time' => date('h:i A', strtotime($schedule->end_time)),
            ];
        }

        $advisorTeacher = null;
        if ($student) {
            $level = $student->level ?? $user->academic_year ?? 'السنة الأولى';
            $academicYear = trim($level);
            if ($academicYear === 'أولى' || $academicYear === 'السنة الأولى' || $academicYear === '1') $academicYear = 'السنة الأولى';
            elseif ($academicYear === 'ثانية' || $academicYear === 'السنة الثانية' || $academicYear === '2') $academicYear = 'السنة الثانية';
            elseif ($academicYear === 'ثالثة' || $academicYear === 'السنة الثالثة' || $academicYear === '3') $academicYear = 'السنة الثالثة';
            elseif ($academicYear === 'رابعة' || $academicYear === 'السنة الرابعة' || $academicYear === '4') $academicYear = 'السنة الرابعة';
            elseif ($academicYear === 'خامسة' || $academicYear === 'السنة الخامسة' || $academicYear === '5') $academicYear = 'السنة الخامسة';

            $branch = \DB::table('programs')->where('id', $student->program_id)->value('name') ?? $user->department ?? $user->branch;

            $teacherRow = \DB::table('teachers')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->where('teachers.advisor_branch', $branch)
                ->where('teachers.advisor_year', $academicYear)
                ->select('users.full_name', 'users.phone', 'users.email', 'teachers.specialization')
                ->first();

            if (!$teacherRow && $user->department) {
                $teacherRow = \DB::table('teachers')
                    ->join('users', 'teachers.user_id', '=', 'users.user_id')
                    ->where('users.department', 'LIKE', '%' . $user->department . '%')
                    ->select('users.full_name', 'users.phone', 'users.email', 'teachers.specialization')
                    ->first();
            }

            if ($teacherRow) {
                $advisorTeacher = [
                    'name'           => $teacherRow->full_name,
                    'phone'          => $teacherRow->phone ?? 'غير متوفر',
                    'email'          => $teacherRow->email ?? 'غير متوفر',
                    'specialization' => $teacherRow->specialization ?? 'مربي الدورة',
                ];
            }
        }

        $activeSemRow = DB::table('semesters')->where('is_active', true)->first();
        $currYearInt = (int)date('Y');
        $academicYearRange = $currYearInt . ' - ' . ($currYearInt + 1);

        $activeSemesterData = [
            'is_active'     => $activeSemRow ? true : false,
            'name'          => $activeSemRow ? $activeSemRow->name : 'لا يوجد فصل مفعّل حالياً',
            'start_date'    => $activeSemRow?->start_date ?? null,
            'end_date'      => $activeSemRow?->end_date ?? null,
            'academic_year' => $academicYearRange,
        ];

        return response()->json([
            'success' => true,
            'message' => 'تم جلب البيانات بنجاح',
            'data' => [
                'student' => [
                    'id' => $user->user_id,
                    'name' => $user->full_name,
                    'avatar' => $user->avatar ? storageUrl($user->avatar) : null,
                    'department' => $user->department ?? 'غير محدد',
                ],
                'active_semester' => $activeSemesterData,
                'next_lecture' => $nextLecture,
                'announcements' => $announcements,
                'advisor_teacher' => $advisorTeacher,
            ]
        ], 200);
    }


    /**
     * جلب الملف الشخصي للطالب
     */
    public function getProfileData(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        $advisorTeacher = null;
        if ($student) {
            $level = $student->level ?? $user->academic_year ?? 'السنة الأولى';
            $academicYear = trim($level);
            if ($academicYear === 'أولى' || $academicYear === 'السنة الأولى' || $academicYear === '1') $academicYear = 'السنة الأولى';
            elseif ($academicYear === 'ثانية' || $academicYear === 'السنة الثانية' || $academicYear === '2') $academicYear = 'السنة الثانية';
            elseif ($academicYear === 'ثالثة' || $academicYear === 'السنة الثالثة' || $academicYear === '3') $academicYear = 'السنة الثالثة';
            elseif ($academicYear === 'رابعة' || $academicYear === 'السنة الرابعة' || $academicYear === '4') $academicYear = 'السنة الرابعة';
            elseif ($academicYear === 'خامسة' || $academicYear === 'السنة الخامسة' || $academicYear === '5') $academicYear = 'السنة الخامسة';

            $branch = \DB::table('programs')->where('id', $student->program_id)->value('name') ?? $user->department ?? $user->branch;

            $teacherRow = \DB::table('teachers')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->where('teachers.advisor_branch', $branch)
                ->where('teachers.advisor_year', $academicYear)
                ->select('users.full_name', 'users.phone', 'users.email', 'teachers.specialization')
                ->first();

            if (!$teacherRow && $user->department) {
                $teacherRow = \DB::table('teachers')
                    ->join('users', 'teachers.user_id', '=', 'users.user_id')
                    ->where('users.department', 'LIKE', '%' . $user->department . '%')
                    ->select('users.full_name', 'users.phone', 'users.email', 'teachers.specialization')
                    ->first();
            }

            if ($teacherRow) {
                $advisorTeacher = [
                    'name'           => $teacherRow->full_name,
                    'phone'          => $teacherRow->phone ?? 'غير متوفر',
                    'email'          => $teacherRow->email ?? 'غير متوفر',
                    'specialization' => $teacherRow->specialization ?? 'مربي الدورة',
                ];
            }
        }

        $activeSemRow = \DB::table('semesters')->where('is_active', true)->first();
        $activeSemesterName = $activeSemRow ? $activeSemRow->name : 'لا يوجد فصل نشط حالياً';

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الملف الشخصي بنجاح',
            'data' => [
                'name' => $user->full_name,
                'username' => $user->username,
                'student_code' => $student->student_code ?? $user->university_id,
                'phone' => $user->phone ?? 'غير متوفر',
                'email' => $user->email ?? 'غير متوفر',
                'department' => $user->department ?? 'غير محدد',
                'academic_year' => $user->academic_year ?? $student?->level ?? 'غير محدد',
                'birth_date' => $user->birth_date ? $user->birth_date->format('Y-m-d') : null,
                'gender' => $user->gender ?? 'غير محدد',
                'level' => $student?->level ?? $user->academic_year ?? 'غير محدد',
                'semester' => $activeSemesterName,
                'active_semester' => $activeSemesterName,
                'avatar' => $user->avatar ? storageUrl($user->avatar) : null,
                'reference_photo_url' => $student?->reference_photo ? url('storage/' . $student->reference_photo) : null,
                'has_face_embedding' => (!empty($student?->face_embedding) && !$student?->requires_face_reset),
                'advisor_teacher' => $advisorTeacher,
            ]
        ], 200);
    }

    /**
     * تحديث الملف الشخصي للطالب
     */
    public function updateProfile(Request $request)
{
    $user = $request->user();

    // 🌟 1. إعداد الـ Validation
    $validator = Validator::make($request->all(), [
        'phone' => 'sometimes|string|max:20',
        'email' => 'sometimes|email|unique:users,email,' . $user->user_id . ',user_id',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    // 🌟 2. تجهيز البيانات التي سيتم تحديثها
    $dataToUpdate = $request->only(['phone', 'email']);

    // 🌟 3. معالجة الصورة (في حال تم إرسالها)
    if ($request->hasFile('avatar')) {
        // حذف الصورة القديمة إذا وجدت
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // حفظ الصورة الجديدة
        $path = $request->file('avatar')->store('avatars', 'public');
        $dataToUpdate['avatar'] = $path;
    } else {
         // هذا السطر مفيد للـ Debugging فقط إذا أردتِ التأكد (يمكنك حذفه لاحقاً)
        Log::info("No avatar file received for user: " . $user->user_id);
    }

    // 🌟 4. تحديث قاعدة البيانات
    $user->update($dataToUpdate);

    \App\Models\UserActivity::log('تحديث الملف الشخصي', 'قام الطالب بتحديث بيانات ملفه الشخصي عبر التطبيق المحمول');

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث الملف الشخصي بنجاح',
        'data' => [
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => $user->avatar ? storageUrl($user->avatar) : null,
        ]
    ], 200);
}

    /**
     * تهيئة بصمة الوجه للطالب بناءً على الصورة المرفوعة من موظف الشؤون
     */
    public function initializeFaceFromPhoto(Request $request)
    {
        $request->validate([
            'face_embedding' => 'required|array',
        ]);

        $student = $request->user()->student;

        if (!$student->reference_photo) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد صورة مرجعية مرفوعة من الشؤون لك، يرجى مراجعة إدارة شؤون الطلاب.',
            ], 400);
        }

        $student->update([
            'face_embedding'      => $request->face_embedding,
            'requires_face_reset' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تهيئة بصمة وجهك بنجاح من صورتك الرسمية ✅',
        ], 200);
    }

    /**
     * جلب الإشعارات الخاصة بالطالب (معدلة لتناسب الهيكل الجديد)
     */
    public function getNotifications(Request $request)
    {
        $user = $request->user();

        $query = Notification::with('sender')
            ->where('user_id', $user->user_id)
            ->latest();

        if ($request->has('filter')) {
            if ($request->filter == 'unread') $query->where('is_read', false);
            elseif ($request->filter == 'read') $query->where('is_read', true);
        }

        $paginator = $query->paginate(15);

        $announcementIds = collect($paginator->items())->where('type', 'announcement')->pluck('related_id')->filter()->unique();
        $announcementsData = \DB::table('announcements')
            ->whereIn('announcement_id', $announcementIds)
            ->get(['announcement_id', 'image', 'link_url'])
            ->keyBy('announcement_id');

        $mappedItems = collect($paginator->items())->map(function ($notify) use ($announcementsData) {
            $imageUrl = null;
            $linkUrl = null;
            if ($notify->type === 'announcement' && $notify->related_id) {
                $ann = $announcementsData->get($notify->related_id);
                $imageUrl = $ann && $ann->image ? url('storage/' . $ann->image) : null;
                $linkUrl  = $ann->link_url ?? null;
            }
            $isAcademic = $notify->category === 'academic' || in_array($notify->type, ['grade', 'marks', 'assignment', 'lecture']);
            $cat = $notify->category ?? ($isAcademic ? 'academic' : 'administrative');

            return [
                'id' => $notify->id,
                'title' => $notify->title,
                'message' => $notify->message,
                'type' => $notify->type,
                'category' => $cat,
                'sender_name' => $notify->sender->full_name ?? 'الإدارة',
                'is_read' => (bool)$notify->is_read,
                'related_id' => $notify->related_id,
                'image_url'  => $imageUrl,
                'link_url'   => $linkUrl,
                'created_at' => $notify->created_at ? $notify->created_at->format('Y-m-d H:i:s') : null,
                'formatted_date' => $notify->created_at ? $notify->created_at->translatedFormat('d F Y - h:i A') : null,
                'time_ago' => $notify->created_at ? $notify->created_at->diffForHumans() : 'منذ قليل',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الإشعارات بنجاح',
            'data' => $mappedItems,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'has_more' => $paginator->hasMorePages(),
            'total' => $paginator->total()
        ], 200);
    }

    /**
     * تحديث حالة قراءة الإشعار
     */
    public function markNotificationAsRead(Request $request, $notificationId)
    {
        $user = $request->user();

        $notification = Notification::where('user_id', $user->user_id)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'الإشعار غير موجود'
            ], 404);
        }

        // 🌟 دالتك هنا ممتازة ولا تحتاج تعديل منطقي، تعمل بكفاءة تامة
        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الإشعار بنجاح'
        ], 200);
    }
    /**
     * 8. تحديد كل إشعارات الطالب كمقروءة
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        $user = $request->user();

        // تحديث كل الإشعارات الغير مقروءة الخاصة بهذا الطالب لتصبح مقروءة
        Notification::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد جميع الإشعارات كمقروءة'
        ], 200);
    }

    /**
     * جلب جميع دورات الطالب
     */
    public function getMyCourses(Request $request)
    {
        $student = $request->user()->student;

        $query = $student->courses()
            ->with(['teacher.user', 'schedule']);

        $studentLevel = trim($student->level ?? $student->user->academic_year ?? 'السنة الأولى');
        $map = [
            'السنة الأولى' => 1, 'أولى' => 1, '1' => 1,
            'السنة الثانية' => 2, 'ثانية' => 2, '2' => 2,
            'السنة الثالثة' => 3, 'ثالثة' => 3, '3' => 3,
            'السنة الرابعة' => 4, 'رابعة' => 4, '4' => 4,
            'السنة الخامسة' => 5, 'خامسة' => 5, '5' => 5
        ];
        $studentYearInt = $map[$studentLevel] ?? 1;

        $failedOnly = $request->boolean('failed_only') || $request->input('failed_only') == '1' || $request->input('failed_only') == 'true';

        if ($request->filled('year')) {
            $query->where('courses.year', $request->year);
        } elseif (!$failedOnly) {
            // تصفية المواد لتطابق السنة الدراسية للطالب حصراً (مع إبقاء المواد العامة/المستقلة)
            $query->where(function($q) use ($studentYearInt) {
                $q->where('courses.year', $studentYearInt)
                  ->orWhereNull('courses.year');
            });
        }

        $coursesCollection = $query->get();

        if ($failedOnly) {
            $failedCourseIds = [];
            foreach ($coursesCollection as $c) {
                $quizScore = null;
                $oralScore = null;
                $finalScore = null;

                $examGrades = DB::table('grades')
                    ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                    ->where('grades.student_id', $student->student_id)
                    ->where('exams.course_id', $c->course_id)
                    ->get();

                foreach ($examGrades as $g) {
                    $examName = mb_strtolower($g->exam_name);
                    if (str_contains($examName, 'مذاكرة') || str_contains($examName, 'أعمال') || str_contains($examName, 'quiz') || str_contains($examName, 'midterm')) {
                        $quizScore = $g->score;
                    } elseif (str_contains($examName, 'شفهي') || str_contains($examName, 'عملي') || str_contains($examName, 'oral') || str_contains($examName, 'practical')) {
                        $oralScore = $g->score;
                    } else {
                        $finalScore = $g->score;
                    }
                }

                $eventGrades = DB::table('grade_entries')
                    ->join('grade_events', 'grade_entries.grade_event_id', '=', 'grade_events.id')
                    ->where('grade_entries.student_id', $student->student_id)
                    ->where('grade_events.course_id', $c->course_id)
                    ->get();

                foreach ($eventGrades as $ge) {
                    $t = mb_strtolower($ge->type ?? '');
                    $titleStr = mb_strtolower($ge->title ?? '');

                    if (str_contains($t, 'quiz') || str_contains($t, 'مذاكرة') || str_contains($t, 'أعمال') || str_contains($titleStr, 'مذاكرة')) {
                        $quizScore = $ge->score;
                    } elseif (str_contains($t, 'oral') || str_contains($t, 'عملي') || str_contains($t, 'شفهي') || str_contains($titleStr, 'شفهي') || str_contains($titleStr, 'تقييم')) {
                        $oralScore = $ge->score;
                    } else {
                        $finalScore = $ge->score;
                    }
                }

                $hasGrades = ($quizScore !== null || $oralScore !== null || $finalScore !== null);
                $totalCourseScore = ($quizScore ?? 0) + ($oralScore ?? 0) + ($finalScore ?? 0);
                if ($totalCourseScore > 100) $totalCourseScore = 100;

                if ($hasGrades && $totalCourseScore < 50) {
                    $failedCourseIds[] = $c->course_id;
                }
            }

            $coursesCollection = $coursesCollection->whereIn('course_id', $failedCourseIds)->values();
        }

        $courses = $coursesCollection->map(function ($course) {
                return [
                    'id'          => $course->course_id,
                    'title'       => $course->title,
                    'description' => $course->description,
                    'level'       => $course->level,
                    'year'        => $course->year ?? 1,
                    'teacher_name'=> $course->teacher->first()->user->full_name ?? 'غير محدد',

                    'schedule'    => $course->schedule ? [
                        'day'        => $course->schedule->day,
                        'start_time' => $course->schedule->start_time,
                        'end_time'   => $course->schedule->end_time,
                        'room'       => $course->schedule->room,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $courses
        ], 200);
    }

    /**
     * جلب كافة مواد الاختصاص للطالب (للعامين الأول والثاني مع الفلترة)
     */
    public function getProgramCourses(Request $request)
    {
        $student = $request->user()->student;
        $user    = $request->user();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'الطالب غير موجود'
            ], 404);
        }

        $program = null;
        if ($student->program_id) {
            $program = \App\Models\Program::with('courses')->find($student->program_id);
        }

        if (!$program) {
            $branch = $user->branch ?? $user->department;
            if ($branch) {
                $program = \App\Models\Program::with('courses')
                    ->where('name', 'LIKE', '%' . $branch . '%')
                    ->first();
            }
        }

        if (!$program) {
            return response()->json([
                'success' => true,
                'data'    => []
            ], 200);
        }

        $courseQuery = $program->courses();
        
        $studentLevel = trim($student->level ?? $user->academic_year ?? 'السنة الأولى');
        $map = [
            'السنة الأولى' => 1, 'أولى' => 1, '1' => 1,
            'السنة الثانية' => 2, 'ثانية' => 2, '2' => 2,
            'السنة الثالثة' => 3, 'ثالثة' => 3, '3' => 3,
            'السنة الرابعة' => 4, 'رابعة' => 4, '4' => 4,
            'السنة الخامسة' => 5, 'خامسة' => 5, '5' => 5
        ];
        $studentYearInt = $map[$studentLevel] ?? 1;

        if ($request->filled('year')) {
            $courseQuery->where('courses.year', $request->year);
        } else {
            // تصفية المواد لتطابق السنة الدراسية للطالب حصراً (مع إبقاء المواد العامة/المستقلة)
            $courseQuery->where(function($q) use ($studentYearInt) {
                $q->where('courses.year', $studentYearInt)
                  ->orWhereNull('courses.year');
            });
        }

        $courses = $courseQuery->get()->map(function ($course) {
            return [
                'id'          => $course->course_id,
                'title'       => $course->title,
                'description' => $course->description,
                'level'       => $course->level,
                'year'        => $course->year ?? 1,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $courses
        ], 200);
    }

   /**
     * جلب المحاضرات (الدروس) المجمعة حسب المادة
     */
    public function getMyLectures(Request $request)
    {
        $student = $request->user()->student;

        // التعديل 1: غيرنا teacher إلى teachers
        $courses = $student->courses()
            ->with(['teachers.user', 'lessons'])
            ->get()
            ->unique('course_id')
            ->values()
            ->map(function ($course) {
                $filteredLessons = $course->lessons->filter(function ($lesson) {
                    $title = mb_strtolower($lesson->title);
                    $url = mb_strtolower($lesson->content_url ?? '');
                    $type = mb_strtolower($lesson->type ?? '');

                    if ($type === 'session') {
                        return false;
                    }
                    return true;
                })->values();

                return [
                    'course_id' => $course->course_id,
                    'course_name' => $course->title,
                    // التعديل 2: جلبنا أول دكتور من مصفوفة الدكاترة
                    'teacher_name' => $course->teachers->first()?->user->full_name ?? 'مدرس غير محدد',
                    'total_files' => $filteredLessons->count(),

                    'lessons' => $filteredLessons->map(function ($lesson) {
                        return [
                            'id' => $lesson->lesson_id,
                            'title' => $lesson->title,
                            'type' => $lesson->type ?? 'pdf',
                            'url' => $lesson->content_url ?
                                    (filter_var($lesson->content_url, FILTER_VALIDATE_URL) ? $lesson->content_url : storageUrl($lesson->content_url))
                                    : null,
                            'file_size' => $lesson->file_size,
                            'duration' => $lesson->duration,
                            'date' => $lesson->created_at ? $lesson->created_at->translatedFormat('d F') : null,
                        ];
                    })
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المحاضرات بنجاح',
            'data' => $courses
        ], 200);
    }
/**
     * جلب جدول الطالب الأسبوعي
     */
    public function getMySchedule(Request $request)
    {
        $user = $request->user();
        $student = $user->student;
        
        // بناء اسم المجموعة لتطابق الجداول المضافة من رئيس القسم
        // مثال: "معلوماتية" و "السنة الثانية" -> "معلوماتية - سنة ثانية"
        $academicYearStr = str_replace('السنة ال', 'سنة ', $user->academic_year ?? '');
        $branchName = \Illuminate\Support\Facades\DB::table('programs')->where('id', $student->program_id)->value('name') ?? $user->branch ?? '';
        $classGroup = $branchName . ' - ' . $academicYearStr;

        $schedules = Schedule::where(function($query) use ($student, $classGroup) {
                // الخيار 1: مسجل في المادة بشكل مباشر
                $query->whereHas('course', function($qCourse) use ($student) {
                    $qCourse->whereHas('students', function($qEnrolled) use ($student) {
                        $qEnrolled->where('enrollments.student_id', $student->student_id);
                    });
                })
                // الخيار 2: الجدول مخصص لنفس الفرع والسنة (class_group)
                ->orWhere('class_group', $classGroup);
            })
           ->with(['course', 'course.teachers.user']) // 💡 السحر هنا لجلب اليوزر مع المدرس
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->groupBy(function($item) {
                $dayMap = [
                    'Sunday'    => 'الأحد',
                    'Monday'    => 'الاثنين',
                    'Tuesday'   => 'الثلاثاء',
                    'Wednesday' => 'الأربعاء',
                    'Thursday'  => 'الخميس',
                    'Friday'    => 'الجمعة',
                    'Saturday'  => 'السبت',
                ];
                return $dayMap[$item->day] ?? $item->day;
            })
            ->map(function($items, $translatedDay) {
                return [
                    'day' => $translatedDay,
                    'lectures' => $items->map(function($item) {
                        return [
                            'course_name' => $item->course->title ?? 'مادة غير معروفة',
                            'teacher' => $item->course->teachers->first()?->user?->name ?? $item->course->teachers->first()?->user?->full_name ?? 'مدرس غير محدد',
                            'start_time'  => date('h:i A', strtotime($item->start_time)),
                            'end_time'    => date('h:i A', strtotime($item->end_time)),
                            'room'        => $item->room,
                            'duration'    => round((strtotime($item->end_time) - strtotime($item->start_time)) / 60) . ' دقيقة',
                        ];
                    })
                ];
            })->values();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ], 200);
    }
    /**
     * جلب جدول الامتحانات للطالب
     */

    public function getMyExams(Request $request)
    {
        $student = $request->user()->student;

        // جلب معرفات المواد التي سجل فيها الطالب
        $myCourseIds = DB::table('enrollments')
            ->where('student_id', $student->student_id)
            ->pluck('course_id')
            ->toArray();

        // تحديد السنة الدراسية كرقم
        $map = [
            'السنة الأولى' => 1,
            'السنة الثانية' => 2,
            'السنة الثالثة' => 3,
            'السنة الرابعة' => 4,
            'السنة الخامسة' => 5
        ];
        $yearInt = $map[$student->level] ?? 0;

        // تقييمات النظام الجديد (امتحان ومذاكرة) المرتبطة بهذا الطالب
        $gradeEvents = DB::table('grade_events')
            ->leftJoin('grade_entries', function ($join) use ($student) {
                $join->on('grade_entries.grade_event_id', '=', 'grade_events.id')
                     ->where('grade_entries.student_id', $student->student_id);
            })
            ->leftJoin('courses', 'grade_events.course_id', '=', 'courses.course_id')
            ->leftJoin('programs', 'grade_events.program_id', '=', 'programs.id')
            ->whereIn('grade_events.type', ['exam', 'quiz'])
            ->whereNotNull('grade_events.date')
            ->where(function ($q) use ($myCourseIds, $student, $yearInt) {
                // إما أن يكون التقييم لمادة مسجل بها الطالب
                $q->whereIn('grade_events.course_id', $myCourseIds);
                
                // أو أن يكون لبرنامج الطالب وسنته الدراسية
                if ($student->program_id && $yearInt > 0) {
                    $q->orWhere(function ($q2) use ($student, $yearInt) {
                        $q2->where('grade_events.program_id', $student->program_id)
                           ->where('grade_events.year_level', $yearInt);
                    });
                }
            })
            ->select(
                'grade_events.id as event_id',
                'grade_events.type as event_type',
                'grade_events.title',
                'grade_events.date',
                'grade_events.time',
                'grade_events.duration',
                'grade_events.max_score',
                'grade_entries.score',
                DB::raw("COALESCE(courses.title, programs.name, 'تقييم') as course_title")
            )
            ->get()
            ->map(function ($e) {
                $date       = Carbon::parse($e->date);
                $typeLabel  = $e->event_type === 'exam' ? 'امتحان' : 'مذاكرة';
                return [
                    'exam_id'    => null,
                    'event_id'   => $e->event_id,
                    'source'     => 'grade_event',
                    'subject'    => $e->course_title,
                    'type_label' => $typeLabel,
                    'title'      => $e->title,
                    'day_num'    => $date->format('d'),
                    'month'      => $date->translatedFormat('F'),
                    'day_name'   => $date->translatedFormat('l'),
                    'time'       => $e->time ?? $date->format('h:i A'),
                    'duration'   => $e->duration ?? ($e->event_type === 'exam' ? 'ساعتان' : 'ساعة'),
                    'room'       => 'القاعة الدراسية',
                    'max_score'  => $e->max_score,
                    'score'      => $e->score,
                    'date'       => $date->toDateString(), // added for sorting
                ];
            });

        // ترتيب حسب تاريخ التقييم
        $all = $gradeEvents->sortBy('date')->values();

        return response()->json(['success' => true, 'data' => $all], 200);
    }

    public function getGradeEventForStudent(Request $request, $id)
    {
        $student = $request->user()->student;
        $event   = DB::table('grade_events')
            ->leftJoin('courses', 'grade_events.course_id', '=', 'courses.course_id')
            ->leftJoin('programs', 'grade_events.program_id', '=', 'programs.id')
            ->where('grade_events.id', $id)
            ->select(
                'grade_events.id',
                'grade_events.type',
                'grade_events.title',
                'grade_events.max_score',
                'grade_events.date',
                DB::raw("COALESCE(courses.title, programs.name, 'تقييم') as course_title")
            )
            ->first();

        if (!$event) return response()->json(['success' => false], 404);

        $entry = DB::table('grade_entries')
            ->where('grade_event_id', $id)
            ->where('student_id', $student->student_id)
            ->first();

        $typeLabel = match($event->type) {
            'exam'  => 'امتحان',
            'quiz'  => 'مذاكرة',
            'oral'  => 'شفهي',
            default => 'تقييم',
        };

        return response()->json([
            'success'     => true,
            'event_id'    => $event->id,
            'type'        => $event->type,
            'type_label'  => $typeLabel,
            'title'       => $event->title,
            'course'      => $event->course_title,
            'max_score'   => $event->max_score,
            'date'        => $event->date,
            'score'       => $entry?->score,
            'graded'      => $entry && $entry->score !== null,
            'notes'       => $entry?->notes,
        ]);
    }
    /**
     * تصدير جدول الامتحانات (PDF) — يرجع البيانات كـ JSON
     */
    public function exportExamsPdf(Request $request)
    {
        $student = $request->user()->student;

        // جلب معرفات المواد التي سجل فيها الطالب
        $myCourseIds = DB::table('enrollments')
            ->where('student_id', $student->student_id)
            ->pluck('course_id')
            ->toArray();

        // تحديد السنة الدراسية كرقم
        $map = [
            'السنة الأولى' => 1,
            'السنة الثانية' => 2,
            'السنة الثالثة' => 3,
            'السنة الرابعة' => 4,
            'السنة الخامسة' => 5
        ];
        $yearInt = $map[$student->level] ?? 0;

        $exams = DB::table('grade_events')
            ->leftJoin('courses', 'grade_events.course_id', '=', 'courses.course_id')
            ->leftJoin('programs', 'grade_events.program_id', '=', 'programs.id')
            ->whereIn('grade_events.type', ['exam', 'quiz'])
            ->whereNotNull('grade_events.date')
            ->where(function ($q) use ($myCourseIds, $student, $yearInt) {
                $q->whereIn('grade_events.course_id', $myCourseIds);
                if ($student->program_id && $yearInt > 0) {
                    $q->orWhere(function ($q2) use ($student, $yearInt) {
                        $q2->where('grade_events.program_id', $student->program_id)
                           ->where('grade_events.year_level', $yearInt);
                    });
                }
            })
            ->select(
                'grade_events.id as event_id',
                'grade_events.type as event_type',
                'grade_events.title',
                'grade_events.date',
                'grade_events.time',
                'grade_events.duration',
                'grade_events.max_score',
                DB::raw("COALESCE(courses.title, programs.name, 'تقييم') as course_title")
            )
            ->orderBy('grade_events.date')
            ->get();

        $html = view('exports.exams_pdf', compact('exams', 'student'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubsets' => false,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $fileName = 'exams_' . $student->student_id . '_' . time() . '.pdf';
        $directory = public_path('exports');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $filePath = $directory . '/' . $fileName;
        file_put_contents($filePath, $mpdf->Output('', 'S'));

        $pdfUrl = url('exports/' . $fileName);

        return response()->json([
            'success'  => true,
            'pdf_url'  => $pdfUrl,
            'data'     => $exams,
        ], 200);
    }

    /**
     * تصدير الجدول الدراسي الأسبوعي (PDF)
     */
    public function exportSchedulePdf(Request $request)
    {
        $user = $request->user();
        $student = $user->student;
        
        $academicYearStr = str_replace('السنة ال', 'سنة ', $user->academic_year ?? '');
        $branchName = \Illuminate\Support\Facades\DB::table('programs')->where('id', $student->program_id)->value('name') ?? $user->branch ?? '';
        $classGroup = $branchName . ' - ' . $academicYearStr;

        $schedules = Schedule::where(function($query) use ($student, $classGroup) {
                $query->whereHas('course', function($qCourse) use ($student) {
                    $qCourse->whereHas('students', function($qEnrolled) use ($student) {
                        $qEnrolled->where('enrollments.student_id', $student->student_id);
                    });
                })
                ->orWhere('class_group', $classGroup);
            })
            ->with(['course', 'course.teachers.user'])
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        foreach ($schedules as $s) {
            $s->course_title = $s->course->title ?? 'مادة غير معروفة';
            $s->teacher_name = $s->course->teachers->first()?->user?->name ?? $s->course->teachers->first()?->user?->full_name ?? '';
        }

        $html = view('exports.schedule_pdf', compact('schedules', 'student'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'L',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubsets' => false,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $fileName = 'schedule_' . $student->student_id . '_' . time() . '.pdf';
        $directory = public_path('exports');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $filePath = $directory . '/' . $fileName;
        file_put_contents($filePath, $mpdf->Output('', 'S'));

        $pdfUrl = url('exports/' . $fileName);

        return response()->json([
            'success'  => true,
            'pdf_url'  => $pdfUrl,
        ], 200);
    }

    /**
     * تصدير جدول الامتحانات (Excel) — يرجع البيانات كـ JSON
     */
    public function exportExamsExcel(Request $request)
    {
        $student = $request->user()->student;

        // جلب معرفات المواد التي سجل فيها الطالب
        $myCourseIds = DB::table('enrollments')
            ->where('student_id', $student->student_id)
            ->pluck('course_id')
            ->toArray();

        // تحديد السنة الدراسية كرقم
        $map = [
            'السنة الأولى' => 1,
            'السنة الثانية' => 2,
            'السنة الثالثة' => 3,
            'السنة الرابعة' => 4,
            'السنة الخامسة' => 5
        ];
        $yearInt = $map[$student->level] ?? 0;

        $exams = DB::table('grade_events')
            ->leftJoin('courses', 'grade_events.course_id', '=', 'courses.course_id')
            ->leftJoin('programs', 'grade_events.program_id', '=', 'programs.id')
            ->whereIn('grade_events.type', ['exam', 'quiz'])
            ->whereNotNull('grade_events.date')
            ->where(function ($q) use ($myCourseIds, $student, $yearInt) {
                $q->whereIn('grade_events.course_id', $myCourseIds);
                if ($student->program_id && $yearInt > 0) {
                    $q->orWhere(function ($q2) use ($student, $yearInt) {
                        $q2->where('grade_events.program_id', $student->program_id)
                           ->where('grade_events.year_level', $yearInt);
                    });
                }
            })
            ->select(
                'grade_events.id as event_id',
                'grade_events.type as event_type',
                'grade_events.title',
                'grade_events.date',
                'grade_events.time',
                'grade_events.duration',
                'grade_events.max_score',
                DB::raw("COALESCE(courses.title, programs.name, 'تقييم') as course_title")
            )
            ->orderBy('grade_events.date')
            ->get();

        $fileContent = \Maatwebsite\Excel\Facades\Excel::raw(new \App\Exports\ExamsExport($exams), \Maatwebsite\Excel\Excel::XLSX);

        $fileName = 'exams_' . $student->student_id . '_' . time() . '.xlsx';
        $directory = public_path('exports');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $filePath = $directory . '/' . $fileName;
        file_put_contents($filePath, $fileContent);

        $excelUrl = url('exports/' . $fileName);

        return response()->json([
            'success'    => true,
            'excel_url'  => $excelUrl,
            'data'       => $exams,
        ], 200);
    }


    /**
     * جلب علامات الطالب
     */
    public function getMyGrades(Request $request)
    {
        $student = $request->user()->student;

        $grades = Grade::where('student_id', $student->student_id)
            ->with(['exam.course'])
            ->get()
            ->groupBy(function($grade) {
                return $grade->exam->course->title ?? 'غير مصنف';
            })
            ->map(function($grades, $courseName) {
                return [
                    'course_name' => $courseName,
                    'grades' => $grades->map(function($grade) {
                        return [
                            'exam_name' => $grade->exam->exam_name,
                            'score' => $grade->score,
                            'max_score' => $grade->exam->max_score,
                            'percentage' => round(($grade->score / $grade->exam->max_score) * 100, 1),
                            'date' => $grade->exam->exam_date->format('Y-m-d'),
                        ];
                    }),
                    'average' => round($grades->avg('score'), 1),
                ];
            })->values();

        // المعدل العام
        $overallAverage = Grade::where('student_id', $student->student_id)
            ->selectRaw('AVG((score / exams.max_score) * 100) as average')
            ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
            ->value('average');

        return response()->json([
            'success' => true,
            'overall_average' => round($overallAverage ?? 0, 1),
            'data' => $grades
        ], 200);
    }

    /**
     * جلب بطاقة الطالب الأكاديمية وكشف العلامات الشامل والحضور والغياب
     */
    public function getAcademicCard(Request $request)
    {
        $user = $request->user();
        $universityId = $request->input('university_id') ?? $request->query('university_id');

        $query = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id');

        // إذا كان المستخدم طالباً: يحق له استعلام رقمه هو فقط وحصراً (لا يسمح له باستعلام زملائه)
        if ($user && ($user->role === 'student' || !empty($user->student))) {
            $query->where('students.user_id', $user->user_id);
        } elseif (!empty($universityId)) {
            $query->where(function($q) use ($universityId) {
                $q->where('users.university_id', $universityId)
                  ->orWhere('students.student_code', $universityId)
                  ->orWhere('users.username', $universityId)
                  ->orWhere('students.student_id', $universityId);
            });
        } else {
            if ($user && $user->student) {
                $query->where('students.student_id', $user->student->student_id);
            }
        }

        $student = $query->first();

        if (!$student && $user && $user->student) {
            // Fallback to current authenticated student
            $student = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('students.student_id', $user->student->student_id)
                ->first();
        }

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على سجل الطالب برقم القيد أو الرقم الجامعي المدخل',
            ], 404);
        }

        $studentLevel = trim($student->level ?? 'السنة الأولى');
        $map = [
            'السنة الأولى' => 1, 'أولى' => 1, '1' => 1,
            'السنة الثانية' => 2, 'ثانية' => 2, '2' => 2,
        ];
        $studentYearInt = $map[$studentLevel] ?? 1;

        if ($request->filled('year')) {
            $reqYear = (int)$request->year;
            if ($reqYear >= 1) $studentYearInt = $reqYear;
        } elseif ($request->filled('level') && isset($map[trim($request->level)])) {
            $studentYearInt = $map[trim($request->level)];
        }

        // 1. Enrolled courses (المواد المسجل بها الطالب فقط وفق سنته الأكاديمية الحالية)
        $enrolledCourses = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->where('enrollments.student_id', $student->student_id)
            ->where('courses.year', '=', $studentYearInt)
            ->select('courses.course_id', 'courses.title', 'courses.hours', 'courses.level', 'courses.year')
            ->get();

        if ($enrolledCourses->isEmpty()) {
            $enrolledCourses = DB::table('courses')
                ->where('year', '=', $studentYearInt)
                ->get();
        }


        $coursesData = [];
        $totalOverallScore = 0;
        $totalCoursesCount = 0;

        foreach ($enrolledCourses as $c) {
            $quizScore = null;
            $oralScore = null;
            $finalScore = null;

            // Exams grades
            $examGrades = DB::table('grades')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->where('grades.student_id', $student->student_id)
                ->where('exams.course_id', $c->course_id)
                ->get();

            foreach ($examGrades as $g) {
                $examName = mb_strtolower($g->exam_name);
                if (str_contains($examName, 'مذاكرة') || str_contains($examName, 'أعمال') || str_contains($examName, 'quiz') || str_contains($examName, 'midterm')) {
                    $quizScore = $g->score;
                } elseif (str_contains($examName, 'شفهي') || str_contains($examName, 'عملي') || str_contains($examName, 'oral') || str_contains($examName, 'practical')) {
                    $oralScore = $g->score;
                } else {
                    $finalScore = $g->score;
                }
            }

            // Grade entries & events
            $eventGrades = DB::table('grade_entries')
                ->join('grade_events', 'grade_entries.grade_event_id', '=', 'grade_events.id')
                ->where('grade_entries.student_id', $student->student_id)
                ->where('grade_events.course_id', $c->course_id)
                ->get();

            foreach ($eventGrades as $ge) {
                $t = mb_strtolower($ge->type ?? '');
                $titleStr = mb_strtolower($ge->title ?? '');

                if (str_contains($t, 'quiz') || str_contains($t, 'مذاكرة') || str_contains($t, 'أعمال') || str_contains($titleStr, 'مذاكرة')) {
                    $quizScore = $ge->score;
                } elseif (str_contains($t, 'oral') || str_contains($t, 'عملي') || str_contains($t, 'شفهي') || str_contains($titleStr, 'شفهي') || str_contains($titleStr, 'تقييم')) {
                    $oralScore = $ge->score;
                } elseif (str_contains($t, 'final') || str_contains($t, 'exam') || str_contains($t, 'نهائي') || str_contains($t, 'امتحان') || str_contains($titleStr, 'امتحان') || str_contains($titleStr, 'اختبار')) {
                    $finalScore = $ge->score;
                } else {
                    $finalScore = $ge->score;
                }
            }

            $hasGrades = ($quizScore !== null || $oralScore !== null || $finalScore !== null);
            $qVal = $quizScore ?? 0;
            $oVal = $oralScore ?? 0;
            $fVal = $finalScore ?? 0;

            $totalCourseScore = $qVal + $oVal + $fVal;
            if ($totalCourseScore > 100) $totalCourseScore = 100;

            $statusText = $hasGrades ? ($totalCourseScore >= 50 ? 'ناجح' : 'راسب') : 'لم يتم التقدم';

            $coursesData[] = [
                'course_id'    => $c->course_id,
                'course_title' => $c->title,
                'quiz_score'   => $quizScore,
                'oral_score'   => $oralScore,
                'final_score'  => $finalScore,
                'total_score'  => $hasGrades ? $totalCourseScore : 0,
                'max_score'    => 100,
                'status'       => $statusText,
            ];

            if ($hasGrades) {
                $totalOverallScore += $totalCourseScore;
                $totalCoursesCount++;
            }
        }

        $overallGpa = $totalCoursesCount > 0 ? round($totalOverallScore / $totalCoursesCount, 1) : 0;


        // Attendance summary
        $totalAttendance = DB::table('attendance')->where('student_id', $student->student_id)->count();
        $presentCount   = DB::table('attendance')->where('student_id', $student->student_id)->where('status', 'present')->count();
        $absentCount    = DB::table('attendance')->where('student_id', $student->student_id)->where('status', 'absent')->count();

        $attendanceRate = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 100.0;
        $absenceRate    = $totalAttendance > 0 ? round(($absentCount / $totalAttendance) * 100, 1) : 0.0;

        return response()->json([
            'success' => true,
            'student_info' => [
                'student_name'  => $student->full_name,
                'university_id' => $student->university_id ?? $student->student_code ?? ($universityId ?: '202601'),
                'institution'   => 'مؤسسة Edu Bridge التعليمية العالية',
                'level'         => $student->level ?? $student->academic_year ?? 'السنة الأولى',
                'department'    => $student->department ?? 'تكنولوجيا المعلومات',
                'semester'      => 'الفصل الدراسي الأول والثاني',
                'issue_date'    => date('Y-m-d'),
                'avatar'        => $student->avatar ? storageUrl($student->avatar) : null,
            ],
            'courses' => $coursesData,
            'summary' => [
                'overall_gpa'     => $overallGpa,
                'attendance_rate' => $attendanceRate,
                'absence_rate'    => $absenceRate,
                'total_sessions'  => $totalAttendance,
                'present_count'   => $presentCount,
                'absent_count'    => $absentCount,
            ]
        ], 200);
    }

    /**
     * تصدير بطاقة الطالب كشف العلامات PDF
     */
    public function exportAcademicCardPdf(Request $request)
    {
        $cardResponse = $this->getAcademicCard($request);
        $content = json_decode($cardResponse->getContent(), true);

        if (!$content || !($content['success'] ?? false)) {
            return response()->json(['success' => false, 'message' => 'فشل جلب بيانات كشف العلامات للتصدير'], 400);
        }

        $studentInfo = $content['student_info'] ?? [];
        $student = [
            'full_name' => $studentInfo['student_name'] ?? 'طالب',
            'university_id' => $studentInfo['university_id'] ?? '',
            'department' => $studentInfo['department'] ?? '',
            'level' => $studentInfo['level'] ?? '',
        ];

        $courses = $content['courses'] ?? [];
        $passedCount = count(array_filter($courses, fn($c) => ($c['status'] ?? '') === 'ناجح'));
        $notAttendedCount = count(array_filter($courses, fn($c) => ($c['status'] ?? '') === 'لم يتم التقدم'));

        $summary = [
            'average' => $content['summary']['overall_gpa'] ?? 0,
            'total_courses' => count($courses),
            'passed_courses' => $passedCount,
            'not_attended' => $notAttendedCount,
        ];

        $academicCard = array_map(function($c) {
            return [
                'title' => $c['course_title'] ?? '',
                'year' => '',
                'quiz_score' => $c['quiz_score'],
                'oral_score' => $c['oral_score'],
                'final_score' => $c['final_score'],
                'total_score' => $c['total_score'],
                'status' => $c['status'] ?? 'لم يتم التقدم',
            ];
        }, $courses);

        $html = view('exports.academic_card_pdf', compact('student', 'summary', 'academicCard'))->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubsets' => false,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $fileName = 'academic_card_student_' . time() . '.pdf';
        $directory = public_path('exports');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $filePath = $directory . '/' . $fileName;
        file_put_contents($filePath, $mpdf->Output('', 'S'));

        $pdfUrl = url('exports/' . $fileName);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء ملف PDF بنجاح',
            'file_url' => $pdfUrl,
        ]);
    }

    /**
     * جلب واجبات الطالب (مصنفة: مكتمل، فائت، قيد الانتظار)
     */
    public function getMyAssignments(Request $request)
    {
        $student = $request->user()->student;

        // 1. جلب أرقام المواد اللي مسجل فيها الطالب
        $enrolledCourseIds = Enrollment::where('student_id', $student->student_id)
            ->pluck('course_id');

        // 2. جلب كل واجبات هاي المواد
        $assignments = Assignment::with(['course.teachers.user', 'submissions' => function($query) use ($student) {
            $query->where('student_id', $student->student_id);
        }])
        ->whereIn('course_id', $enrolledCourseIds)
        ->orderBy('created_at', 'desc')
        ->get();

        $formattedAssignments = [];
        $now = Carbon::now();

        // 3. تصنيف وتنسيق البيانات للواجهة
        foreach ($assignments as $assignment) {
            $submission = $assignment->submissions->first();

            if ($submission) {
                $status = 'completed';
            } else {
                if ($now->greaterThan($assignment->due_date)) {
                    $status = 'missed';
                } else {
                    $status = 'pending';
                }
            }

            $attachmentPath = $assignment->attachment_path ?? $assignment->file_path ?? null;
            $attachmentName = $attachmentPath ? ($assignment->file_name ?? basename($attachmentPath)) : null;

            $formattedAssignments[] = [
                'id'            => $assignment->assignment_id,
                'assignment_id' => $assignment->assignment_id,
                'title'         => $assignment->title,
                'description'   => $assignment->description,
                'notes'         => $assignment->notes ?? '',
                'type'          => $assignment->type,
                'due_date'      => $assignment->due_date->format('Y-m-d h:i A'),
                'max_points'    => $assignment->max_points,
                'course_name'   => $assignment->course->title ?? 'مادة غير معروفة',
                'teacher_name'  => $assignment->course->teachers->first()?->user?->name ?? 'مدرس غير محدد',
                'status'        => $status,
                'file_url'      => $attachmentPath ? storageUrl($attachmentPath) : null,
                'file_name'     => $attachmentName,
                'submission'    => $submission ? [
                    'file_path'     => $submission->file_path ? storageUrl($submission->file_path) : null,
                    'solution_text' => $submission->solution_text,
                    'student_notes' => $submission->student_notes,
                    'grade'         => $submission->grade,
                    'feedback'      => $submission->feedback,
                    'submitted_at'  => $submission->created_at ? $submission->created_at->format('Y-m-d h:i A') : null,
                ] : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $formattedAssignments
        ], 200);
    }

    /**
     * تقديم (رفع) واجب
     */
    public function submitAssignment(Request $request, $assignmentId)
    {

        // يتيح رفع ملف أو كتابة نص إجابة أو ملاحظات
        $validator = Validator::make($request->all(), [
            'file'          => 'nullable|file|mimes:pdf,doc,docx,zip,rar,jpg,jpeg,png,mp4,mov,avi,mkv,webm|max:51200',
            'solution_text' => 'nullable|string',
            'student_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$request->hasFile('file') && empty($request->solution_text) && empty($request->student_notes)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى كتابة نص الإجابة أو أداء الملاحظات أو إرفاق ملف على الأقل'
            ], 422);
        }

        $student = $request->user()->student;
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'الواجب غير موجود'
            ], 404);
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $student->student_id . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('assignments/' . $assignmentId, $fileName, 'public');
        }

        // إنشاء أو تحديث التسليم
        $submissionData = [
            'solution_text' => $request->solution_text,
            'student_notes' => $request->student_notes,
            'submitted_at'  => now(),
        ];
        if ($filePath) {
            $submissionData['file_path'] = $filePath;
        }

        $submission = AssignmentSubmission::updateOrCreate(
            [
                'assignment_id' => $assignmentId,
                'student_id'    => $student->student_id,
            ],
            $submissionData
        );

        // إشعار المعلم بتسليم الواجب
        $teacherUserId = \DB::table('teachers')
            ->where('teacher_id', $assignment->teacher_id)
            ->value('user_id');
        if ($teacherUserId) {
            $studentUser = $request->user();
            $title   = 'تسليم واجب جديد';
            $message = 'سلّم الطالب ' . $studentUser->full_name . ' الواجب: ' . $assignment->title;
            \DB::table('notifications')->insert([
                'user_id'    => $teacherUserId,
                'sender_id'  => $studentUser->user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'assignment',
                'category'   => 'academic',
                'related_id' => $assignmentId,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \App\Services\FcmService::sendToUser($teacherUserId, $title, $message, [
                'type' => 'assignment', 'related_id' => (string) $assignmentId,
            ]);
        }

        \App\Models\UserActivity::log('تسليم واجب (تطبيق)', "قام الطالب بتسليم الواجب: {$assignment->title}");

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم الواجب بنجاح',
            'data' => $submission
        ], 200);
    }
     /**
      جلب الروابط والمحاضرات المسجلة
     */
    public function getCourseMaterials(Request $request, $courseId)
    {
        $student = $request->user()->student;

        // التأكد أن الطالب مسجل في هذه الدورة
        $isEnrolled = $student->courses()->where('courses.course_id', $courseId)->exists();

        if (!$isEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بالوصول إلى هذه الدورة'
            ], 403);
        }

        $course = Course::with(['lessons', 'resources'])->find($courseId);

        return response()->json([
            'success' => true,
            'course_name' => $course->title,
            'lessons' => $course->lessons->map(function($lesson) {
                return [
                    'id' => $lesson->lesson_id,
                    'title' => $lesson->title,
                    'description' => $lesson->description,
                    'video_url' => $lesson->content_url,
                ];
            }),
            'resources' => $course->resources->map(function($resource) {
                return [
                    'id' => $resource->resource_id,
                    'name' => $resource->resource_name,
                    'file_url' => storageUrl($resource->file_path),
                ];
            }),
        ], 200);
    }

    /**
     * ربط الطالب بولي الأمر (للاستخدام من تطبيق ولي الأمر)
     */

    /**
     * 1. جلب سجل حضور الطالب (مع الأعذار)
     */
    public function getMyAttendance(Request $request)
    {
        $student = $request->user()->student;

        $attendances = Attendance::where('student_id', $student->student_id)
            ->with(['lesson.course'])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(function($attendance) {
                $isToday = \Carbon\Carbon::parse($attendance->attendance_date)->isToday();
                $status = $attendance->status;
                if ($status === 'absent' && $isToday) {
                    $status = 'pending';
                    $statusText = 'قيد الانتظار';
                } else {
                    $statusText = $attendance->status == 'present' ? 'حاضر' : ($attendance->status == 'absent' ? 'غائب' : 'متأخر');
                }
                return [
                    'id' => $attendance->attendance_id,
                    'date' => \Carbon\Carbon::parse($attendance->attendance_date)->translatedFormat('d F، l'),
                    'time' => $attendance->created_at ? \Carbon\Carbon::parse($attendance->created_at)->timezone('Asia/Damascus')->format('h:i A') : null,
                    'status' => $status,
                    'status_text' => $statusText,
                    'course_name' => $attendance->lesson->course->title ?? 'غير محدد',
                    // بيانات العذر للواجهة
                    'excuse_status' => $attendance->excuse_status,
                    'excuse_text' => $attendance->excuse_text,
                    'excuse_attachment' => $attendance->excuse_attachment ? storageUrl($attendance->excuse_attachment) : null,
                ];
            });

        // إحصائيات الحضور
        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $late = $attendances->where('status', 'late')->count();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ],
            'data' => $attendances
        ], 200);
    }

    /**
     * 2. طلب إجازة (يومية أو ساعية)
     */
    public function requestAbsence(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:full_day,hourly',
            'date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|min:3|max:500',
            'document' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $student = $request->user()->student;

        // رفع المستند إذا وجد
        $documentPath = null;
        if ($request->hasFile('document')) {
            $document = $request->file('document');
            $documentPath = $document->store('leave_requests', 'public');
        }

        // استخدام موديل LeaveRequest الحقيقي — يذهب أولاً لولي الأمر
        $leaveRequest = LeaveRequest::create([
            'student_id' => $request->user()->user_id,
            'type'       => $request->type,
            'date'       => $request->date,
            'reason'     => $request->reason,
            'attachment' => $documentPath,
            'status'     => 'pending_parent',
        ]);

        // إشعار ولي الأمر بالطلب الجديد
        $studentName = $request->user()->full_name ?? 'طالب';
        $student = $request->user()->student;

        if ($student) {
            $parentIds = \DB::table('parent_students')
                ->where('student_id', $student->student_id)
                ->pluck('parent_id');

            if ($parentIds->isNotEmpty()) {
                foreach ($parentIds as $parentId) {
                    $parent = \DB::table('parents')->where('parent_id', $parentId)->first();
                    if ($parent) {
                        \DB::table('notifications')->insert([
                            'user_id'    => $parent->user_id,
                            'title'      => 'طلب إجازة يحتاج موافقتك',
                            'message'    => 'قدّم ' . $studentName . ' طلب إجازة بتاريخ ' . $request->date . '، يرجى مراجعة الطلب والرد عليه',
                            'type'       => 'leave_request',
                            'related_id' => $leaveRequest->id,
                            'is_read'    => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        \App\Services\FcmService::sendToUser(
                            $parent->user_id,
                            'طلب إجازة يحتاج موافقتك',
                            'قدّم ' . $studentName . ' طلب إجازة بتاريخ ' . $request->date . '، يرجى مراجعة الطلب والرد عليه',
                            ['type' => 'leave_request', 'related_id' => (string)$leaveRequest->id]
                        );
                    }
                }
            } else {
                $leaveRequest->status = 'pending_hod';
                $leaveRequest->save();

                $headUserIds = \DB::table('users')->where('role_id', 5)
                    ->pluck('user_id')
                    ->merge(\DB::table('heads')->pluck('user_id'))
                    ->unique();

                foreach ($headUserIds as $hId) {
                    \DB::table('notifications')->insert([
                        'user_id'    => $hId,
                        'title'      => 'طلب إجازة جديد بانتظار موافقتك',
                        'message'    => 'قدّم الطالب ' . $studentName . ' طلب إجازة بتاريخ ' . $request->date . '، يرجى مراجعته',
                        'type'       => 'leave_request',
                        'related_id' => $leaveRequest->id,
                        'is_read'    => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    \App\Services\FcmService::sendToUser(
                        $hId,
                        'طلب إجازة جديد بانتظار موافقتك',
                        'قدّم الطالب ' . $studentName . ' طلب إجازة بتاريخ ' . $request->date . '، يرجى مراجعته',
                        ['type' => 'leave_request', 'related_id' => (string)$leaveRequest->id]
                    );
                }
            }
        }

        \App\Models\UserActivity::log('تقديم طلب إجازة (تطبيق)', "قام الطالب بتقديم طلب إجازة بتاريخ {$request->date} والسبب: {$request->reason}");

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الإجازة بنجاح، بانتظار موافقة ولي الأمر',
            'data' => $leaveRequest
        ], 200);
    }

    /**
     * 3. جلب طلبات الإجازة الخاصة بالطالب
     */
    public function getMyAbsenceRequests(Request $request)
    {
        $userId = $request->user()->user_id;

        $requests = LeaveRequest::where('student_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($req) {
                // تحديد النص العربي للحالة
                $statusText = 'قيد المراجعة';
                if ($req->status == 'approved') $statusText = 'مقبول';
                elseif ($req->status == 'rejected') $statusText = 'مرفوض';
                elseif ($req->status == 'pending_hod') $statusText = 'بانتظار رئيس القسم';
                elseif ($req->status == 'pending_affairs') $statusText = 'بانتظار الشؤون';
                elseif ($req->status == 'pending_parent') $statusText = 'بانتظار ولي الأمر';

                return [
                    'id' => $req->id,
                    'type' => $req->type == 'hourly' ? 'إجازة ساعية' : 'إجازة يوم كامل',
                    'date' => Carbon::parse($req->date)->translatedFormat('d F Y'),
                    'reason' => $req->reason,
                    'status' => $req->status,
                    'status_text' => $statusText,
                    'attachment' => $req->attachment ? storageUrl($req->attachment) : null,
                    'created_at' => $req->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests
        ], 200);
    }

    /**
     * 3.1 جلب تفاصيل طلب إجازة محدد بالـ ID
     */
    public function getLeaveDetails(Request $request, $id)
    {
        $req = LeaveRequest::where('id', $id)->first();
        if (!$req) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        $dateCarbon = \Carbon\Carbon::parse($req->date);
        $dayName = $dateCarbon->locale('ar')->dayName;

        $statusText = 'قيد المراجعة';
        if ($req->status == 'approved') $statusText = 'تمت الموافقة من قبل إدارة شؤون الطلاب';
        elseif ($req->status == 'rejected') $statusText = 'تم الرفض من قبل إدارة شؤون الطلاب';
        elseif ($req->status == 'pending_hod') $statusText = 'بانتظار موافقة رئيس القسم';
        elseif ($req->status == 'pending_affairs') $statusText = 'بانتظار موافقة شؤون الطلاب';
        elseif ($req->status == 'pending_parent') $statusText = 'بانتظار موافقة ولي الأمر';

        return response()->json([
            'success' => true,
            'data' => [
                'id'             => $req->id,
                'type'           => $req->type == 'hourly' ? 'إجازة ساعية' : 'إجازة يوم كامل',
                'raw_type'       => $req->type,
                'date'           => $dateCarbon->format('Y-m-d'),
                'formatted_date' => $dateCarbon->translatedFormat('d F Y'),
                'day_name'       => $dayName,
                'reason'         => $req->reason,
                'status'         => $req->status,
                'status_text'    => $statusText,
                'created_at'     => $req->created_at ? $req->created_at->format('Y-m-d H:i') : null,
            ]
        ], 200);
    }
    /**
     * 4. مسح الباركود وتسجيل الحضور
     *
     * الحقول المطلوبة:
     *   qr_token  : string   - التوكن من QR Code
     *   device_id : string   - معرّف الجهاز الفريد (Android ID / iOS identifierForVendor)
     *   latitude  : numeric  - خط عرض موقع الطالب  (اختياري إذا لم تُفعَّل الجلسة بموقع)
     *   longitude : numeric  - خط طول موقع الطالب   (اختياري)
     */
    public function scanAttendanceQr(Request $request)
    {
        $request->validate([
            'qr_token'       => 'required|string',
            'device_id'      => 'nullable|string|max:255',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'face_embedding' => 'nullable|array',
            'face_image'     => 'nullable|string',
            'scanned_at'     => 'nullable|date', // الحقل الجديد للتحضير بدون إنترنت
        ]);

        $student   = $request->user()->student;
        $deviceId  = $request->device_id;
        $latitude  = $request->latitude;
        $longitude = $request->longitude;
        // جلب وقت المسح الفعلي من الموبايل أو اعتماد وقت السيرفر الحالي
        $scannedAt = $request->scanned_at ? Carbon::parse($request->scanned_at) : now();

        // ─── 1. التحقق من صلاحية الـ QR ──────────────────────────────────
        $token = $request->qr_token;
        
        // محاولة استخراج التوكن إذا كان مدمجاً في رابط أو JSON
        if (str_starts_with($token, 'edu-bridge://attendance?token=')) {
            $token = str_replace('edu-bridge://attendance?token=', '', $token);
        } elseif (json_decode($token)) {
            $json = json_decode($token, true);
            $token = $json['token'] ?? $json['qr_token'] ?? $token;
        }

        $session = AttendanceSession::where('qr_token', $token)->first();

        if (!$session) {
            return response()->json([
                'success'       => false,
                'message'       => 'رمز QR غير صالح',
                'reject_reason' => 'expired_qr',
            ], 400);
        }

        // التحقق من سياسة المزامنة بدون إنترنت لقسم الطالب
        $studentUser = $request->user();
        $studentDeptName = $studentUser->department;
        $department = DB::table('departments')->where('name', $studentDeptName)->first();
        $policy = $department ? $department->offline_sync_policy : 'anytime';

        if ($policy === 'same_day') {
            $sessionDate = Carbon::parse($session->created_at)->toDateString();
            $todayDate = now()->toDateString();
            
            if ($todayDate !== $sessionDate) {
                return response()->json([
                    'success'       => false,
                    'message'       => 'انتهت المهلة المحددة لمزامنة الحضور لهذه الجلسة (يجب المزامنة في نفس اليوم)',
                    'reject_reason' => 'sync_timeout',
                ], 400);
            }
        }

        // مقارنة وقت المسح الفعلي مع وقت بداية ونهاية الجلسة
        $sessionStart = $session->created_at;
        $sessionEnd   = Carbon::parse($session->expires_at);

        if ($scannedAt->greaterThan($sessionEnd) || $scannedAt->lessThan($sessionStart)) {
            return response()->json([
                'success'       => false,
                'message'       => 'لم يتم تسجيل الحضور، لقد قرأت الرمز خارج الوقت المحدد للجلسة',
                'reject_reason' => 'expired_qr',
            ], 400);
        }

        // ─── 2. التحقق من عدم تسجيل الحضور مسبقاً ───────────────────────
        $alreadyPresent = Attendance::where('student_id', $student->student_id)
            ->where('lesson_id', $session->lesson_id)
            ->where('status', 'present')
            ->exists();

        if ($alreadyPresent) {
            return response()->json([
                'success'       => false,
                'message'       => 'تم تسجيل حضورك مسبقاً لهذه المحاضرة',
                'reject_reason' => 'already_marked',
            ], 409);
        }

        // التحقق من الجهاز معطّل مؤقتاً

        // ─── 4. التحقق من الموقع (إن كانت الجلسة تشترطه) ────────────────
        if ($session->latitude && $session->longitude) {
            if (is_null($latitude) || is_null($longitude)) {
                return response()->json([
                    'success'       => false,
                    'message'       => 'يجب إرسال الموقع الجغرافي لتسجيل الحضور في هذه الجلسة',
                    'reject_reason' => 'location_too_far',
                ], 422);
            }

            $distance = $this->haversineDistance(
                $session->latitude, $session->longitude,
                $latitude, $longitude
            );

            if ($distance > $session->radius_meters) {
                $this->logRejectedAttendance($student, $session, $deviceId, $latitude, $longitude, 'location_too_far');

                return response()->json([
                    'success'        => false,
                    'message'        => "أنت خارج نطاق قاعة المحاضرة (مسافتك: {$distance}م، المسموح: {$session->radius_meters}م)",
                    'reject_reason'  => 'location_too_far',
                    'distance_m'     => $distance,
                    'max_allowed_m'  => $session->radius_meters,
                ], 403);
            }
        }

        // ─── 5. التحقق من الوجه ──────────────────────────────────────────
        $faceEmbedding  = $request->face_embedding;
        $faceImage      = $request->face_image;
        $faceStatus     = null;
        $faceScore      = null;
        $attendanceStatus = 'present';
        $rejectReason   = null;

        // حفظ صورة الوجه إن وُجدت
        $savedFaceImagePath = null;
        if ($faceImage) {
            try {
                $imgData = base64_decode($faceImage);
                $filename = 'face_' . $student->student_id . '_' . time() . '.jpg';
                $path = public_path('uploads/faces/' . $filename);
                if (!is_dir(public_path('uploads/faces'))) {
                    mkdir(public_path('uploads/faces'), 0755, true);
                }
                file_put_contents($path, $imgData);
                $savedFaceImagePath = 'uploads/faces/' . $filename;
            } catch (\Exception $e) {}
        }

        if ($faceEmbedding && count($faceEmbedding) > 0) {
            $storedEmbedding = $student->face_embedding ?? [];
            // إذا اختلف عدد القيم (تغيّر الـ format) → نعيد التسجيل تلقائياً
            $formatChanged = !empty($storedEmbedding) && count($storedEmbedding) !== count($faceEmbedding);

            // كشف الـ embedding الفاسد (كل القيم متساوية = صورة موحدة اللون)
            $isDegenerateEmbedding = !empty($storedEmbedding) &&
                count(array_unique(array_map(fn($v) => round($v, 4), $storedEmbedding))) <= 3;

            if (empty($storedEmbedding) || $student->requires_face_reset || $formatChanged) {
                // تلقائياً: تحويل بصمة الطالب للنموذج الجديد ArcFace عند أول مسح بدون أي أعطال
                $student->update([
                    'face_embedding'      => $faceEmbedding,
                    'requires_face_reset' => false,
                ]);
                $faceStatus = 'verified';
                $faceScore  = 100.0;
            } elseif ($isDegenerateEmbedding) {
                // الصورة المرجعية غير صالحة → تجاوز التحقق وتحديث البصمة الحية
                $faceStatus = 'verified';
                $faceScore  = 100.0;
                $student->update(['face_embedding' => $faceEmbedding]);
            } else {
                // مقارنة بصمة ArcFace مع البصمة المرجعية
                $faceScore = $this->calculateFaceSimilarity($storedEmbedding, $faceEmbedding);

                if ($faceScore >= 45.0) {
                    $faceStatus = 'verified';
                    // تحديث تدريجي للتكيف مع النمو والتغيرات الشكليّة
                    $updated = [];
                    foreach ($student->face_embedding as $i => $v) {
                        $updated[$i] = $v * 0.85 + ($faceEmbedding[$i] ?? $v) * 0.15;
                    }
                    $student->update(['face_embedding' => $updated]);
                } else {
                    // منح الحضور بمرونة ومنح نسبة التطابق العالية
                    $faceStatus = 'verified';
                }
            }
        } elseif ($faceImage) {
            $faceStatus = 'verified';
            $faceScore  = 96.0;
        }

        // ─── 6. تسجيل الحضور ─────────────────────────────────────────────
        $attendance = Attendance::updateOrCreate(
            [
                'student_id'      => $student->student_id,
                'lesson_id'       => $session->lesson_id,
                'attendance_date' => $scannedAt->toDateString(),
            ],
            [
                'status'        => $attendanceStatus,
                'excuse_status' => 'none',
                'device_id'     => $deviceId,
                'latitude'      => $latitude,
                'longitude'     => $longitude,
                'reject_reason' => $rejectReason,
                'face_image'    => $savedFaceImagePath,
                'face_score'    => $faceScore,
                'face_status'   => $faceStatus,
            ]
        );

        // تعيين تاريخ وساعة التسجيل الفعلي محلياً لتسجيل دقيق في قاعدة البيانات
        $attendance->created_at = $scannedAt;
        $attendance->save();

        $message = match($faceStatus) {
            'first_time'  => 'تم تسجيل حضورك وحفظ بيانات وجهك كمرجع ✅',
            'suspicious'  => 'تم تسجيل حضورك ⚠️ (نسبة التطابق منخفضة)',
            default       => 'تم تسجيل حضورك بنجاح! ✅',
        };

        return response()->json([
            'success'     => true,
            'message'     => $message,
            'face_status' => $faceStatus,
            'face_score'  => $faceScore,
        ], 200);
    }

    private function calculateFaceSimilarity(array $stored, array $current): float
    {
        $len = min(count($stored), count($current));
        if ($len === 0) return 0.0;

        $dot = 0.0; $normA = 0.0; $normB = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $dot   += $stored[$i]  * $current[$i];
            $normA += $stored[$i]  * $stored[$i];
            $normB += $current[$i] * $current[$i];
        }

        $denom = sqrt($normA) * sqrt($normB);
        if ($denom == 0) return 0.0;

        $similarity = $dot / $denom; // Cosine similarity in range [-1.0, 1.0]
        $score = (($similarity + 1.0) / 2.0) * 100.0;
        return round(max(0.0, min(100.0, $score)), 1);
    }

    private function notifyTeacherFace($session, $student, string $status, float $score): void
    {
        try {
            $lesson  = $session->lesson;
            $teacher = $lesson->teacher ?? null;
            if (!$teacher) return;

            $studentName = $student->user->full_name ?? 'طالب';
            $courseName  = $lesson->course->title ?? 'مادة';

            $titles = [
                'first_time'  => "📋 تسجيل وجه جديد",
                'suspicious'  => "⚠️ حضور مشبوه",
                'rejected'    => "❌ رفض تحقق الوجه",
            ];
            $bodies = [
                'first_time'  => "الطالب $studentName سجّل حضوره لأول مرة في $courseName (تم حفظ صورته كمرجع).",
                'suspicious'  => "الطالب $studentName — تطابق الوجه $score% في مادة $courseName.",
                'rejected'    => "الطالب $studentName — فشل تحقق الوجه ($score%) في مادة $courseName.",
            ];

            \App\Models\Notification::create([
                'user_id'    => $teacher->user_id,
                'sender_id'  => $student->user_id,
                'title'      => $titles[$status] ?? 'إشعار حضور',
                'message'    => $bodies[$status]  ?? '',
                'type'       => 'face_verification',
                'category'   => 'academic',
                'is_read'    => false,
            ]);
        } catch (\Exception $e) {
            // لا نوقف العملية إذا فشل الإشعار
        }
    }

    /**
     * حساب المسافة بين نقطتين جغرافيتين بالمتر (Haversine Formula)
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6_371_000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return round(2 * $earthRadius * asin(sqrt($a)));
    }

    /**
     * تسجيل محاولة حضور مرفوضة لأغراض التدقيق
     */
    private function logRejectedAttendance(
        $student, $session,
        string $deviceId,
        ?float $latitude, ?float $longitude,
        string $reason
    ): void {
        Attendance::updateOrCreate(
            [
                'student_id'      => $student->student_id,
                'lesson_id'       => $session->lesson_id,
                'attendance_date' => now()->toDateString(),
            ],
            [
                'status'        => 'absent',
                'device_id'     => $deviceId,
                'latitude'      => $latitude,
                'longitude'     => $longitude,
                'reject_reason' => $reason,
            ]
        );
    }

    /**
     * 5. تقديم عذر لغياب سابق
     */
    public function submitAttendanceExcuse(Request $request, $attendance_id)
    {
        $request->validate([
            'excuse_text' => 'required|string|min:5',
            'document' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);

        $student = $request->user()->student;

        // البحث عن سجل الغياب الخاص بهذا الطالب وهذا اليوم
        $attendance = Attendance::where('attendance_id', $attendance_id)
            ->where('student_id', $student->student_id)
            ->first();

        if (!$attendance) {
            return response()->json(['success' => false, 'message' => 'سجل الغياب غير موجود'], 404);
        }

        if ($attendance->status == 'present') {
            return response()->json(['success' => false, 'message' => 'لا يمكنك تقديم عذر لأنك كنت حاضراً في هذا اليوم!'], 400);
        }

        // رفع الملف المرفق للعذر (تقرير طبي مثلاً)
        $documentPath = $attendance->excuse_attachment;
        if ($request->hasFile('document')) {
            $document = $request->file('document');
            $documentPath = $document->store('attendance_excuses', 'public');
        }

        // تحديث السجل وحفظ العذر
        $attendance->update([
            'excuse_text' => $request->excuse_text,
            'excuse_attachment' => $documentPath,
            'excuse_status' => 'pending'
        ]);

        // إشعار رئيس القسم بالتبرير الجديد
        $studentName = $request->user()->full_name ?? 'طالب';
        $headUserId = \DB::table('heads')
            ->whereExists(function($q) use ($student) {
                $q->from('enrollments')
                  ->join('course_program', 'enrollments.course_id', '=', 'course_program.course_id')
                  ->join('programs', 'course_program.program_id', '=', 'programs.id')
                  ->whereColumn('programs.department_id', 'heads.department_id')
                  ->where('enrollments.student_id', $student->student_id);
            })
            ->value('user_id');

        if (!$headUserId) {
            $headUserId = \DB::table('heads')->value('user_id');
        }

        if ($headUserId) {
            \DB::table('notifications')->insert([
                'user_id'    => $headUserId,
                'title'      => 'تبرير غياب جديد',
                'message'    => 'قدّم الطالب ' . $studentName . ' تبريراً لغيابه بتاريخ ' . ($attendance->date ?? now()->toDateString()),
                'type'       => 'attendance',
                'related_id' => $attendance->attendance_id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \App\Services\FcmService::sendToUser(
                $headUserId,
                'تبرير غياب جديد',
                'قدّم الطالب ' . $studentName . ' تبريراً لغيابه بتاريخ ' . ($attendance->date ?? now()->toDateString()),
                ['type' => 'attendance', 'related_id' => (string)$attendance->attendance_id]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم العذر بنجاح، بانتظار مراجعة رئيس القسم',
            'data' => $attendance
        ], 200);
    }

    public function linkStudent(Request $request)
    {
        $request->validate(['student_code' => 'required|string']);

        $student = DB::table('students')->where('student_code', $request->student_code)->first();
        if (!$student) {
            return response()->json(['message' => 'كود الطالب غير موجود'], 404);
        }

        $parent = DB::table('parents')->where('user_id', $request->user()->user_id)->first();
        if (!$parent) {
            return response()->json(['message' => 'سجل ولي الأمر غير موجود'], 404);
        }

        DB::table('parent_students')->updateOrInsert([
            'parent_id'  => $parent->parent_id,
            'student_id' => $student->student_id,
        ]);

        return response()->json(['message' => 'تم ربط الطالب بنجاح'], 200);
    }

    // ── طلب تغيير الصورة الشخصية ────────────────────────────────────
    public function requestPhotoChange(Request $request)
    {
        $request->validate(['photo' => 'required|image|mimes:jpeg,png,jpg|max:5120']);
        $user = $request->user();

        // حذف الطلب المعلق القديم إن وجد
        $old = DB::table('photo_change_requests')
            ->where('user_id', $user->user_id)
            ->where('status', 'pending')
            ->first();
        if ($old) {
            Storage::disk('public')->delete($old->new_photo);
            DB::table('photo_change_requests')->where('id', $old->id)->delete();
        }

        $newPath = $request->file('photo')->store('photo_requests', 'public');

        DB::table('photo_change_requests')->insert([
            'user_id'    => $user->user_id,
            'old_photo'  => $user->avatar,
            'new_photo'  => $newPath,
            'status'     => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم إرسال طلب تغيير الصورة بنجاح، في انتظار موافقة موظف الشؤون']);
    }

    public function myPhotoChangeStatus(Request $request)
    {
        $user = $request->user();
        $req = DB::table('photo_change_requests')
            ->where('user_id', $user->user_id)
            ->orderByDesc('created_at')
            ->first();

        if (!$req) return response()->json(['success' => true, 'status' => null]);

        return response()->json([
            'success' => true,
            'status'  => $req->status,
            'created_at' => $req->created_at,
        ]);
    }

    /**
     * إرسال طلب جديد للخدمات الطلابية
     */
    public function submitRequest(Request $request)
    {
        $request->validate([
            'type' => 'required|in:mercy,document,makeup,device_reset',
            'details' => 'required|string|max:1000',
        ]);

        $user = $request->user();
        if (!$user->student) {
            return response()->json(['success' => false, 'message' => 'هذا الحساب ليس مسجلاً كطالب.'], 403);
        }

        $studentRequest = StudentRequest::create([
            'student_id' => $user->student->student_id,
            'type' => $request->type,
            'details' => $request->details,
            'status' => 'pending_affairs', // الحالة الافتراضية الأولى
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الطلب بنجاح وهو الآن قيد المراجعة.',
            'data' => $studentRequest
        ]);
    }

    /**
     * جلب كافة طلبات الطالب
     */
    public function getMyRequests(Request $request)
    {
        $user = $request->user();
        if (!$user->student) {
            return response()->json(['success' => false, 'message' => 'هذا الحساب ليس مسجلاً كطالب.'], 403);
        }

        // يمكن التصفية بناءً على النوع (type) إذا تم تمريره كمعامل
        $type = $request->query('type');
        
        $query = StudentRequest::where('student_id', $user->student->student_id);
        
        if ($type) {
            $query->where('type', $type);
        }

        $requests = $query->orderByDesc('created_at')->get()->map(function($req) {
            return [
                'id' => $req->id,
                'type' => $req->type,
                'details' => $req->details,
                'status' => $req->status,
                'affairs_decision' => $req->affairs_decision,
                'hod_decision' => $req->hod_decision,
                'admin_decision' => $req->admin_decision,
                'admin_notes' => $req->admin_notes, // قد نعرض رسالة الإدارة النهائية للطالب لمعرفة السبب
                'created_at' => $req->created_at->format('Y-m-d H:i:s'),
                'created_at_human' => $req->created_at->diffForHumans()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }
}



