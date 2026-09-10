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


}
