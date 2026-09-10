<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCourseController extends Controller
{
    use \App\Traits\NormalizesAccountCredentialsTrait;
    public function courses()
    {
        $departments = DB::table('departments')->orderBy('name')->get();
        $departmentIds = $departments->pluck('department_id')->toArray();
        
        $heads = DB::table('heads')
            ->join('users', 'heads.user_id', '=', 'users.user_id')
            ->whereIn('heads.department_id', $departmentIds)
            ->select('heads.department_id', 'users.user_id', 'users.full_name')
            ->get()
            ->keyBy('department_id');

        $programCounts = DB::table('programs')
            ->whereIn('department_id', $departmentIds)
            ->select('department_id', DB::raw('count(*) as count'))
            ->groupBy('department_id')
            ->get()
            ->keyBy('department_id');

        foreach ($departments as $dept) {
            $head = $heads->get($dept->department_id);
            $dept->current_hod_name = $head ? $head->full_name : 'غير مخصص حالياً';
            $dept->current_hod_user_id = $head ? $head->user_id : null;
            $dept->courses_count = isset($programCounts[$dept->department_id]) ? $programCounts[$dept->department_id]->count : 0;
        }

        $programs = DB::table('programs')
            ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
            ->select('programs.*', 'departments.name as department_name')
            ->orderByRaw('CASE WHEN programs.department_id IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('programs.created_at')
            ->get();

        $programIds = $programs->pluck('id')->toArray();
        
        $allCoursesInPrograms = DB::table('course_program')
            ->join('courses', 'course_program.course_id', '=', 'courses.course_id')
            ->whereIn('course_program.program_id', $programIds)
            ->select('course_program.program_id', 'courses.title as course_name')
            ->get();

        $coursesByProgram = [];
        foreach ($allCoursesInPrograms as $cip) {
            $coursesByProgram[$cip->program_id][] = $cip;
        }

        foreach ($programs as $program) {
            $program->department_name = $program->department_name ?? 'غير مخصصة (دورة مستقلة)';
            $coursesInProgram = $coursesByProgram[$program->id] ?? [];

            $program->course_count = count($coursesInProgram);
            $program->total_hours = $program->course_count * 4; // estimate 4h per course
            $program->courses_list = $coursesInProgram;
        }

        return view('admin.courses', compact('programs', 'departments'));
    }

    public function createCourse()
    {
        $departments = DB::table('departments')->get();
        return view('admin.courses.create', compact('departments'));
    }

    public function storeDepartment(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $request->validate([
            'name'        => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'اسم القسم مطلوب.',
            'name.unique'   => 'هذا القسم موجود مسبقاً.'
        ]);

        $id = DB::table('departments')->insertGetId([
            'name'        => $request->name,
            'description' => $request->description,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->back()->with([
            'success' => 'تم إضافة القسم الجديد بنجاح!',
            'new_department_id' => $id,
            'new_department_name' => $request->name,
        ]);
    }

    public function updateDepartment(Request $request, $id)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:departments,name,' . $id . ',department_id',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'اسم القسم مطلوب.',
            'name.unique'   => 'هذا القسم موجود مسبقاً.'
        ]);

        DB::table('departments')->where('department_id', $id)->update([
            'name'        => $request->name,
            'description' => $request->description,
            'updated_at'  => now(),
        ]);

        return redirect()->route('admin.courses')->with('success', 'تم تعديل بيانات القسم بنجاح!');
    }

    public function deleteDepartment($id)
    {
        $dept = DB::table('departments')->where('department_id', $id)->first();
        if (!$dept) {
            return redirect()->back()->with('error', 'القسم غير موجود.');
        }

        DB::table('programs')->where('department_id', $id)->update(['department_id' => null]);
        DB::table('heads')->where('department_id', $id)->delete();
        DB::table('departments')->where('department_id', $id)->delete();

        return redirect()->route('admin.courses')->with('success', 'تم حذف قسم (' . $dept->name . ') بنجاح!');
    }

    public function storeCourse(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $request->validate([
            'name'          => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,department_id',
            'description'   => 'nullable|string',
            'duration'      => 'nullable|string|max:100',
            'start_date'    => 'nullable|date',
        ], [
            'name.required' => 'اسم الدورة مطلوب.',
        ]);

        $deptId = $request->filled('department_id') ? $request->department_id : null;

        // Prevent duplicate insertions caused by rapid multiple clicks or network lag (15-second window)
        $existingRecent = DB::table('programs')
            ->where('name', $request->name)
            ->where('department_id', $deptId)
            ->where('created_at', '>=', now()->subSeconds(15))
            ->first();

        if ($existingRecent) {
            return redirect()->route('admin.courses')->with('success', 'تم إضافة الدورة الجديدة بنجاح!');
        }

        DB::table('programs')->insert([
            'name'          => $request->name,
            'department_id' => $deptId,
            'description'   => $request->description,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.courses')->with('success', 'تم إضافة الدورة الجديدة بنجاح!');
    }

    public function deleteCourse($id)
    {
        DB::table('course_program')->where('program_id', $id)->delete();
        DB::table('programs')->where('id', $id)->delete();

        return redirect()->route('admin.courses')->with('success', 'تم حذف الدورة بنجاح.');
    }

    public function assignProgramsToDepartment(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,department_id',
            'program_ids'   => 'required|array',
            'program_ids.*' => 'exists:programs,id',
        ], [
            'department_id.required' => 'يرجى تحديد القسم الهدف.',
            'program_ids.required'   => 'يرجى اختيار دورة واحدة على الأقل لتخصيصها.',
        ]);

        DB::table('programs')
            ->whereIn('id', $request->program_ids)
            ->update([
                'department_id' => $request->department_id,
                'updated_at'    => now(),
            ]);

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();

        return redirect()->route('admin.courses', ['department_id' => $request->department_id])
            ->with('success', 'تم نقل وتخصيص الدورات المختارة لقسم (' . ($dept ? $dept->name : '') . ') بنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  ASSIGN HEAD OF DEPARTMENT
    // ────────────────────────────────────────────────────────────

    public function assignHODForm()
    {
        $departments = DB::table('departments')->get();
        foreach ($departments as $dept) {
            $head = DB::table('heads')
                ->join('users', 'heads.user_id', '=', 'users.user_id')
                ->where('heads.department_id', $dept->department_id)
                ->select('users.user_id', 'users.full_name', 'users.email', 'users.phone')
                ->first();

            $dept->current_hod_name = $head ? $head->full_name : 'غير مخصص حالياً';
            $dept->current_hod_user_id = $head ? $head->user_id : null;
        }

        // Get users who could be HODs (teachers and existing HODs) with exact specialization & dept names
        $availableUsers = DB::table('users')
            ->leftJoin('teachers', 'users.user_id', '=', 'teachers.user_id')
            ->leftJoin('heads', 'users.user_id', '=', 'heads.user_id')
            ->leftJoin('departments', 'heads.department_id', '=', 'departments.department_id')
            ->whereIn('users.role_id', [2, 5]) // teachers (2) and HODs (5)
            ->where('users.status', 'active')
            ->select(
                'users.user_id',
                'users.full_name',
                'users.email',
                'users.phone',
                'users.role_id',
                'users.department as user_dept',
                'teachers.specialization',
                'departments.name as head_dept_name'
            )
            ->get();

        foreach ($availableUsers as $u) {
            if ($u->role_id == 5) {
                $u->department = $u->head_dept_name ?? $u->user_dept ?? 'قسم أكاديمي';
                $u->role_title = 'رئيس قسم (' . $u->department . ')';
            } else {
                $u->department = $u->specialization ?? $u->user_dept ?? '';
                $u->role_title = 'مدرب / مدرس أكاديمي' . ($u->specialization ? ' (تخصص: ' . $u->specialization . ')' : '');
            }
        }

        return view('admin.courses.assign_hod', compact('departments', 'availableUsers'));
    }

    public function assignHOD(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,department_id',
            'user_id'       => 'required|exists:users,user_id',
        ], [
            'department_id.required' => 'يرجى اختيار القسم.',
            'user_id.required'       => 'يرجى اختيار رئيس القسم.',
        ]);

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();
        $user = DB::table('users')->where('user_id', $request->user_id)->first();

        // Remove old HOD for this department if any
        $oldHead = DB::table('heads')->where('department_id', $request->department_id)->first();
        if ($oldHead) {
            // Reset old HOD's role back to teacher
            DB::table('users')->where('user_id', $oldHead->user_id)->update([
                'role_id'    => 2,
                'department' => null,
                'updated_at' => now(),
            ]);
            DB::table('heads')->where('department_id', $request->department_id)->delete();
        }

        // Update the selected user's role to HOD
        DB::table('users')->where('user_id', $request->user_id)->update([
            'role_id'    => 5,
            'department' => $dept->name,
            'updated_at' => now(),
        ]);

        // Insert into heads table
        DB::table('heads')->insert([
            'user_id'       => $request->user_id,
            'department_id' => $request->department_id,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Send notification
        DB::table('notifications')->insert([
            'user_id'    => $request->user_id,
            'title'      => 'تعيين رئيس قسم',
            'message'    => 'تم تعيينك رئيساً لقسم ' . $dept->name . '. مبارك!',
            'type'       => 'system',
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\FcmService::sendToUser(
            $request->user_id,
            'تعيين رئيس قسم',
            'تم تعيينك رئيساً لقسم ' . $dept->name . '. مبارك!',
            ['type' => 'system']
        );

        return redirect()->route('admin.courses', ['department_id' => $request->department_id])->with('success', 'تم تعيين ' . $user->full_name . ' رئيساً لقسم ' . $dept->name . ' بنجاح! يمكنك الآن إضافة وتخصيص الدورات لهذا القسم.');
    }

    public function storeNewHOD(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }
        $request->merge(['full_name' => $fullName]);

        $request->validate([
            'department_id' => 'required|exists:departments,department_id',
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'phone'         => 'required|string|max:20',
            'email'         => 'required|email|unique:users,email|max:255',
            'username'      => 'required|string|unique:users,username|max:255',
            'password'      => 'required|string|min:6|confirmed',
        ], [
            'department_id.required' => 'يرجى اختيار القسم.',
            'first_name.required'    => 'الاسم الأول مطلوب.',
            'last_name.required'     => 'الاسم الثاني مطلوب.',
            'phone.required'         => 'رقم الهاتف مطلوب.',
            'email.required'         => 'البريد الإلكتروني مطلوب.',
            'email.unique'           => 'البريد الإلكتروني مستخدم بالفعل.',
            'username.required'      => 'اسم المستخدم مطلوب.',
            'username.unique'        => 'اسم المستخدم مستخدم بالفعل.',
            'password.required'      => 'كلمة المرور مطلوبة.',
            'password.confirmed'     => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();

        // Remove old HOD for this department if exists
        $oldHead = DB::table('heads')->where('department_id', $request->department_id)->first();
        if ($oldHead) {
            DB::table('users')->where('user_id', $oldHead->user_id)->update([
                'role_id'    => 2, // revert to teacher
                'department' => null,
                'updated_at' => now(),
            ]);
            DB::table('heads')->where('department_id', $request->department_id)->delete();
        }

        // Create new HOD user
        $userId = DB::table('users')->insertGetId([
            'role_id'    => 5, // HOD
            'full_name'  => $fullName,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'username'   => $request->username,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'password'   => bcrypt($request->password),
            'department' => $dept->name,
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert into heads table
        DB::table('heads')->insert([
            'user_id'       => $userId,
            'department_id' => $request->department_id,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.courses', ['department_id' => $request->department_id])->with('success', 'تم إنشاء حساب رئيس القسم الجديد (' . $fullName . ') لقسم ' . $dept->name . ' وتعيينه بنجاح! يمكنك الآن إضافة وتخصيص الدورات لهذا القسم.');
    }

    public function unassignHOD(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,department_id',
        ]);

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();
        $oldHead = DB::table('heads')->where('department_id', $request->department_id)->first();

        if ($oldHead) {
            DB::table('users')->where('user_id', $oldHead->user_id)->update([
                'role_id'    => 2, // Revert to teacher
                'department' => null,
                'updated_at' => now(),
            ]);
            DB::table('heads')->where('department_id', $request->department_id)->delete();
        }

        return redirect()->route('admin.courses.assign-hod')
            ->with('success', 'تم إبطال وإلغاء تعيين رئيس القسم لقسم (' . ($dept ? $dept->name : '') . ') بنجاح.');
    }
}
