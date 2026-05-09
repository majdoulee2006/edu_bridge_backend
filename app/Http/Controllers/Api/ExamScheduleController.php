<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamScheduleController extends Controller
{
    // GET /api/hod/exams?class_group=معلوماتية 1
    public function index(Request $request)
    {
        $query = DB::table('exams')
            ->join('courses', 'exams.course_id', '=', 'courses.course_id')
            ->select(
                'exams.exam_id',
                'exams.exam_name',
                'exams.exam_date',
                'exams.max_score',
                'exams.room',
                'exams.class_group',
                'courses.title as subject',
                'exams.course_id'
            );

        if ($request->has('class_group')) {
            $query->where('exams.class_group', $request->class_group);
        }

        $exams = $query->orderBy('exams.exam_date', 'asc')->get();

        return response()->json($exams);
    }

    // POST /api/hod/exams
    public function store(Request $request)
    {
        $request->validate([
            'course_id'   => 'required|exists:courses,course_id',
            'exam_name'   => 'required|string',
            'exam_date'   => 'required|date',
            'max_score'   => 'nullable|integer',
            'room'        => 'nullable|string',
            'class_group' => 'nullable|string',
        ]);

        $id = DB::table('exams')->insertGetId([
            'course_id'   => $request->course_id,
            'exam_name'   => $request->exam_name,
            'exam_date'   => $request->exam_date,
            'max_score'   => $request->max_score ?? 100,
            'room'        => $request->room,
            'class_group' => $request->class_group,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(['message' => 'تم إضافة الامتحان بنجاح', 'exam_id' => $id], 201);
    }

    // PUT /api/hod/exams/{id}
    public function update(Request $request, $id)
    {
        $exists = DB::table('exams')->where('exam_id', $id)->exists();
        if (!$exists) {
            return response()->json(['message' => 'الامتحان غير موجود'], 404);
        }

        DB::table('exams')->where('exam_id', $id)->update([
            'course_id'   => $request->course_id,
            'exam_name'   => $request->exam_name,
            'exam_date'   => $request->exam_date,
            'max_score'   => $request->max_score ?? 100,
            'room'        => $request->room,
            'class_group' => $request->class_group,
            'updated_at'  => now(),
        ]);

        return response()->json(['message' => 'تم تعديل الامتحان بنجاح']);
    }

    // DELETE /api/hod/exams/{id}
    public function destroy($id)
    {
        DB::table('exams')->where('exam_id', $id)->delete();
        return response()->json(['message' => 'تم حذف الامتحان بنجاح']);
    }
}
