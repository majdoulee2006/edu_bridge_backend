<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * بحث عن طالب بالرقم الجامعي أو كود الطالب (يُستخدم عند ربط ولي أمر بابنه من لوحات الأدمن/الشؤون/رئيس القسم).
 * الأدمن والشؤون: كل الطلاب. رئيس القسم: طلاب قسمه فقط.
 */
class StudentLookupController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array((int) $user->role_id, [1, 5, 6], true)) {
            abort(403);
        }

        $uid = trim((string) $request->query('uid'));
        if (mb_strlen($uid) < 3) {
            return response()->json((object) []);
        }

        $row = DB::table('users')
            ->join('students', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
            ->where('users.role_id', 3)
            ->where(function ($q) use ($uid) {
                $q->where('users.university_id', $uid)->orWhere('students.student_code', $uid);
            })
            ->select(
                'students.student_id', 'users.user_id', 'users.full_name', 'students.level',
                DB::raw("COALESCE(departments.name, users.department, '') as department")
            )
            ->first();

        // رئيس القسم يرى طلاب قسمه فقط؛ غير ذلك يُعامَل كأنه غير موجود (لا تسريب لوجوده)
        if (!$row || ((int) $user->role_id === 5 && !Access::headManagesUser($user, $row->user_id))) {
            return response()->json((object) []);
        }

        return response()->json([
            'full_name'  => $row->full_name,
            'department' => $row->department,
            'level'      => $row->level,
        ]);
    }
}
