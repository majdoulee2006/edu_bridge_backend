<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Admin;

class AdminWebController extends Controller
{
    use \App\Traits\NormalizesAccountCredentialsTrait;

    // ────────────────────────────────────────────────────────────
    //  ACCOUNTS MANAGEMENT
    // ────────────────────────────────────────────────────────────

    public function accounts(Request $request)
    {
        $roleFilter = $request->input('role', 'all');
        $search = trim($request->input('search', ''));

        $query = DB::table('users')
            ->select(['user_id', 'role_id', 'full_name', 'username', 'email', 'phone', 'university_id', 'status', 'created_at'])
            ->where('role_id', '!=', 1); // Exclude admin self

        if ($roleFilter !== 'all') {
            $roleMap = [
                'student' => 3,
                'teacher' => 2,
                'hod'     => 5,
                'parent'  => 4,
                'affairs' => 6,
            ];
            if (isset($roleMap[$roleFilter])) {
                $query->where('role_id', $roleMap[$roleFilter]);
            }
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('username', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('university_id', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(24)->withQueryString();

        $counts = [
            'all'     => DB::table('users')->where('role_id', '!=', 1)->count(),
            'student' => DB::table('users')->where('role_id', 3)->count(),
            'teacher' => DB::table('users')->where('role_id', 2)->count(),
            'hod'     => DB::table('users')->where('role_id', 5)->count(),
            'parent'  => DB::table('users')->where('role_id', 4)->count(),
            'affairs' => DB::table('users')->where('role_id', 6)->count(),
        ];

        $pendingUsers = DB::table('users')
            ->where('status', 'inactive')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.accounts', compact('users', 'roleFilter', 'search', 'counts', 'pendingUsers'));
    }

    public function deleteSingleAccount($id)
    {
        $usr = DB::table('users')->where('user_id', $id)->first();
        if (!$usr) {
            return back()->with('error', 'الحساب غير موجود.');
        }

        $roleId = intval($usr->role_id);

        if ($roleId == 3) {
            $student = DB::table('students')->where('user_id', $id)->first();
            if ($student) {
                // parent_students.student_id هو FK على users.user_id، وليس students.student_id
                DB::table('parent_students')->where('student_id', $id)->delete();
                DB::table('enrollments')->where('student_id', $student->student_id)->delete();
                DB::table('students')->where('student_id', $student->student_id)->delete();
            }
        } elseif ($roleId == 2) {
            $teacher = DB::table('teachers')->where('user_id', $id)->first();
            if ($teacher) {
                DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->delete();
                DB::table('teachers')->where('teacher_id', $teacher->teacher_id)->delete();
            }
        } elseif ($roleId == 5) {
            DB::table('heads')->where('user_id', $id)->delete();
        } elseif ($roleId == 4) {
            $parent = DB::table('parents')->where('user_id', $id)->first();
            if ($parent) {
                // parent_students.parent_id هو FK على users.user_id، وليس parents.parent_id
                DB::table('parent_students')->where('parent_id', $id)->delete();
                DB::table('parents')->where('parent_id', $parent->parent_id)->delete();
            }
        }

        DB::table('users')->where('user_id', $id)->delete();

        \App\Models\UserActivity::log('حذف حساب', "قامت الإدارة بحذف حساب: {$usr->full_name} ({$usr->username})");

        return back()->with('success', "تم حذف حساب ({$usr->full_name}) بنجاح!");
    }

    public function deleteAllByRole(Request $request, $role)
    {
        $roleMap = ['student' => 3, 'parent' => 4];
        if (!isset($roleMap[$role])) {
            return back()->with('error', 'إجراء غير مصرح به لهذه الفئة.');
        }

        // حماية إضافية: يجب على الأدمن كتابة العدد الحالي للحسابات بالضبط لتأكيد الحذف الجماعي النهائي
        $actualCount = DB::table('users')->where('role_id', $roleMap[$role])->count();
        if ($actualCount === 0) {
            return back()->with('error', 'لا توجد حسابات لحذفها.');
        }
        if ((int) $request->input('confirm_count') !== $actualCount) {
            return back()->with('error', 'فشل التأكيد: العدد المُدخل لا يطابق عدد الحسابات الحالي (' . $actualCount . '). لم يتم حذف أي شيء.');
        }

        DB::beginTransaction();
        try {
            if ($role === 'student') {
                $studentUsers = DB::table('users')->where('role_id', 3)->pluck('user_id')->toArray();
                $studentIds = DB::table('students')->whereIn('user_id', $studentUsers)->pluck('student_id')->toArray();

                if (!empty($studentIds)) {
                    // parent_students.student_id هو FK على users.user_id، وليس students.student_id
                    DB::table('parent_students')->whereIn('student_id', $studentUsers)->delete();
                    DB::table('enrollments')->whereIn('student_id', $studentIds)->delete();

                    if (\Illuminate\Support\Facades\Schema::hasTable('grades')) {
                        DB::table('grades')->whereIn('student_id', $studentIds)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('attendance')) {
                        DB::table('attendance')->whereIn('student_id', $studentIds)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('student_warnings')) {
                        DB::table('student_warnings')->whereIn('student_id', $studentIds)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('assignment_submissions')) {
                        DB::table('assignment_submissions')->whereIn('student_id', $studentIds)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('reports')) {
                        DB::table('reports')->whereIn('student_id', $studentIds)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('requests')) {
                        DB::table('requests')->whereIn('student_id', $studentIds)->delete();
                    }

                    DB::table('students')->whereIn('student_id', $studentIds)->delete();
                }

                if (!empty($studentUsers)) {
                    if (\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                        DB::table('notifications')->whereIn('user_id', $studentUsers)->delete();
                    }
                    DB::table('users')->whereIn('user_id', $studentUsers)->delete();
                }

                \App\Models\UserActivity::log('حذف جماعي', "قامت الإدارة بحذف جميع حسابات الطلاب (" . count($studentUsers) . " طالب)");
                DB::commit();

                return redirect()->route('admin.accounts', ['role' => 'student'])->with('success', 'تم حذف جميع حسابات الطلاب بنجاح!');

            } elseif ($role === 'parent') {
                $parentUsers = DB::table('users')->where('role_id', 4)->pluck('user_id')->toArray();
                $parentIds = DB::table('parents')->whereIn('user_id', $parentUsers)->pluck('parent_id')->toArray();

                if (!empty($parentIds)) {
                    // parent_students.parent_id هو FK على users.user_id، وليس parents.parent_id
                    DB::table('parent_students')->whereIn('parent_id', $parentUsers)->delete();
                    DB::table('parents')->whereIn('parent_id', $parentIds)->delete();
                }

                if (!empty($parentUsers)) {
                    if (\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                        DB::table('notifications')->whereIn('user_id', $parentUsers)->delete();
                    }
                    DB::table('users')->whereIn('user_id', $parentUsers)->delete();
                }

                \App\Models\UserActivity::log('حذف جماعي', "قامت الإدارة بحذف جميع حسابات أولياء الأمور (" . count($parentUsers) . " ولي أمر)");
                DB::commit();

                return redirect()->route('admin.accounts', ['role' => 'parent'])->with('success', 'تم حذف جميع حسابات أولياء الأمور بنجاح!');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('deleteAllByRole error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء الحذف، تم التراجع عن كافة التغييرات: ' . $e->getMessage());
        }
    }

    public function approveAccount($id)
    {
        DB::table('users')
            ->where('user_id', $id)
            ->update(['status' => 'active', 'updated_at' => now()]);

        $user = DB::table('users')->where('user_id', $id)->first();

        // ---- تسجيل الطالب تلقائياً بكافة مواد وقسم برنامجه عند الموافقة ----
        if ($user && $user->role_id == 3) {
            $student = \App\Models\Student::where('user_id', $user->user_id)->first();
            if ($student) {
                $existingEnrollments = \DB::table('enrollments')->where('student_id', $student->student_id)->count();
                if ($existingEnrollments === 0) {
                    $branch = $user->branch ?? $user->department;
                    $program = null;
                    if ($branch) {
                        $program = \DB::table('programs')
                            ->where('name', 'LIKE', '%' . $branch . '%')
                            ->first();
                    }
                    if (!$program && $user->department) {
                        $program = \DB::table('programs')
                            ->where('name', 'LIKE', '%' . $user->department . '%')
                            ->first();
                    }

                    $courseIds = collect();
                    if ($program) {
                        $student->update(['program_id' => $program->id]);
                        $courseIds = \DB::table('course_program')
                            ->where('program_id', $program->id)
                            ->pluck('course_id');
                    }

                    if ($courseIds->isEmpty()) {
                        $courseIds = \DB::table('courses')->pluck('course_id');
                    }

                    foreach ($courseIds as $courseId) {
                        \DB::table('enrollments')->insertOrIgnore([
                            'student_id'      => $student->student_id,
                            'course_id'       => $courseId,
                            'status'          => 'active',
                            'enrollment_date' => now(),
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }
                \App\Models\Student::autoAssignAdvisor($student->student_id);
            }
        }

        // ---- إضافة ربط الأبناء بولي الأمر عند الموافقة ----
        if ($user && $user->role_id == 4 && !empty($user->children_ids)) {
            $childrenIds = is_string($user->children_ids) ? json_decode($user->children_ids, true) : $user->children_ids;
            if (is_array($childrenIds)) {
                $parent = DB::table('parents')->where('user_id', $id)->first();
                if ($parent) {
                    foreach ($childrenIds as $universityId) {
                        $childStudent = DB::table('students')
                            ->where('student_code', $universityId)
                            ->first();
                        if ($childStudent) {
                            $childUser = \App\Models\User::where('user_id', $childStudent->user_id)->first();
                            DB::table('parent_students')->insertOrIgnore([
                                'parent_id'    => $user->user_id,
                                'student_id'   => $childUser ? $childUser->user_id : $childStudent->user_id,
                                'relationship' => 'father',
                                'created_at'   => now(),
                                'updated_at'   => now(),
                            ]);
                        }
                    }
                }
            }
        }
        // ---------------------------------------------------

        // Add welcome notification
        DB::table('notifications')->insert([
            'user_id'    => $id,
            'title'      => 'تم تفعيل الحساب',
            'message'    => 'تهانينا! قامت الإدارة بتفعيل حسابك بنجاح. يمكنك الآن استخدام كافة الميزات والوصول لموادك.',
            'type'       => 'system',
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\FcmService::sendToUser(
            $id,
            'تم تفعيل الحساب',
            'تهانينا! قامت الإدارة بتفعيل حسابك بنجاح. يمكنك الآن استخدام كافة الميزات والوصول لموادك.',
            ['type' => 'system']
        );

        // ── إرسال إشعار تليجرام للموافقة والتفعيل عبر البوت ──
        if ($user) {
            $telegramChatId = $user->telegram_chat_id ?? '7821980919';
            try {
                $telegram = new \App\Services\TelegramService();
                $idText = $user->university_id ?? $user->username ?? '';
                $text = "🎓 <b>تفعيل الحساب - Edu Bridge</b>\n\n"
                      . "مرحباً <b>{$user->full_name}</b>،\n\n"
                      . "🎉 لقد تم <b>الموافقة وتفعيل حسابك بنجاح</b> من قِبل إدارة المعهد!\n"
                      . "📚 تم تسجيل ونزول كافة موادك ومحاضراتك الأكاديمية بنجاح.\n\n"
                      . ($idText ? "🆔 <b>الرقم الجامعي / اسم المستخدم:</b> <code>{$idText}</code>\n\n" : "\n")
                      . "📲 يمكنك الآن فتح التطبيق وتسجيل الدخول مباشرة للوصول إلى كافة الخدمات والمواد.";
                $telegram->sendMessage((int) $telegramChatId, $text);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Telegram admin approveAccount notification error: ' . $e->getMessage());
            }

            \App\Models\UserActivity::log('قبول حساب', "قامت الإدارة بالموافقة على حساب: {$user->full_name} ({$user->email})");
        }

        return redirect()->back()->with('success', 'تم قبول وتفعيل حساب المستخدم بنجاح وإرسال رسالة التليجرام!');
    }

    public function rejectAccount($id)
    {
        // Fetch user first to see their role
        $usr = DB::table('users')->where('user_id', $id)->first();
        if ($usr) {
            \App\Models\UserActivity::log('رفض حساب', "قامت الإدارة برفض وحذف طلب حساب: {$usr->full_name} ({$usr->email})");
            // Delete dynamic children mapping or role table details
            if ($usr->role_id == 3) {
                DB::table('students')->where('user_id', $id)->delete();
            } elseif ($usr->role_id == 2) {
                DB::table('teachers')->where('user_id', $id)->delete();
            } elseif ($usr->role_id == 5) {
                DB::table('heads')->where('user_id', $id)->delete();
            } elseif ($usr->role_id == 4) {
                $parent = DB::table('parents')->where('user_id', $id)->first();
                if ($parent) {
                    // parent_students.parent_id هو FK على users.user_id، وليس parents.parent_id
                    DB::table('parent_students')->where('parent_id', $id)->delete();
                    DB::table('parents')->where('parent_id', $parent->parent_id)->delete();
                }
            }
            DB::table('users')->where('user_id', $id)->delete();
        }

        return redirect()->back()->with('success', 'تم رفض وحذف طلب الحساب بنجاح.');
    }

    public function editAccount($id)
    {
        $usr = DB::table('users')->where('user_id', $id)->first();
        if (!$usr) {
            return back()->with('error', 'الحساب غير موجود.');
        }

        if ($usr->role_id == 3) { // Student
            $student = DB::table('students')->where('user_id', $id)->first();
            $departments = DB::table('departments')->get();
            $programs = DB::table('programs')->get();

            if ($student && !empty($student->program_id)) {
                $studentProg = DB::table('programs')->where('id', $student->program_id)->first();
                if ($studentProg) {
                    $studentDept = DB::table('departments')->where('department_id', $studentProg->department_id)->first();
                    if ($studentDept) {
                        $student->department = $studentDept->name;
                    }
                }
            }
            
            // Extract first name and last name
            $nameParts = explode(' ', $usr->full_name, 2);
            $usr->first_name = $nameParts[0] ?? '';
            $usr->last_name = $nameParts[1] ?? '';
            
            return view('admin.accounts.edit_student', compact('usr', 'student', 'departments', 'programs'));
        } elseif ($usr->role_id == 2) { // Teacher
            $teacher = DB::table('teachers')->where('user_id', $id)->first();
            $departments = DB::table('departments')->get();
            $teacherCourses = DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id ?? 0)->pluck('course_id')->toArray();
            
            $allCourses = DB::table('courses')
                ->leftJoin('course_program', 'courses.course_id', '=', 'course_program.course_id')
                ->leftJoin('programs', 'course_program.program_id', '=', 'programs.id')
                ->select('courses.course_id as id', 'courses.title', 'programs.department_id')
                ->get();
            $deptCourses = [];
            foreach ($allCourses as $c) {
                if ($c->department_id) {
                    $deptCourses[$c->department_id][] = ['id' => $c->id, 'title' => $c->title];
                }
            }
            $allBranches = DB::table('programs')->select('id', 'name', 'department_id')->get();
            $deptBranches = [];
            foreach ($allBranches as $b) {
                if ($b->department_id) {
                    $deptBranches[$b->department_id][] = ['id' => $b->id, 'name' => $b->name];
                }
            }
            
            $nameParts = explode(' ', $usr->full_name, 2);
            $usr->first_name = $nameParts[0] ?? '';
            $usr->last_name = $nameParts[1] ?? '';
            
            return view('admin.accounts.edit_teacher', compact('usr', 'teacher', 'departments', 'teacherCourses', 'deptCourses', 'deptBranches'));
        } elseif ($usr->role_id == 4) { // Parent
            $parent = DB::table('parents')->where('user_id', $id)->first();
            return view('admin.accounts.edit_parent', compact('usr', 'parent'));
        } elseif ($usr->role_id == 5) { // HOD
            $hod = DB::table('heads')->where('user_id', $id)->first();
            $departments = DB::table('departments')->get();
            $nameParts = explode(' ', $usr->full_name, 2);
            $usr->first_name = $usr->first_name ?: ($nameParts[0] ?? '');
            $usr->last_name = $usr->last_name ?: ($nameParts[1] ?? '');
            return view('admin.accounts.edit_hod', compact('usr', 'hod', 'departments'));
        } elseif ($usr->role_id == 6) { // Affairs
            $nameParts = explode(' ', $usr->full_name, 2);
            $usr->first_name = $usr->first_name ?: ($nameParts[0] ?? '');
            $usr->last_name = $usr->last_name ?: ($nameParts[1] ?? '');
            return view('admin.accounts.edit_affairs', compact('usr'));
        } else {
            return back()->with('error', 'لا يمكن تعديل هذا النوع من الحسابات.');
        }
    }

    public function updateAccount(Request $request, $id)
    {
        $this->normalizeAccountCredentials($request);


        $usr = DB::table('users')->where('user_id', $id)->first();
        if (!$usr) {
            return back()->with('error', 'الحساب غير موجود.');
        }

        $rules = [
            'first_name' => 'nullable|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'full_name'  => 'nullable|string|max:255',
            'phone'      => 'nullable|string|max:20',
            'email'      => [
                'required',
                'email',
                'max:255',
                'unique:users,email,'.$id.',user_id',
                
            ],
            'password'   => 'nullable|min:6|confirmed',
            'status'     => 'required|in:active,inactive',
        ];
        
        $usernameValidation = function ($attribute, $value, $fail) {
            if (str_contains(strtolower($value), '@edu-bridge.com') || str_contains(strtolower($value), '@edu-bridge') || str_contains($value, '@')) {
                $fail('اسم المستخدم يجب ألا يحتوي على @ أو نطاق (@edu-bridge.com).');
            }
        };

        if ($usr->role_id == 3) { // Student
            $rules['university_id']    = 'required|string|unique:users,university_id,'.$id.',user_id|max:255';
            $rules['department']       = 'required|string|max:255';
            $rules['program_id']       = 'required|integer|exists:programs,id';
            $rules['level']            = 'required|string|max:255';
            $rules['birth_date']       = 'required|date';
            $rules['gender']           = 'required|in:ذكر,أنثى';
            $rules['telegram_chat_id'] = 'nullable|string|max:100';
        } elseif ($usr->role_id == 2) { // Teacher
            $rules['department']     = 'required|string|max:255';
            $rules['specialization'] = 'required|string|max:255';
            $rules['username']       = ['required', 'string', 'max:255', 'unique:users,username,'.$id.',user_id'];
            $rules['courses']        = 'nullable|array';
        } elseif ($usr->role_id == 5) { // HOD
            $rules['department_id']  = 'required|exists:departments,department_id';
            $rules['username']       = ['required', 'string', 'max:255', 'unique:users,username,'.$id.',user_id'];
        } elseif ($usr->role_id == 6) { // Affairs
            $rules['username']       = ['required', 'string', 'max:255', 'unique:users,username,'.$id.',user_id'];
        } elseif ($usr->role_id == 4) { // Parent
            $rules['username']       = ['nullable', 'string', 'max:255', 'unique:users,username,'.$id.',user_id'];
        }

        $request->validate($rules, [
            'email.unique'         => 'البريد الإلكتروني مستخدم بالفعل لحساب آخر.',
            'username.unique'      => 'اسم المستخدم مستخدم بالفعل لحساب آخر.',
            'university_id.unique' => 'الرقم الجامعي مستخدم بالفعل لحساب آخر.',
            'password.confirmed'   => 'تأكيد كلمة المرور غير متطابق.',
            'password.min'         => 'يجب ألا تقل كلمة المرور عن 6 أحرف.',
        ]);

        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? $usr->full_name;
        }

        $updateData = [
            'full_name'  => $fullName,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'status'     => $request->status,
            'updated_at' => now(),
        ];

        if ($request->filled('first_name')) {
            $updateData['first_name'] = $request->first_name;
        }
        if ($request->filled('last_name')) {
            $updateData['last_name'] = $request->last_name;
        }
        
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if ($usr->role_id == 3) { // Student
            $progName = DB::table('programs')->where('id', $request->program_id)->value('name');
            $updateData['university_id']    = $request->university_id;
            $updateData['username']         = $request->university_id;
            $updateData['department']       = $request->department;
            $updateData['branch']           = $progName;
            $updateData['academic_year']    = $request->level;
            $updateData['gender']           = $request->gender;
            $updateData['birth_date']       = $request->birth_date;
            $updateData['telegram_chat_id'] = $request->telegram_chat_id;
        } elseif ($usr->role_id == 2) { // Teacher
            $updateData['department']       = $request->department;
            if ($request->filled('username')) {
                $updateData['username'] = $request->username;
            }
        } elseif ($usr->role_id == 5) { // HOD
            $deptName = DB::table('departments')->where('department_id', $request->department_id)->value('name');
            $updateData['department']       = $deptName;
            if ($request->filled('username')) {
                $updateData['username'] = $request->username;
            }
        } elseif (in_array($usr->role_id, [4, 6]) && $request->filled('username')) {
            $updateData['username'] = $request->username;
        }

        DB::transaction(function () use ($request, $usr, $id, $updateData) {
            DB::table('users')->where('user_id', $id)->update($updateData);

            if ($usr->role_id == 3) {
                DB::table('students')->updateOrInsert(
                    ['user_id' => $id],
                    [
                        'program_id'   => $request->program_id,
                        'student_code' => $request->university_id,
                        'level'        => $request->level,
                        'birth_date'   => $request->birth_date,
                        'updated_at'   => now(),
                    ]
                );
            } elseif ($usr->role_id == 2) {
                $teacher = DB::table('teachers')->where('user_id', $id)->first();
                if (!$teacher) {
                    $teacherId = DB::table('teachers')->insertGetId([
                        'user_id'        => $id,
                        'specialization' => $request->specialization,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                } else {
                    $teacherId = $teacher->teacher_id;
                    DB::table('teachers')->where('teacher_id', $teacherId)->update([
                        'specialization' => $request->specialization,
                        'updated_at'     => now(),
                    ]);
                }

                DB::table('course_teachers')->where('teacher_id', $teacherId)->delete();
                if ($request->has('courses') && is_array($request->courses)) {
                    foreach ($request->courses as $courseId) {
                        DB::table('course_teachers')->insertOrIgnore([
                            'course_id'  => $courseId,
                            'teacher_id' => $teacherId,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            } elseif ($usr->role_id == 5) {
                DB::table('heads')->updateOrInsert(
                    ['user_id' => $id],
                    [
                        'department_id' => $request->department_id,
                        'updated_at'    => now(),
                    ]
                );
            } elseif ($usr->role_id == 4) {
                DB::table('parents')->updateOrInsert(
                    ['user_id' => $id],
                    [
                        'updated_at' => now(),
                    ]
                );
            }
        });

        \App\Models\UserActivity::log('تعديل حساب', "قامت الإدارة بتعديل بيانات الحساب: {$fullName} ({$request->email})");

        return redirect()->route('admin.accounts')->with('success', 'تم تحديث بيانات الحساب بنجاح!');
    }

    // ─── Student Create & Store ───
    public function createStudent()
    {
        $departments = DB::table('departments')->get();
        $programs = DB::table('programs')->get();
        return view('admin.accounts.create_student', compact('departments', 'programs'));
    }

    public function storeStudent(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }
        $request->merge(['full_name' => $fullName]);

        $request->validate([
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'university_id'    => 'required|string|unique:users,university_id|max:255',
            'email'            => [
                'required',
                'email',
                'unique:users,email',
                'max:255',
                
            ],
            'phone'            => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:100',
            'department'       => 'required|string|max:255',
            'program_id'       => 'required|integer|exists:programs,id',
            'level'            => 'required|string|max:255',
            'birth_date'       => 'required|date',
            'gender'           => 'required|in:ذكر,أنثى',
            'password'         => 'required|string|min:6|confirmed',
        ], [
            'first_name.required'  => 'الاسم الأول مطلوب.',
            'last_name.required'   => 'الاسم الثاني مطلوب.',
            'university_id.unique' => 'الرقم الجامعي مستخدم بالفعل لحساب آخر.',
            'email.unique'         => 'البريد الإلكتروني مستخدم بالفعل لحساب آخر.',
            'password.confirmed'   => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $courseIds = collect();

        DB::transaction(function () use ($request, $fullName, &$courseIds) {
            $userId = DB::table('users')->insertGetId([
                'role_id'          => 3,
                'full_name'        => $fullName,
                'first_name'       => $request->first_name,
                'last_name'        => $request->last_name,
                'username'         => $request->university_id,
                'university_id'    => $request->university_id,
                'email'            => $request->email,
                'phone'            => $request->phone,
                'telegram_chat_id' => $request->telegram_chat_id,
                'password'         => bcrypt($request->password),
                'department'       => $request->department,
                'gender'           => $request->gender,
                'birth_date'       => $request->birth_date,
                'academic_year'    => $request->level,
                'status'           => 'active',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            $studentId = DB::table('students')->insertGetId([
                'user_id'      => $userId,
                'program_id'   => $request->program_id,
                'student_code' => $request->university_id,
                'level'        => $request->level,
                'birth_date'   => $request->birth_date,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            \App\Models\Student::autoAssignAdvisor($studentId);

            // تسجيل تلقائي بكل مواد الدورة والسنة
            $yearNum = $request->level === 'السنة الأولى' ? 1 : 2;
            $courseIds = DB::table('course_program')
                ->where('program_id', $request->program_id)
                ->join('courses', 'course_program.course_id', '=', 'courses.course_id')
                ->where('courses.year', $yearNum)
                ->pluck('course_program.course_id');

            foreach ($courseIds as $courseId) {
                DB::table('enrollments')->insert([
                    'student_id'      => $studentId,
                    'course_id'       => $courseId,
                    'enrollment_date' => now()->toDateString(),
                    'status'          => 'active',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        });

        // إرسال بيانات الطالب عبر تليجرام مباشرة (بعد نجاح الحفظ بالكامل)
        if ($request->filled('telegram_chat_id')) {
            try {
                $botToken = config('services.telegram.bot_token');
                $programName = DB::table('programs')->where('id', $request->program_id)->value('name') ?? $request->program_id;
                $message = "🎓 <b>مرحباً بك في جامعة Edu-Bridge!</b> 🎉\n\n"
                         . "تم إنشاء حساب الطالب الخاص بك بنجاح. إليك كافة التفاصيل والمعلومات:\n\n"
                         . "👤 <b>الاسم الكامل:</b> " . e($request->full_name) . "\n"
                         . "🔑 <b>الرقم الجامعي (اسم المستخدم):</b> <code>" . e($request->university_id) . "</code>\n"
                         . "🔒 <b>كلمة المرور:</b> <code>" . e($request->password) . "</code>\n"
                         . "📧 <b>البريد الإلكتروني:</b> <code>" . e($request->email) . "</code>\n"
                         . "📞 <b>رقم الهاتف:</b> <code>" . e($request->phone ?? '—') . "</code>\n"
                         . "🏢 <b>القسم:</b> <code>" . e($request->department) . "</code>\n"
                         . "💻 <b>البرنامج الدراسي:</b> <code>" . e($programName) . "</code>\n"
                         . "📚 <b>المستوى الدراسي:</b> <code>" . e($request->level) . "</code>\n"
                         . "📅 <b>تاريخ الميلاد:</b> <code>" . e($request->birth_date) . "</code>\n"
                         . "🚻 <b>الجنس:</b> <code>" . e($request->gender) . "</code>\n\n"
                         . "📲 يمكنك الآن تسجيل الدخول مباشرة إلى تطبيق الجامعة باستخدام رقمك الجامعي وكلمة المرور أعلاه.";

                \Illuminate\Support\Facades\Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $request->telegram_chat_id,
                    'text'    => $message,
                    'parse_mode' => 'HTML',
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Telegram Bot Error: ' . $e->getMessage());
            }
        }

        \App\Models\UserActivity::log('إنشاء حساب طالب', "قامت الإدارة بإنشاء حساب جديد للطالب: {$fullName} برقم جامعي ({$request->university_id})");

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب الطالب وتسجيله في ' . $courseIds->count() . ' مادة تلقائياً!');
    }

    // ─── Parent Create & Store ───
    public function createParent()
    {
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select('students.student_id', 'students.student_code', 'students.level', 'users.full_name')
            ->get();

        return view('admin.accounts.create_parent', compact('students'));
    }

    public function storeParent(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }

        $request->merge(['full_name' => $fullName]);

        $request->validate([
            'first_name'              => 'required|string|max:100',
            'last_name'               => 'required|string|max:100',
            'phone'                   => 'required|string|max:20',
            'username'                => [
                'required',
                'string',
                'unique:users,username',
                'max:255',
                
            ],
            'email'                   => [
                'required',
                'email',
                'unique:users,email',
                'max:255',
                
            ],
            'telegram_id'             => 'nullable|string|max:255',
            'children_university_ids' => 'nullable|array',
            'password'                => 'required|string|min:6|confirmed',
        ], [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'last_name.required'  => 'الاسم الثاني مطلوب.',
            'username.unique'     => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'        => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed'  => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        DB::transaction(function () use ($request, $fullName) {
            $userId = DB::table('users')->insertGetId([
                'role_id'          => 4, // parent
                'full_name'        => $fullName,
                'first_name'       => $request->first_name,
                'last_name'        => $request->last_name,
                'username'         => $request->username,
                'email'            => $request->email,
                'phone'            => $request->phone,
                'telegram_id'      => $request->telegram_id,
                'telegram_chat_id' => $request->telegram_id,
                'password'         => bcrypt($request->password),
                'status'           => 'active',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::table('parents')->insertGetId([
                'user_id'     => $userId,
                'telegram_id' => $request->telegram_id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            if ($request->filled('children_university_ids')) {
                foreach (array_filter($request->children_university_ids) as $universityId) {
                    $student = DB::table('students')
                        ->join('users', 'students.user_id', '=', 'users.user_id')
                        ->where('students.student_code', $universityId)
                        ->orWhere('users.username', $universityId)
                        ->select('students.student_id', 'users.user_id as student_user_id')
                        ->first();
                    if (!$student) continue;

                    // parent_students.parent_id/student_id هما FK على users.user_id (وليس parents.parent_id/students.student_id)
                    DB::table('parent_students')->insertOrIgnore([
                        'parent_id'    => $userId,
                        'student_id'   => $student->student_user_id,
                        'relationship' => 'guardian',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }
        });

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب ولي الأمر بنجاح ربطاً بالأبناء المحددين!');
    }

    // ─── Teacher Create & Store ───
    public function createTeacher()
    {
        $departments = DB::table('departments')->orderBy('name')->get();
        
        $coursesList = DB::table('courses')
            ->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
            ->join('programs', 'course_program.program_id', '=', 'programs.id')
            ->select('courses.course_id', 'courses.title', 'programs.department_id')
            ->distinct()
            ->get();
            
        $deptCourses = [];
        foreach ($coursesList as $c) {
            $deptCourses[$c->department_id][] = ['id' => $c->course_id, 'title' => $c->title];
        }
        
        $branchesList = DB::table('programs')->select('id', 'name', 'department_id')->get();
        $deptBranches = [];
        foreach ($branchesList as $b) {
            $deptBranches[$b->department_id][] = ['id' => $b->id, 'name' => $b->name];
        }
        
        $courses     = DB::table('courses')->orderBy('title')->get();
        return view('admin.accounts.create_teacher', compact('departments', 'courses', 'deptCourses', 'deptBranches'));
    }

    public function storeTeacher(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }
        $request->merge(['full_name' => $fullName]);

        $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'username'       => [
                'required',
                'string',
                'unique:users,username',
                'max:255',
                
            ],
            'phone'          => 'nullable|string|max:20',
            'email'          => [
                'required',
                'email',
                'unique:users,email',
                'max:255',
                
            ],
            'department'     => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'password'       => 'required|string|min:6|confirmed',
            'courses'        => 'nullable|array',
        ], [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'last_name.required'  => 'الاسم الثاني مطلوب.',
            'username.unique'     => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'        => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed'  => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        DB::transaction(function () use ($request, $fullName) {
            $userId = DB::table('users')->insertGetId([
                'role_id'    => 2,
                'full_name'  => $fullName,
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'username'   => $request->username,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'department' => $request->department,
                'password'   => bcrypt($request->password),
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $teacherId = DB::table('teachers')->insertGetId([
                'user_id'        => $userId,
                'specialization' => $request->specialization,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // ربط المواد
            if ($request->filled('courses')) {
                foreach ($request->courses as $courseId) {
                    DB::table('course_teachers')->insertOrIgnore([
                        'teacher_id' => $teacherId,
                        'course_id'  => $courseId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب المعلم بنجاح!');
    }

    // ─── HOD Create & Store ───
    public function createHOD()
    {
        $departments = DB::table('departments')->get();
        return view('admin.accounts.create_hod', compact('departments'));
    }

    public function storeHOD(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }
        $request->merge(['full_name' => $fullName]);

        $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'username'      => [
                'required',
                'string',
                'unique:users,username',
                'max:255',
                
            ],
            'phone'         => 'nullable|string|max:20',
            'email'         => [
                'required',
                'email',
                'unique:users,email',
                'max:255',
                
            ],
            'department_id' => 'required|exists:departments,department_id',
            'password'      => 'required|string|min:6|confirmed',
        ], [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'last_name.required'  => 'الاسم الثاني مطلوب.',
            'username.unique'     => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'        => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed'  => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();

        DB::transaction(function () use ($request, $fullName, $dept) {
            $userId = DB::table('users')->insertGetId([
                'role_id'    => 5,
                'full_name'  => $fullName,
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'username'   => $request->username,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'department' => $dept ? $dept->name : null,
                'password'   => bcrypt($request->password),
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('heads')->insert([
                'user_id'       => $userId,
                'department_id' => $request->department_id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        });

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب رئيس القسم بنجاح وتخصيص القسم له!');
    }

    // ─── Affairs Create & Store ───
    public function createAffairs()
    {
        return view('admin.accounts.create_affairs');
    }

    public function storeAffairs(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }
        $request->merge(['full_name' => $fullName]);

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'username'   => [
                'required',
                'string',
                'unique:users,username',
                'max:255',
                
            ],
            'phone'      => 'nullable|string|max:20',
            'email'      => [
                'required',
                'email',
                'unique:users,email',
                'max:255',
                
            ],
            'password'   => 'required|string|min:6|confirmed',
        ], [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'last_name.required'  => 'الاسم الثاني مطلوب.',
            'username.unique'     => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'        => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed'  => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        DB::table('users')->insert([
            'role_id'    => 6,
            'full_name'  => $fullName,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'username'   => $request->username,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'password'   => bcrypt($request->password),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب موظف الشؤون بنجاح!');
    }

    // ─── Dynamic Deletion Lists ───
    public function deleteList($role_id)
    {
        $roleId = intval($role_id);
        
        // Define role titles and UI configs
        $roleTitlePlural = '';
        $searchPlaceholder = '';
        $cardIcon = '';
        $cardIconColor = '';

        if ($roleId == 3) {
            $roleTitlePlural = 'الطلاب';
            $searchPlaceholder = 'بحث عن طالب بالاسم أو البريد...';
            $cardIcon = 'school';
            $cardIconColor = 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400';
            
            $users = DB::table('users')
                ->join('students', 'users.user_id', '=', 'students.user_id')
                ->select('users.*', 'students.student_code', 'students.level')
                ->get();
        } elseif ($roleId == 2) {
            $roleTitlePlural = 'المدربين والمعلمين';
            $searchPlaceholder = 'بحث عن مدرب بالاسم أو الاختصاص...';
            $cardIcon = 'sports';
            $cardIconColor = 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400';

            $users = DB::table('users')
                ->join('teachers', 'users.user_id', '=', 'teachers.user_id')
                ->select('users.*', 'teachers.specialization')
                ->get();
        } elseif ($roleId == 5) {
            $roleTitlePlural = 'رؤساء الأقسام';
            $searchPlaceholder = 'بحث عن رئيس قسم بالاسم...';
            $cardIcon = 'supervisor_account';
            $cardIconColor = 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400';

            $users = DB::table('users')
                ->join('heads', 'users.user_id', '=', 'heads.user_id')
                ->select('users.*')
                ->get();
        } elseif ($roleId == 4) {
            $roleTitlePlural = 'أولياء الأمور';
            $searchPlaceholder = 'بحث عن ولي أمر بالاسم أو رقم الهاتف...';
            $cardIcon = 'family_restroom';
            $cardIconColor = 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400';

            $users = DB::table('users')
                ->join('parents', 'users.user_id', '=', 'parents.user_id')
                ->select('users.*')
                ->get();
        } elseif ($roleId == 6) {
            $roleTitlePlural = 'موظفي الشؤون';
            $searchPlaceholder = 'بحث عن موظف بالاسم...';
            $cardIcon = 'badge';
            $cardIconColor = 'bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400';

            $users = DB::table('users')
                ->where('role_id', 6)
                ->get();
        } else {
            return redirect()->route('admin.accounts')->with('error', 'فئة الصلاحية المحددة غير صالحة.');
        }

        return view('admin.accounts.delete_list', compact('users', 'roleId', 'roleTitlePlural', 'searchPlaceholder', 'cardIcon', 'cardIconColor'));
    }

    public function deleteAccounts(Request $request, $role_id)
    {
        $roleId = intval($role_id);
        
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,user_id',
        ], [
            'user_ids.required' => 'يرجى تحديد مستخدم واحد على الأقل للحذف.',
        ]);

        $userIds = $request->user_ids;

        foreach ($userIds as $id) {
            // Delete dependent records first to maintain foreign key integrity
            if ($roleId == 3) {
                $student = DB::table('students')->where('user_id', $id)->first();
                if ($student) {
                    DB::table('parent_students')->where('student_id', $student->student_id)->delete();
                    DB::table('students')->where('student_id', $student->student_id)->delete();
                }
            } elseif ($roleId == 2) {
                $teacher = DB::table('teachers')->where('user_id', $id)->first();
                if ($teacher) {
                    DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->delete();
                    DB::table('teachers')->where('teacher_id', $teacher->teacher_id)->delete();
                }
            } elseif ($roleId == 5) {
                DB::table('heads')->where('user_id', $id)->delete();
            } elseif ($roleId == 4) {
                $parent = DB::table('parents')->where('user_id', $id)->first();
                if ($parent) {
                    DB::table('parent_students')->where('parent_id', $parent->parent_id)->delete();
                    DB::table('parents')->where('parent_id', $parent->parent_id)->delete();
                }
            }

            // Finally, delete user from users table
            DB::table('users')->where('user_id', $id)->delete();
        }

        return redirect()->route('admin.accounts')->with('success', 'تم حذف الحسابات المحددة نهائياً وبنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  COURSES (PROGRAMS) MANAGEMENT
    // ────────────────────────────────────────────────────────────

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

    // ────────────────────────────────────────────────────────────
    //  SEMESTERS & SUBJECTS
    // ────────────────────────────────────────────────────────────

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
