<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * عرض صورة الوجه المرفقة بسجل حضور، لمن يحق له فقط:
 * الأدمن والشؤون، معلّم الطالب، رئيس قسم الطالب، والطالب نفسه.
 */
class FaceImageController extends Controller
{
    public function show(Request $request, $attendanceId)
    {
        $user = $request->user();
        $row  = DB::table('attendance')->where('attendance_id', $attendanceId)->first();

        if (!$row || empty($row->face_image)) {
            return response()->json(['success' => false, 'message' => 'لا توجد صورة'], 404);
        }

        if (!$this->canView($user, $row->student_id)) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بعرض هذه الصورة'], 403);
        }

        $headers = ['Cache-Control' => 'private, no-store'];

        // صور جديدة في التخزين الخاص
        if (str_starts_with($row->face_image, 'faces/') && Storage::disk('local')->exists($row->face_image)) {
            return Storage::disk('local')->response($row->face_image, null, $headers);
        }

        // صور قديمة كانت تُحفظ في المجلد العام: تُخدَّم عبر هذا المسار فقط
        if (str_starts_with($row->face_image, 'uploads/faces/')) {
            $legacy = realpath(public_path($row->face_image));
            $base   = realpath(public_path('uploads/faces'));
            if ($legacy && $base && str_starts_with($legacy, $base . DIRECTORY_SEPARATOR) && is_file($legacy)) {
                return response()->file($legacy, $headers);
            }
        }

        return response()->json(['success' => false, 'message' => 'الملف غير موجود'], 404);
    }

    private function canView($user, $studentId): bool
    {
        switch ((int) $user->role_id) {
            case 1:
            case 6:
                return true;
            case 2:
                $teacher = $user->teacher;
                return $teacher && Access::teacherTeachesStudent($teacher->teacher_id, $studentId);
            case 5:
                return Access::headManagesStudent($user, $studentId);
            case 3:
                return (int) DB::table('students')->where('student_id', $studentId)->value('user_id') === (int) $user->user_id;
            default:
                return false;
        }
    }
}
