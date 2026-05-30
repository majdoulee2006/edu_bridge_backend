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
        $nextLecture = null;
        $today = now()->format('l');

        $schedule = Schedule::whereIn('course_id', $enrolledCourseIds)
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
     * جلب المحاضرات (الدروس) المجمعة حسب المادة
     */
    public function getMyLectures(Request $request)
    {
        $student = $request->user()->student;

        // التعديل 1: غيرنا teacher إلى teachers
        $courses = $student->courses()
            ->with(['teachers.user', 'lessons'])
            ->get()
            ->map(function ($course) {
                return [
                    'course_id' => $course->course_id,
                    'course_name' => $course->title,
                    // التعديل 2: جلبنا أول دكتور من مصفوفة الدكاترة
                    'teacher_name' => $course->teachers->first()?->user->full_name ?? 'مدرس غير محدد',
                    'total_files' => $course->lessons->count(),

                    'lessons' => $course->lessons->map(function ($lesson) {
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
        $student = $request->user()->student;

        $schedules = Schedule::whereHas('course', function($query) use ($student) {
                $query->whereHas('students', function($q) use ($student) {
                    $q->where('enrollments.student_id', $student->student_id);
                });
            })
           ->with(['course', 'course.teachers.user']) // 💡 السحر هنا لجلب اليوزر مع المدرس
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day')
            ->map(function($items, $day) {
                return [
                    'day' => $day,
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

        // نجلب الامتحانات للمواد التي يدرسها الطالب فقط (مع تجنب الغموض في student_id)
        $exams = Exam::whereHas('course', function($query) use ($student) {
                $query->whereHas('students', function($q) use ($student) {
                    $q->where('enrollments.student_id', $student->student_id);
                });
            })
            ->with('course')
            ->orderBy('exam_date') // ترتيب من الأقرب للأبعد
            ->get()
            ->map(function($exam) {
                $date = Carbon::parse($exam->exam_date);
                return [
                    'exam_id'  => $exam->exam_id,
                    // استخدمنا title بدل course_name
                    'subject'  => $exam->exam_name ?? ($exam->course->title ?? 'امتحان مادة'),
                    'day_num'  => $date->format('d'),
                    'month'    => $date->translatedFormat('F'), // مثال: يونيو
                    'day_name' => $date->translatedFormat('l'), // مثال: الأحد
                    'time'     => $date->format('h:i A'),
                    'duration' => 'ساعتان', // يمكنك جعلها ديناميكية لاحقاً
                    'room'     => 'القاعة الامتحانية',
                    'type'     => 'نهائي'
                ];
            });

        return response()->json([
            'success' => true,
            'data'   => $exams
        ], 200);
    }
    /**
     * تصدير جدول الامتحانات (PDF) — يرجع البيانات كـ JSON
     */
    public function exportExamsPdf(Request $request)
    {
        $student = $request->user()->student;

        $exams = Exam::whereHas('course', function($query) use ($student) {
                $query->whereHas('students', function($q) use ($student) {
                    $q->where('enrollments.student_id', $student->student_id);
                });
            })
            ->with('course')
            ->orderBy('exam_date')
            ->get()
            ->map(function($exam) {
                $date = Carbon::parse($exam->exam_date);
                return [
                    'subject'  => $exam->exam_name ?? ($exam->course->title ?? 'امتحان'),
                    'date'     => $date->translatedFormat('d F Y'),
                    'day'      => $date->translatedFormat('l'),
                    'time'     => $date->format('h:i A'),
                ];
            });

        return response()->json([
            'success'  => true,
            'pdf_url'  => null,
            'data'     => $exams,
        ], 200);
    }

    /**
     * تصدير جدول الامتحانات (Excel) — يرجع البيانات كـ JSON
     */
    public function exportExamsExcel(Request $request)
    {
        $student = $request->user()->student;

        $exams = Exam::whereHas('course', function($query) use ($student) {
                $query->whereHas('students', function($q) use ($student) {
                    $q->where('enrollments.student_id', $student->student_id);
                });
            })
            ->with('course')
            ->orderBy('exam_date')
            ->get()
            ->map(function($exam) {
                $date = Carbon::parse($exam->exam_date);
                return [
                    'subject'  => $exam->exam_name ?? ($exam->course->title ?? 'امتحان'),
                    'date'     => $date->format('Y-m-d'),
                    'time'     => $date->format('h:i A'),
                ];
            });

        return response()->json([
            'success'    => true,
            'excel_url'  => null,
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

            $attachmentPath = $assignment->attachment_path ?? null;
            $attachmentName = $attachmentPath ? basename($attachmentPath) : null;

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
        $isEnrolled = $student->courses()->where('course_id', $courseId)->exists();

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
                return [
                    'id' => $attendance->attendance_id,
                    'date' => Carbon::parse($attendance->attendance_date)->translatedFormat('d F، l'),
                    'time' => $attendance->created_at ? Carbon::parse($attendance->created_at)->format('h:i A') : null,
                    'status' => $attendance->status,
                    'status_text' => $attendance->status == 'present' ? 'حاضر' : ($attendance->status == 'absent' ? 'غائب' : 'متأخر'),
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
            'qr_token'  => 'required|string',
            'device_id' => 'required|string|max:255',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $student   = $request->user()->student;
        $deviceId  = $request->device_id;
        $latitude  = $request->latitude;
        $longitude = $request->longitude;

        // ─── 1. التحقق من صلاحية الـ QR ──────────────────────────────────
        $session = AttendanceSession::where('qr_token', $request->qr_token)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            return response()->json([
                'success'       => false,
                'message'       => 'رمز QR غير صالح أو انتهت صلاحيته',
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

        // ─── 3. التحقق من الجهاز ──────────────────────────────────────────
        if ($student->device_id) {
            // الطالب لديه جهاز مسجّل → يجب أن يتطابق
            if ($student->device_id !== $deviceId) {
                $this->logRejectedAttendance($student, $session, $deviceId, $latitude, $longitude, 'device_mismatch');

                return response()->json([
                    'success'       => false,
                    'message'       => 'هذا الجهاز غير مصرح له بتسجيل حضورك. يرجى استخدام جهازك المسجّل.',
                    'reject_reason' => 'device_mismatch',
                ], 403);
            }
        } else {
            // أول مرة → نربط الجهاز بالطالب تلقائياً
            $student->update([
                'device_id'        => $deviceId,
                'is_device_locked' => true,
            ]);
        }

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

        // ─── 5. تسجيل الحضور ─────────────────────────────────────────────
        $attendance = Attendance::updateOrCreate(
            [
                'student_id'      => $student->student_id,
                'lesson_id'       => $session->lesson_id,
                'attendance_date' => now()->toDateString(),
            ],
            [
                'status'        => 'present',
                'excuse_status' => 'none',
                'device_id'     => $deviceId,
                'latitude'      => $latitude,
                'longitude'     => $longitude,
                'reject_reason' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل حضورك بنجاح!',
            'data'    => $attendance,
        ], 200);
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
}



