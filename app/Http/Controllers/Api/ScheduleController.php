<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    // GET /api/hod/schedules?class_group=معلوماتية 1
    public function index(Request $request)
    {
        $query = DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
            ->leftJoin('teachers', 'schedules.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.user_id')
            ->select(
                'schedules.schedule_id',
                'schedules.day',
                'schedules.start_time',
                'schedules.end_time',
                'schedules.room',
                'schedules.class_group',
                'courses.title as subject',
                'users.full_name as teacher_name',
                'schedules.teacher_id',
                'schedules.course_id'
            );

        // فلترة حسب الشعبة إذا مُرِّرت
        if ($request->has('class_group')) {
            $query->where('schedules.class_group', $request->class_group);
        }

        $schedules = $query->orderByRaw("FIELD(schedules.day, 'الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس')")
                           ->get();

        return response()->json($schedules);
    }

    // POST /api/hod/schedules
    public function store(Request $request)
    {
        $request->validate([
            'course_id'   => 'required|exists:courses,course_id',
            'day'         => 'required|string',
            'start_time'  => 'required',
            'end_time'    => 'required',
            'room'        => 'nullable|string',
            'teacher_id'  => 'nullable|exists:teachers,teacher_id',
            'class_group' => 'nullable|string',
        ]);

        $id = DB::table('schedules')->insertGetId([
            'course_id'   => $request->course_id,
            'day'         => $request->day,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'room'        => $request->room,
            'teacher_id'  => $request->teacher_id,
            'class_group' => $request->class_group,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(['message' => 'تم إنشاء الجدول بنجاح', 'schedule_id' => $id], 201);
    }

    // PUT /api/hod/schedules/{id}
    public function update(Request $request, $id)
    {
        $exists = DB::table('schedules')->where('schedule_id', $id)->exists();
        if (!$exists) {
            return response()->json(['message' => 'الجدول غير موجود'], 404);
        }

        DB::table('schedules')->where('schedule_id', $id)->update([
            'course_id'   => $request->course_id,
            'day'         => $request->day,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'room'        => $request->room,
            'teacher_id'  => $request->teacher_id,
            'class_group' => $request->class_group,
            'updated_at'  => now(),
        ]);

        return response()->json(['message' => 'تم تعديل الجدول بنجاح']);
    }

    // DELETE /api/hod/schedules/{id}
    public function destroy($id)
    {
        DB::table('schedules')->where('schedule_id', $id)->delete();
        return response()->json(['message' => 'تم حذف الجدول بنجاح']);
    }
}
