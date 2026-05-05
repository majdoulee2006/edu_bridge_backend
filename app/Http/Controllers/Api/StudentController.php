<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Storage;
use App\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AbsenceRequest;
use App\Models\Course;
use App\Models\Schedule;
class StudentController extends Controller
{
    /**
     * لوحة التحكم الرئيسية للطالب
     */
  public function getDashboardData(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        // 🌟 1. استخراج أرقام الكورسات أولاً لأننا سنحتاجها في المحاضرات والإعلانات معاً
        $enrolledCourseIds = $student ? $student->courses->modelKeys() : [];

        // 🌟 2. جلب آخر 5 إعلانات (باستخدام الاستهداف الذكي)
        $announcements = Announcement::where(function($query) use ($user, $student, $enrolledCourseIds) {
            
            // أ) إعلانات عامة (للكل)
            $query->where('type', 'general')
                  ->orWhereNull('target_role');
                  
            // ب) إعلانات مخصصة للطلاب بشكل عام أو حسب القسم والسنة
            $query->orWhere(function($q) use ($user, $student) {
                $q->where('target_role', 'student')
                  // إذا الإعلان مخصص لقسم، يجب أن يطابق قسم الطالب
                  ->where(function($sub) use ($user) {
                      $sub->whereNull('department_id')
                          ->orWhere('department_id', $user->department_id);
                  })
                  // إذا الإعلان مخصص لسنة، يجب أن يطابق سنة الطالب
                  ->where(function($sub) use ($user) {
                      $sub->whereNull('academic_year')
                          ->orWhere('academic_year', $user->academic_year);
                  });
            });

            // ج) إعلانات مخصصة لكورسات الطالب الحالية فقط
            if (!empty($enrolledCourseIds)) {
                $query->orWhereIn('course_id', $enrolledCourseIds);
            }

        })
        ->latest()
        ->take(5)
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->announcement_id,
                'type' => $item->type ?? 'general',
                'title' => $item->title ?? 'إعلان',
                'content' => $item->content ?? '',
                'time_ago' => $item->created_at ? $item->created_at->diffForHumans() : 'منذ قليل',
            ];
        });

        // 3. جلب المحاضرة القادمة
        $nextLecture = null;
        $today = now()->format('l'); // اسم اليوم بالعربية/الإنجليزية

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

        // 4. إحصائيات سريعة
        $totalCourses = $student ? $student->courses()->count() : 0;
        $totalAttendances = $student ? Attendance::where('student_id', $student->student_id)->count() : 0;
        $presentCount = $student ? Attendance::where('student_id', $student->student_id)->where('status', 'present')->count() : 0;
        $attendanceRate = $totalAttendances > 0 ? round(($presentCount / $totalAttendances) * 100, 1) : 0;

        return response()->json([
            'status' => true,
            'message' => 'تم جلب البيانات بنجاح',
            'data' => [
                'student' => [
                    'id' => $user->user_id,
                    'name' => $user->full_name,
                    'student_code' => $student->student_code ?? $user->university_id,
                    'level' => $student->level ?? 'غير محدد',
                    'phone' => $user->phone ?? 'غير متوفر',
                    'email' => $user->email ?? 'غير متوفر',
                    'department' => $user->department ?? 'غير محدد',
                    'academic_year' => $user->academic_year ?? 'غير محدد',
                    'birth_date' => $user->birth_date ? date('Y-m-d', strtotime($user->birth_date)) : null,
                    'gender' => $user->gender ?? 'غير محدد',
                    'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                ],
                'statistics' => [
                    'total_courses' => $totalCourses,
                    'attendance_rate' => $attendanceRate,
                    'total_assignments' => $student ? AssignmentSubmission::where('student_id', $student->student_id)->count() : 0,
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
            'status' => true,
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
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
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
            'status' => false,
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
        \Log::info("No avatar file received for user: " . $user->user_id);
    }

    // 🌟 4. تحديث قاعدة البيانات
    $user->update($dataToUpdate);

    return response()->json([
        'status' => true,
        'message' => 'تم تحديث الملف الشخصي بنجاح',
        'data' => [
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
        ]
    ], 200);
}
    /**
     * جلب الإشعارات الخاصة بالطالب
     */
    public function getNotifications(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->user_id)
            ->latest()
            ->get()
            ->map(function ($notify) {
                return [
                    'id' => $notify->id,
                    'title' => $notify->title,
                    'message' => $notify->message,
                    'type' => $notify->type,
                    'is_read' => (bool)$notify->is_read,
                    'created_at' => $notify->created_at ? $notify->created_at->format('Y-m-d H:i:s') : null,
                    'time_ago' => $notify->created_at ? $notify->created_at->diffForHumans() : 'منذ قليل',
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'تم جلب الإشعارات بنجاح',
            'data' => [
                'academic' => $notifications->whereIn('type', ['academic', 'marks', 'attendance', 'assignment'])->values(),
                'administrative' => $notifications->whereIn('type', ['administrative', 'general', 'announcement'])->values(),
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
                'status' => false,
                'message' => 'الإشعار غير موجود'
            ], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث حالة الإشعار'
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
            'status' => true,
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
                    $q->where('student_id', $student->student_id);
                });
            })
            ->with('course')
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day')
            ->map(function($items, $day) {
                return [
                    'day' => $day,
                    'lectures' => $items->map(function($item) {
                        return [
                            'course_name' => $item->course->title,
                            'start_time' => date('h:i A', strtotime($item->start_time)),
                            'end_time' => date('h:i A', strtotime($item->end_time)),
                            'room' => $item->room,
                        ];
                    })
                ];
            })->values();

        return response()->json([
            'status' => true,
            'data' => $schedules
        ], 200);
    }

    /**
     * جلب سجل حضور الطالب
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
                    'date' => $attendance->attendance_date->format('Y-m-d'),
                    'status' => $attendance->status,
                    'status_text' => $attendance->status == 'present' ? 'حاضر' : ($attendance->status == 'absent' ? 'غائب' : 'متأخر'),
                    'course_name' => $attendance->lesson->course->title ?? 'غير محدد',
                ];
            });

        // إحصائيات الحضور
        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $late = $attendances->where('status', 'late')->count();

        return response()->json([
            'status' => true,
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
            'status' => true,
            'overall_average' => round($overallAverage ?? 0, 1),
            'data' => $grades
        ], 200);
    }

    /**
     * جلب واجبات الطالب
     */
    public function getMyAssignments(Request $request)
    {
        $student = $request->user()->student;

        $submissions = AssignmentSubmission::where('student_id', $student->student_id)
            ->with(['assignment.course'])
            ->get()
            ->map(function($submission) {
                return [
                    'id' => $submission->submission_id,
                    'title' => $submission->assignment->title,
                    'course_name' => $submission->assignment->course->title,
                    'due_date' => $submission->assignment->due_date->format('Y-m-d H:i'),
                    'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i') : null,
                    'grade' => $submission->grade,
                    'max_points' => $submission->assignment->max_points,
                    'status' => $submission->grade ? 'graded' : ($submission->submitted_at ? 'submitted' : 'pending'),
                    'feedback' => $submission->feedback,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $submissions
        ], 200);
    }

    /**
     * تقديم واجب
     */
    public function submitAssignment(Request $request, $assignmentId)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx,zip|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $student = $request->user()->student;
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json([
                'status' => false,
                'message' => 'الواجب غير موجود'
            ], 404);
        }

        // رفع الملف
        $file = $request->file('file');
        $fileName = time() . '_' . $student->student_code . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('assignments/' . $assignmentId, $fileName, 'public');

        // إنشاء أو تحديث التسليم
        $submission = AssignmentSubmission::updateOrCreate(
            [
                'assignment_id' => $assignmentId,
                'student_id' => $student->student_id,
            ],
            [
                'file_path' => $filePath,
                'submitted_at' => now(),
                'grade' => null,
                'feedback' => null,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'تم تقديم الواجب بنجاح',
            'data' => $submission
        ], 200);
    }

    /**
     * طلب إذن غياب
     */
    public function requestAbsence(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|min:10|max:500',
            'document' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $student = $request->user()->student;

        // رفع المستند إذا وجد
        $documentPath = null;
        if ($request->hasFile('document')) {
            $document = $request->file('document');
            $documentPath = $document->store('absence_requests', 'public');
        }

        $absenceRequest = AbsenceRequest::create([
            'student_id' => $student->student_id,
            'date' => $request->date,
            'reason' => $request->reason,
            'document' => $documentPath,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال طلب الإذن بنجاح، سيتم مراجعته من قبل الإدارة',
            'data' => $absenceRequest
        ], 200);
    }

    /**
     * جلب طلبات الإذن الخاصة بالطالب
     */
    public function getMyAbsenceRequests(Request $request)
    {
        $student = $request->user()->student;

        $requests = AbsenceRequest::where('student_id', $student->student_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($req) {
                return [
                    'id' => $req->request_id,
                    'date' => $req->date->format('Y-m-d'),
                    'reason' => $req->reason,
                    'status' => $req->status,
                    'status_text' => $req->status == 'approved' ? 'مقبول' : ($req->status == 'rejected' ? 'مرفوض' : 'قيد المراجعة'),
                    'created_at' => $req->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $requests
        ], 200);
    }

    /**
     * جلب الروابط والمحاضرات المسجلة
     */
    public function getCourseMaterials(Request $request, $courseId)
    {
        $student = $request->user()->student;

        // التأكد أن الطالب مسجل في هذه الدورة
        $isEnrolled = $student->courses()->where('course_id', $courseId)->exists();

        if (!$isEnrolled) {
            return response()->json([
                'status' => false,
                'message' => 'غير مسموح لك بالوصول إلى هذه الدورة'
            ], 403);
        }

        $course = Course::with(['lessons', 'resources'])->find($courseId);

        return response()->json([
            'status' => true,
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
                    'file_url' => asset('storage/' . $resource->file_path),
                ];
            }),
        ], 200);
    }

    /**
     * ربط الطالب بولي الأمر (للاستخدام من تطبيق ولي الأمر)
     */

}
