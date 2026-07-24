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

        // 🌟 2. جلب الإعلانات (العامة + المخصصة لقسم الطالب فقط)
        $announcements = Announcement::with('author')
            ->where(function($query) use ($user) {
                $query->whereNull('department_id')
                      ->orWhere('department_id', $user->department_id);
            })
            ->where(function($q) {
                $q->whereNull('target_role')->orWhere('target_role', 'student');
            })
            ->latest()
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
                    'image_url' => $item->image ? url('storage/' . $item->image) : null,
                    'link_url' => $item->link_url ?? null,
                    'author_name' => $item->author->full_name ?? 'الإدارة',
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

        return response()->json([
            'success' => true,
            'message' => 'تم جلب البيانات بنجاح',
            'data' => [
                // 🌟 رجعناها خفيفة ونظيفة مثل ما طلبتي بالضبط!
                'student' => [
                    'id' => $user->user_id,
                    'name' => $user->full_name,
                    'avatar' => $user->avatar ? storageUrl($user->avatar) : null,
                    'department' => $user->department ?? 'غير محدد',
                ],
                'next_lecture' => $nextLecture,
                'announcements' => $announcements,
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
                'academic_year' => $user->academic_year ?? 'غير محدد',
                'birth_date' => $user->birth_date ? $user->birth_date->format('Y-m-d') : null,
                'gender' => $user->gender ?? 'غير محدد',
                'level' => $student->level ?? 'غير محدد',
                // 🌟 إضافة رابط الصورة (إذا مافي صورة بنرجع null)
                'avatar' => $user->avatar ? storageUrl($user->avatar) : null,
                'reference_photo_url' => $student->reference_photo ? url('storage/' . $student->reference_photo) : null,
                'has_face_embedding' => (!empty($student->face_embedding) && !$student->requires_face_reset),
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

        // 🌟 إضافة (with('sender')) لجلب بيانات من أرسل الإشعار
        $notifications = Notification::with('sender')
            ->where('user_id', $user->user_id)
            ->latest()
            ->get()
            ->map(function ($notify) {
                $imageUrl = null;
                $linkUrl = null;
                if ($notify->type === 'announcement' && $notify->related_id) {
                    $ann = \DB::table('announcements')->where('announcement_id', $notify->related_id)->first(['image', 'link_url']);
                    $imageUrl = $ann && $ann->image ? url('storage/' . $ann->image) : null;
                    $linkUrl  = $ann->link_url ?? null;
                }
                return [
                    'id' => $notify->id,
                    'title' => $notify->title,
                    'message' => $notify->message,
                    'type' => $notify->type,
                    'category' => $notify->category,
                    'sender_name' => $notify->sender->full_name ?? 'الإدارة',
                    'is_read' => (bool)$notify->is_read,
                    'related_id' => $notify->related_id,
                    'image_url'  => $imageUrl,
                    'link_url'   => $linkUrl,
                    'created_at' => $notify->created_at ? $notify->created_at->format('Y-m-d H:i:s') : null,
                    'time_ago' => $notify->created_at ? $notify->created_at->diffForHumans() : 'منذ قليل',
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الإشعارات بنجاح',
            'data' => [
                // 🌟 استخدام حقل category الجديد للتقسيم بشكل مباشر وأدق
                'academic' => $notifications->where('category', 'academic')->values(),
                'administrative' => $notifications->where('category', 'administrative')->values(),
                'all' => $notifications->values(),
            ]
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

        $courses = $student->courses()
            ->with(['teacher.user', 'schedule'])
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->course_id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'level' => $course->level,
                    'teacher_name' => $course->teacher->user->full_name ?? 'غير محدد',
                    'schedule' => $course->schedule ? [
                        'day' => $course->schedule->day,
                        'start_time' => $course->schedule->start_time,
                        'end_time' => $course->schedule->end_time,
                        'room' => $course->schedule->room,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $courses
        ], 200);
    }

    /**
     * جلب كافة مواد الاختصاص للطالب (للعامين)
     */
    public function getProgramCourses(Request $request)
    {
        $student = $request->user()->student;
        $user = $request->user();

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
                'data' => []
            ], 200);
        }

        $courses = $program->courses->map(function ($course) {
            return [
                'id' => $course->course_id,
                'title' => $course->title,
                'description' => $course->description,
                'level' => $course->level,
                'year' => $course->year,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $courses
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

                    if (str_contains($title, 'حضور') || 
                        str_contains($title, 'غياب') || 
                        str_contains($title, 'تفقد') || 
                        str_contains($title, 'حصة') ||
                        str_contains($url, 'attendance') || 
                        $type === 'session') {
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
            ->groupBy('day')
            ->map(function($items, $day) {
                // ترجمة الأيام إلى العربية إذا كانت بالإنجليزية (لتتوافق مع تطبيق الموبايل)
                $dayMap = [
                    'Sunday'    => 'الأحد',
                    'Monday'    => 'الاثنين',
                    'Tuesday'   => 'الثلاثاء',
                    'Wednesday' => 'الأربعاء',
                    'Thursday'  => 'الخميس',
                    'Friday'    => 'الجمعة',
                    'Saturday'  => 'السبت',
                ];
                $translatedDay = $dayMap[$day] ?? $day;

                return [
                    'day' => $translatedDay,
                    'lectures' => $items->map(function($item) {
                        return [
                            // 💡 2. التعديل: استخدمنا title بدل course_name
                            'course_name' => $item->course->title ?? 'مادة غير معروفة',

                            // 💡 3. التعديل: جلب اسم أول مدرس من قائمة المدرسين
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

        $pdf = \Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::loadView('exports.exams_pdf', compact('exams', 'student'), [], [
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $fileName = 'exams_' . $student->student_id . '_' . time() . '.pdf';
        $directory = public_path('exports');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $filePath = $directory . '/' . $fileName;
        file_put_contents($filePath, $pdf->output());

        $pdfUrl = url('exports/' . $fileName);

        return response()->json([
            'success'  => true,
            'pdf_url'  => $pdfUrl,
            'data'     => $exams,
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
                    'student_notes' => $submission->student_notes,
                    'grade'         => $submission->grade,
                    'feedback'      => $submission->feedback,
                    'submitted_at'  => $submission->created_at->format('Y-m-d h:i A'),
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

        // تم التعديل ليقبل 50 ميجا (51200 كيلوبايت) وإضافة الصور حسب تصميمك
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx,zip,jpg,jpeg,png,mp4|max:51200',
            'student_notes' => 'nullable|string' // استقبال ملاحظات الطالب
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
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

        // اختياري: منع التسليم إذا انتهى الوقت (حسب قوانين تطبيقك)
        // if (Carbon::now()->greaterThan($assignment->due_date)) {
        //     return response()->json(['success' => false, 'message' => 'عذراً، انتهى وقت تسليم هذا الواجب'], 403);
        // }

        // رفع الملف
        $file = $request->file('file');
        // استخدام student_id لضمان عدم تكرار الأسماء
        $fileName = time() . '_' . $student->student_id . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('assignments/' . $assignmentId, $fileName, 'public');

        // إنشاء أو تحديث التسليم
        $submission = AssignmentSubmission::updateOrCreate(
            [
                'assignment_id' => $assignmentId,
                'student_id' => $student->student_id,
            ],
            [
                'file_path' => $filePath,
                'student_notes' => $request->student_notes,
                'grade' => null,
                'feedback' => null,
                'submitted_at' => now(),
            ]
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
                    'time' => $attendance->created_at ? \Carbon\Carbon::parse($attendance->created_at)->format('h:i A') : null,
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
        }

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
                return response()->json([
                    'success'       => false,
                    'message'       => 'بصمة الوجه الخاصة بك غير مهيأة، يرجى تهيئتها أولاً.',
                    'reject_reason' => 'face_not_initialized',
                ], 400);
            } elseif ($isDegenerateEmbedding) {
                // الصورة المرجعية غير صالحة → تجاوز التحقق ومنح الحضور
                $faceStatus = 'verified';
                $faceScore  = 100.0;
                // إعادة تعيين الـ embedding بالقيمة الحية
                $student->update(['face_embedding' => $faceEmbedding]);
            } else {
                // مقارنة الـ embedding مع المرجع
                $faceScore = $this->calculateFaceSimilarity($storedEmbedding, $faceEmbedding);

                if ($faceScore >= 55) {
                    $faceStatus = 'verified';
                    // تحديث تدريجي للمرجع (10%) للتكيف مع التغيرات الطبيعية
                    $updated = [];
                    foreach ($student->face_embedding as $i => $v) {
                        $updated[$i] = $v * 0.9 + ($faceEmbedding[$i] ?? $v) * 0.1;
                    }
                    $student->update(['face_embedding' => $updated]);
                } elseif ($faceScore >= 25) {
                    $faceStatus = 'suspicious';
                    $this->notifyTeacherFace($session, $student, 'suspicious', $faceScore);
                } else {
                    // درجة منخفضة جداً → نسجل الحضور كـ suspicious بدل الرفض
                    $faceStatus = 'suspicious';
                    $this->notifyTeacherFace($session, $student, 'suspicious', $faceScore);
                }
            }
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

        $similarity = $dot / $denom; // [-1, 1]
        return round(max(0, $similarity * 100), 1); // [0, 100]
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
                'body'       => $bodies[$status]  ?? '',
                'type'       => 'face_verification',
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
            'type' => 'required|in:mercy,document,makeup',
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



