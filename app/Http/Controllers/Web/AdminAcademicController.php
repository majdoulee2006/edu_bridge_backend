<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAcademicController extends Controller
{
    use \App\Traits\NormalizesAccountCredentialsTrait;
    public function semestersSubjects(Request $request)
    {
        $departments = DB::table('departments')->get();
        $semesters   = DB::table('semesters')->orderByDesc('start_date')->get();

        // السنوات الأكاديمية: من عمود year في المواد (1 = السنة الأولى، 2 = السنة الثانية...)
        $academicYears = DB::table('courses')
            ->whereNotNull('year')->where('year', '!=', '')
            ->distinct()->orderBy('year')->pluck('year');

        // Fetch programs with their associated department names
        $programs = DB::table('programs')
            ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
            ->select('programs.*', 'departments.name as department_name')
            ->get();

        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id', 'users.full_name')
            ->get();

        // Default filters
        $selectedDept     = $request->get('department_id');
        $selectedProgram  = $request->get('program_id');
        $selectedSemester = $request->get('semester_id');
        $selectedYear     = $request->get('year');

        // Build subjects query
        $coursesQuery = DB::table('courses')
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
            $courseIds = DB::table('course_program')
                ->where('program_id', $selectedProgram)
                ->pluck('course_id');
            $coursesQuery->whereIn('courses.course_id', $courseIds);
        } elseif ($selectedDept) {
            $programIds = DB::table('programs')
                ->where('department_id', $selectedDept)
                ->pluck('id');
            $courseIds = DB::table('course_program')
                ->whereIn('program_id', $programIds)
                ->pluck('course_id');
            $coursesQuery->whereIn('courses.course_id', $courseIds);
        }

        $courses = $coursesQuery->get();

        foreach ($courses as $course) {
            $lessons = DB::table('lessons')
                ->where('course_id', $course->course_id)
                ->select('lesson_id', 'title', 'description', 'file_path', 'file_name', 'file_type', 'content_url', 'created_at')
                ->get();
            $course->lessons_list = $lessons;

            $semInfo = DB::table('semesters')
                ->where('semester_id', $course->semester_id)
                ->first();
            $course->semester_name = $semInfo ? $semInfo->name : 'غير محدد';

            $coursePrograms = DB::table('course_program')
                ->join('programs', 'course_program.program_id', '=', 'programs.id')
                ->where('course_program.course_id', $course->course_id)
                ->select('programs.department_id', 'programs.id as program_id')
                ->get();

            $course->program_id = $coursePrograms->first()->program_id ?? null;

            $deptIds     = $coursePrograms->pluck('department_id')->unique();
            $courseDepts = DB::table('departments')
                ->whereIn('department_id', $deptIds)
                ->pluck('name');
            $course->departments_list = $courseDepts;
        }

        return view('admin.semesters_subjects', compact(
            'departments', 'semesters', 'programs', 'courses', 'teachers',
            'selectedDept', 'selectedProgram', 'selectedSemester',
            'selectedYear', 'academicYears'
        ));
    }

    // ────────────────────────────────────────────────────────────
    //  LECTURES (محاضرات المعلمين)
    // ────────────────────────────────────────────────────────────
    public function lectures(Request $request)
    {
        $selectedDept    = $request->query('department_id');
        $selectedProgram = $request->query('program_id');
        $selectedYear    = $request->query('year');
        $selectedCourse  = $request->query('course_id');

        $departments = DB::table('departments')->get();

        $programsQuery = DB::table('programs');
        if ($selectedDept) {
            $programsQuery->where('department_id', $selectedDept);
        }
        $programs = $programsQuery->get();

        $coursesQuery = DB::table('courses');
        if ($selectedYear) {
            $coursesQuery->where('year', $selectedYear);
        }
        if ($selectedProgram) {
            $cIds = DB::table('course_program')->where('program_id', $selectedProgram)->pluck('course_id');
            $coursesQuery->whereIn('course_id', $cIds);
        } elseif ($selectedDept) {
            $pIds = DB::table('programs')->where('department_id', $selectedDept)->pluck('id');
            $cIds = DB::table('course_program')->whereIn('program_id', $pIds)->pluck('course_id');
            $coursesQuery->whereIn('course_id', $cIds);
        }
        $courses = $coursesQuery->get();

        foreach ($courses as $c) {
            $progs = DB::table('course_program')
                ->join('programs', 'course_program.program_id', '=', 'programs.id')
                ->where('course_program.course_id', $c->course_id)
                ->select('programs.id as program_id', 'programs.department_id')
                ->get();

            $c->program_ids = $progs->pluck('program_id')->toArray();
            $c->department_ids = $progs->pluck('department_id')->unique()->toArray();

            // Teachers assigned to this course
            $teacherNames = DB::table('course_teachers')
                ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->where('course_teachers.course_id', $c->course_id)
                ->pluck('users.full_name')
                ->toArray();

            $c->teacher_names = implode('، ', $teacherNames);
        }

        $query = DB::table('lessons')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->join('teachers', 'lessons.teacher_id', '=', 'teachers.teacher_id')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->where(function($q) {
                $q->whereNull('lessons.type')
                  ->orWhere('lessons.type', '!=', 'session');
            })
            ->where('lessons.title', 'not like', '%حضور%')
            ->where('lessons.title', 'not like', '%غياب%')
            ->where('lessons.title', 'not like', '%تفقد%')
            ->where('lessons.title', 'not like', '%حصة%')
            ->where('lessons.title', 'not like', '%جلسة%')
            ->where(function($q) {
                $q->whereNull('lessons.content_url')
                  ->orWhere('lessons.content_url', 'not like', '%attendance%');
            })
            ->where(function($q) {
                $q->where(function($q2) {
                    $q2->whereNotNull('lessons.file_path')
                       ->where('lessons.file_path', '!=', '');
                })->orWhere(function($q2) {
                    $q2->whereNotNull('lessons.content_url')
                       ->where('lessons.content_url', '!=', '');
                });
            });

        if ($selectedCourse) {
            $query->where('lessons.course_id', $selectedCourse);
        } else {
            if ($selectedYear) {
                $query->where('courses.year', $selectedYear);
            }

            if ($selectedProgram) {
                $courseIds = DB::table('course_program')
                    ->where('program_id', $selectedProgram)
                    ->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            } elseif ($selectedDept) {
                $progIds = DB::table('programs')
                    ->where('department_id', $selectedDept)
                    ->pluck('id');
                $courseIds = DB::table('course_program')
                    ->whereIn('program_id', $progIds)
                    ->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            }
        }

        $lectures = $query->select(
            'lessons.*',
            'courses.title as course_title',
            'courses.year as course_year',
            'users.full_name as teacher_name'
        )
        ->orderByDesc('lessons.created_at')
        ->get();

        // Get teacher info for selected course or filtered view
        $assignedTeachers = [];
        if ($selectedCourse) {
            $assignedTeachers = DB::table('course_teachers')
                ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->where('course_teachers.course_id', $selectedCourse)
                ->pluck('users.full_name')
                ->toArray();
        }

        return view('admin.lectures', compact(
            'lectures', 'courses', 'departments', 'programs',
            'selectedDept', 'selectedProgram', 'selectedYear', 'selectedCourse',
            'assignedTeachers'
        ));
    }

    public function storeSubject(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'level'       => 'nullable|string',
            'year'        => 'required|integer|in:1,2',
            'semester_id' => 'required|integer',
            'program_id'  => 'required|integer',
            'hours'       => 'required|integer|min:1',
            'weight'      => 'required|integer|min:1',
        ]);

        // Prevent duplicate insertions caused by rapid multiple clicks or network lag (15-second window)
        $existingRecent = DB::table('courses')
            ->where('title', $request->title)
            ->where('semester_id', $request->semester_id)
            ->where('year', $request->year)
            ->where('created_at', '>=', now()->subSeconds(15))
            ->first();

        if ($existingRecent) {
            return back()->with('success', 'تم إضافة المادة بنجاح!');
        }

        // حفظ المادة مع السنة
        $courseId = DB::table('courses')->insertGetId([
            'title'       => $request->title,
            'description' => $request->description,
            'level'       => $request->level ?? 'عام',
            'year'        => $request->year,
            'semester_id' => $request->semester_id,
            'hours'       => $request->hours,
            'weight'      => $request->weight,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // ربط الدورة (البرنامج)
        DB::table('course_program')->insert([
            'course_id'  => $courseId,
            'program_id' => $request->program_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // تسجيل تلقائي لكل الطلاب اللي في نفس الدورة والسنة
        $levelLabel = $request->year == 1 ? 'السنة الأولى' : 'السنة الثانية';
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.program_id', $request->program_id)
            ->where(function($q) use ($request, $levelLabel) {
                $q->where('students.level', (string) $request->year)
                  ->orWhere('students.level', $levelLabel)
                  ->orWhere('users.academic_year', (string) $request->year)
                  ->orWhere('users.academic_year', $levelLabel);
            })
            ->pluck('students.student_id');

        foreach ($students as $studentId) {
            $exists = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->exists();
            if (!$exists) {
                DB::table('enrollments')->insert([
                    'student_id'      => $studentId,
                    'course_id'       => $courseId,
                    'enrollment_date' => now()->toDateString(),
                    'status'          => 'active',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        return back()->with('success', 'تم إضافة المادة وتسجيل ' . $students->count() . ' طالب تلقائياً!');
    }

    public function updateSubject(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'level'       => 'nullable|string',
            'year'        => 'required|integer|in:1,2',
            'semester_id' => 'required|integer',
            'program_id'  => 'required|integer',
            'hours'       => 'required|integer|min:1',
            'weight'      => 'required|integer|min:1',
        ]);

        DB::table('courses')->where('course_id', $id)->update([
            'title'       => $request->title,
            'description' => $request->description,
            'level'       => $request->level ?? 'عام',
            'year'        => $request->year,
            'semester_id' => $request->semester_id,
            'hours'       => $request->hours,
            'weight'      => $request->weight,
            'updated_at'  => now(),
        ]);

        DB::table('course_program')->where('course_id', $id)->update([
            'program_id' => $request->program_id,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'تم تحديث المادة بنجاح!');
    }

    public function deleteSubject($id)
    {
        DB::table('courses')->where('course_id', $id)->delete();
        return back()->with('success', 'تم حذف المادة بنجاح!');
    }
}
