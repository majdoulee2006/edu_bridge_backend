@extends('layouts.hod')

@section('title', 'الحسابات')

@push('styles')
<style>
    .page-subtitle { color: var(--text-secondary); font-size: 1rem; margin-top: -1.5rem; margin-bottom: 2rem; }

    .type-switcher {
        display: flex;
        background-color: var(--bg-secondary);
        border-radius: 1rem;
        padding: 0.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
    }
    .type-btn {
        flex: 1;
        padding: 0.75rem;
        text-align: center;
        border-radius: 0.75rem;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        border: none;
        background: transparent;
        color: var(--text-secondary);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }
    .type-btn.active { background-color: var(--accent-color); color: #1a1a1a; }

    .add-btn {
        width: 100%;
        padding: 1.25rem;
        border: 2px dashed var(--border-color);
        background-color: transparent;
        border-radius: 1rem;
        color: var(--text-primary);
        font-weight: 700;
        font-size: 1.1rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        margin-bottom: 2rem;
        transition: border-color 0.2s;
        font-family: inherit;
    }
    .add-btn:hover { border-color: var(--accent-color); }
    .add-icon { background-color: #fefce8; color: #ca8a04; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

    .account-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: var(--shadow);
        position: relative;
    }
    .account-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
    .account-avatar { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; }
    .avatar-blue   { background-color: #dbeafe; color: #1d4ed8; }
    .avatar-purple { background-color: #f3e8ff; color: #7e22ce; }
    .avatar-orange { background-color: #ffedd5; color: #c2410c; }
    .avatar-green  { background-color: #dcfce7; color: #16a34a; }
    .account-info h4 { font-size: 1.15rem; font-weight: 700; margin-bottom: 0.2rem; }
    .account-info p  { color: var(--text-secondary); font-size: 0.88rem; }
    .delete-btn-wrap { position: absolute; top: 1.25rem; left: 1.25rem; }

    .chips-container { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .chip { padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.83rem; font-weight: 600; background-color: var(--bg-primary); color: var(--text-secondary); }
    .chip-accent { background-color: #fefce8; color: #ca8a04; border: 1px solid #fef08a; }

    /* Modal */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); display: flex; align-items: center;
        justify-content: center; z-index: 1000; opacity: 0; pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }
    .modal-card {
        background-color: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem;
        width: 92%; max-width: 560px; box-shadow: var(--shadow);
        transform: translateY(20px); transition: transform 0.3s ease;
        max-height: 90vh; overflow-y: auto;
    }
    .modal-overlay.active .modal-card { transform: translateY(0); }

    .form-label { display: block; margin-bottom: 0.4rem; font-weight: 700; font-size: 0.88rem; color: var(--text-secondary); }
    .form-input {
        width: 100%; padding: 0.7rem 0.9rem; border-radius: 0.75rem;
        border: 1px solid var(--border-color); background-color: var(--bg-primary);
        color: var(--text-primary); font-family: inherit; font-size: 0.95rem;
        box-sizing: border-box;
    }
    .form-input:focus { outline: none; border-color: var(--accent-color); }
    .form-group { margin-bottom: 0.9rem; }
    .form-row { display: flex; gap: 0.75rem; margin-bottom: 0.9rem; }
    .form-row > * { flex: 1; }

    .gender-toggle { display: flex; background: var(--bg-primary); border-radius: 0.75rem; border: 1px solid var(--border-color); overflow: hidden; }
    .gender-toggle label { flex: 1; text-align: center; padding: 0.65rem; cursor: pointer; font-weight: 600; font-size: 0.9rem; color: var(--text-secondary); transition: all 0.2s; }
    .gender-toggle input[type=radio] { display: none; }
    .gender-toggle input[type=radio]:checked + label { background-color: var(--accent-color); color: #1a1a1a; }

    .courses-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; background: var(--bg-primary); border-radius: 0.75rem; border: 1px solid var(--border-color); padding: 0.75rem; max-height: 180px; overflow-y: auto; }
    .course-check { display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; transition: all 0.15s; }
    .course-check:hover { border-color: var(--accent-color); background: rgba(202,138,4,0.05); }
    .course-check input { accent-color: var(--accent-color); cursor: pointer; }
    .course-check span { font-size: 0.83rem; font-weight: 600; color: var(--text-primary); }

    .uid-result { display: none; font-size: 0.8rem; padding: 0.35rem 0.6rem; border-radius: 0.5rem; margin-top: 0.3rem; font-weight: 600; }
    .uid-ok  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .uid-err { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    .btn-save   { background-color: var(--accent-color); color: #1a1a1a; flex: 1; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem; font-family: inherit; }
    .btn-cancel { background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); flex: 1; padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem; font-family: inherit; }
</style>
@endpush

@section('content')
    <p class="page-subtitle">إدارة المستخدمين والصلاحيات</p>


    @if($errors->any())
        <div style="background:#fef2f2;color:#dc2626;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;">
            <ul style="margin:0; padding-right:1.5rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <script>
            // Open the parent modal automatically if we just submitted the parent form and it failed
            document.addEventListener('DOMContentLoaded', function() {
                // If there's an error, and the URL has #parent, we could open it, or just show the errors
            });
        </script>
    @endif

    {{-- Tab Switcher --}}
    <div class="type-switcher">
        <button class="type-btn active" id="btn-teachers" onclick="switchTab('teachers')">
            <i class="fa-solid fa-chalkboard-teacher"></i> مدرب
        </button>
        <button class="type-btn" id="btn-students" onclick="switchTab('students')">
            <i class="fa-solid fa-user-graduate"></i> طالب
        </button>
        <button class="type-btn" id="btn-parents" onclick="switchTab('parents')">
            <i class="fa-solid fa-people-roof"></i> ولي الأمر
        </button>
    </div>

    {{-- Add Button --}}
    <button id="add-account-btn" class="add-btn" onclick="openAddModal()">
        <div class="add-icon"><i class="fa-solid fa-plus"></i></div>
        <span id="add-btn-text">إضافة حساب مدرب جديد</span>
    </button>

    {{-- ===== Tab: Teachers ===== --}}
    <div id="tab-teachers">
        @forelse($teachers as $index => $teacher)
        @php $colorClass = ['avatar-blue','avatar-purple','avatar-orange','avatar-green'][$index % 4]; @endphp
        <div class="account-card">
            <div class="delete-btn-wrap" style="display:flex; gap:0.5rem;">
                <button type="button" onclick="openEditModal({{ $teacher->user_id }}, '{{ addslashes($teacher->full_name) }}', '{{ addslashes($teacher->email) }}', '{{ addslashes($teacher->phone) }}', 'teacher', {{ json_encode($teacher->course_ids) }})" style="background:transparent;border:none;color:#3b82f6;cursor:pointer;font-size:1.1rem;"><i class="fa-solid fa-pen"></i></button>
                <form action="{{ route('hod.accounts.delete', $teacher->user_id) }}" method="POST" onsubmit="return confirm('حذف هذا الحساب؟')">
                    @csrf
                    <button type="submit" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;"><i class="fa-solid fa-trash-can"></i></button>
                </form>
            </div>
            <div class="account-header">
                <div class="account-avatar {{ $colorClass }}">{{ mb_substr($teacher->full_name,0,1,'UTF-8') }}</div>
                <div class="account-info">
                    <h4>{{ $teacher->full_name }}</h4>
                    <p>{{ $teacher->department ?? '' }}{{ $teacher->department && $teacher->specialization ? ' — ' : '' }}{{ $teacher->specialization ?? '' }}</p>
                </div>
            </div>
            <div class="chips-container">
                @if($teacher->email)<span class="chip">{{ $teacher->email }}</span>@endif
                @if($teacher->phone)<span class="chip">{{ $teacher->phone }}</span>@endif
                @if($teacher->is_advisor)
                    <span class="chip" style="background:#fdf4ff;color:#9333ea;border:1px solid #f3e8ff;font-weight:700;"><i class="fa-solid fa-star"></i> مربي دورة: {{ $teacher->advisor_course_title }}</span>
                @endif
                @forelse($teacher->courses ?? [] as $course)
                    <span class="chip chip-accent">{{ $course }}</span>
                @empty
                    <span class="chip" style="border:1px dashed var(--border-color);background:transparent;">لا توجد مواد مسندة</span>
                @endforelse
            </div>
            
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border-color); display: flex; gap: 0.5rem;">
                <button onclick="openAdvisorModal({{ $teacher->teacher_id }}, '{{ $teacher->full_name }}', {{ $teacher->is_advisor ? 'true' : 'false' }})" style="background: var(--bg-primary); border: 1px solid var(--border-color); padding: 0.5rem 1rem; border-radius: 0.5rem; font-family: inherit; font-weight: 700; font-size: 0.85rem; color: var(--text-primary); cursor: pointer; transition: all 0.2s;">
                    <i class="fa-solid fa-user-tie"></i> إدارة مربي الدورة
                </button>
            </div>
        </div>
        @empty
        <div class="account-card" style="text-align:center;color:var(--text-secondary);padding:2rem;">لا توجد حسابات مدربين حالياً.</div>
        @endforelse
    </div>

    {{-- ===== Tab: Students ===== --}}
    <div id="tab-students" style="display:none;">
        @forelse($students as $index => $student)
        @php $colorClass = ['avatar-blue','avatar-purple','avatar-orange','avatar-green'][$index % 4]; @endphp
        <div class="account-card">
            <div class="delete-btn-wrap" style="display:flex; gap:0.5rem;">
                <button type="button" onclick="openEditModal({{ $student->user_id }}, '{{ addslashes($student->full_name) }}', '{{ addslashes($student->email) }}', '{{ addslashes($student->phone) }}')" style="background:transparent;border:none;color:#3b82f6;cursor:pointer;font-size:1.1rem;"><i class="fa-solid fa-pen"></i></button>
                <form action="{{ route('hod.accounts.delete', $student->user_id) }}" method="POST" onsubmit="return confirm('حذف هذا الحساب؟')">
                    @csrf
                    <button type="submit" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;"><i class="fa-solid fa-trash-can"></i></button>
                </form>
            </div>
            <div class="account-header">
                <div class="account-avatar {{ $colorClass }}">{{ mb_substr($student->full_name,0,1,'UTF-8') }}</div>
                <div class="account-info">
                    <h4>{{ $student->full_name }}</h4>
                    <p>{{ $student->department ?? '' }}{{ $student->department && $student->level ? ' — ' : '' }}{{ $student->level ?? '' }}</p>
                </div>
            </div>
            <div class="chips-container">
                @if($student->university_id)<span class="chip chip-accent">{{ $student->university_id }}</span>@endif
                @if($student->email)<span class="chip">{{ $student->email }}</span>@endif
                @if($student->phone)<span class="chip">{{ $student->phone }}</span>@endif
                @if($student->gender)<span class="chip">{{ $student->gender }}</span>@endif
                @if($student->birth_date)<span class="chip">{{ $student->birth_date }}</span>@endif
            </div>
        </div>
        @empty
        <div class="account-card" style="text-align:center;color:var(--text-secondary);padding:2rem;">لا توجد حسابات طلاب حالياً.</div>
        @endforelse
    </div>

    {{-- ===== Tab: Parents ===== --}}
    <div id="tab-parents" style="display:none;">
        @forelse($parents as $index => $parent)
        @php $colorClass = ['avatar-green','avatar-blue','avatar-purple','avatar-orange'][$index % 4]; @endphp
        <div class="account-card">
            <div class="delete-btn-wrap" style="display:flex; gap:0.5rem;">
                <button type="button" onclick="openEditModal({{ $parent->user_id }}, '{{ addslashes($parent->full_name) }}', '{{ addslashes($parent->email) }}', '{{ addslashes($parent->phone) }}')" style="background:transparent;border:none;color:#3b82f6;cursor:pointer;font-size:1.1rem;"><i class="fa-solid fa-pen"></i></button>
                <form action="{{ route('hod.accounts.delete', $parent->user_id) }}" method="POST" onsubmit="return confirm('حذف هذا الحساب؟')">
                    @csrf
                    <button type="submit" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;"><i class="fa-solid fa-trash-can"></i></button>
                </form>
            </div>
            <div class="account-header">
                <div class="account-avatar {{ $colorClass }}">{{ mb_substr($parent->full_name,0,1,'UTF-8') }}</div>
                <div class="account-info">
                    <h4>{{ $parent->full_name }}</h4>
                    <p>{{ $parent->email }} @if($parent->phone) | {{ $parent->phone }} @endif</p>
                </div>
            </div>
            <div class="chips-container">
                <span class="chip" style="color:var(--text-secondary);font-size:0.8rem;">الأبناء:</span>
                @forelse($parent->children ?? [] as $child)
                    <span class="chip chip-accent">{{ $child }}</span>
                @empty
                    <span class="chip" style="border:1px dashed var(--border-color);background:transparent;">لا يوجد أبناء مرتبطون</span>
                @endforelse
            </div>
        </div>
        @empty
        <div class="account-card" style="text-align:center;color:var(--text-secondary);padding:2rem;">لا توجد حسابات أولياء أمور حالياً.</div>
        @endforelse
    </div>


    {{-- ==================== MODALS ==================== --}}

    {{-- Modal: Add Teacher --}}
    <div id="teacher-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size:1.4rem;font-weight:800;margin-bottom:1.25rem;text-align:center;">إضافة حساب مدرب جديد</h4>
            <form action="{{ route('hod.accounts.store_teacher') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" name="full_name" required placeholder="مثال: د. أحمد المحمد" class="form-input">
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">رقم الهاتف</label>
                        <input type="tel" name="phone" placeholder="09xxxxxxxx" dir="ltr" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">البريد الإلكتروني <span style="color:#ef4444">*</span></label>
                        <input type="email" name="email" required placeholder="teacher@domain.com" dir="ltr" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">القسم <span style="color:#ef4444">*</span></label>
                    <input type="text" name="department" value="{{ Auth::user()->department }}" readonly class="form-input" style="background-color: var(--bg-primary); cursor: not-allowed; color: var(--text-secondary);">
                </div>
                <div class="form-group">
                    <label class="form-label">التخصصات (الفروع) <span style="color:#ef4444">*</span></label>
                    <div class="courses-grid" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));">
                        @foreach($branches as $branch)
                            <label class="course-check">
                                <input type="checkbox" name="specializations[]" value="{{ $branch->name }}" class="branch-checkbox" onchange="filterTeacherCourses()">
                                <span>{{ $branch->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">المواد التي يدرسها</label>
                    <div class="courses-grid" id="teacher-courses-grid">
                        <span style="color:var(--text-secondary); font-size:0.9rem;">يرجى اختيار الفرع لعرض المواد المتاحة.</span>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">كلمة المرور <span style="color:#ef4444">*</span></label>
                        <input type="password" name="password" required minlength="6" placeholder="••••••••" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">تأكيد كلمة المرور <span style="color:#ef4444">*</span></label>
                        <input type="password" name="password_confirmation" required placeholder="••••••••" class="form-input">
                    </div>
                </div>
                <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                    <button type="submit" class="btn-save">إضافة وحفظ</button>
                    <button type="button" onclick="closeModal('teacher-modal')" class="btn-cancel">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Add Student --}}
    <div id="student-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size:1.4rem;font-weight:800;margin-bottom:1.25rem;text-align:center;">إضافة حساب طالب جديد</h4>
            <form action="{{ route('hod.accounts.store_student') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">الاسم الكامل <span style="color:#ef4444">*</span></label>
                    <input type="text" name="full_name" required placeholder="مثال: يوسف المحمد" class="form-input">
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">رقم الهاتف</label>
                        <input type="tel" name="phone" placeholder="09xxxxxxxx" dir="ltr" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">الرقم الجامعي <span style="color:#ef4444">*</span></label>
                        <input type="text" name="university_id" required placeholder="2023xxxx" dir="ltr" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني <span style="color:#ef4444">*</span></label>
                    <input type="email" name="email" required placeholder="student@university.edu" dir="ltr" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Telegram Chat ID (اختياري)</label>
                    <input type="text" name="telegram_chat_id" placeholder="مثال: 123456789" dir="ltr" class="form-input" value="7650604064" title="احصل عليه من بوت الجامعة لإرسال الإشعارات وبيانات الدخول فوراً">
                    <small style="color: #6b7280; font-size: 0.8rem;">قم بإدخاله إذا كنت تريد إرسال بيانات الدخول للطالب فور إنشاء الحساب</small>
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">القسم <span style="color:#ef4444">*</span></label>
                        <input type="text" name="department" value="{{ Auth::user()->department }}" readonly class="form-input" style="background-color: var(--bg-primary); cursor: not-allowed; color: var(--text-secondary);">
                    </div>
                    <div>
                        <label class="form-label">التخصص (الفرع) <span style="color:#ef4444">*</span></label>
                        <select name="program_id" required class="form-input">
                            <option value="" disabled selected>اختر الفرع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">السنة الدراسية <span style="color:#ef4444">*</span></label>
                        <select name="level" required class="form-input">
                            <option value="" disabled selected>اختر السنة</option>
                            <option value="السنة الأولى">السنة الأولى</option>
                            <option value="السنة الثانية">السنة الثانية</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">تاريخ الميلاد <span style="color:#ef4444">*</span></label>
                        <input type="date" name="birth_date" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">الجنس <span style="color:#ef4444">*</span></label>
                        <div class="gender-toggle">
                            <input type="radio" name="gender" id="male-s" value="ذكر" checked>
                            <label for="male-s">ذكر</label>
                            <input type="radio" name="gender" id="female-s" value="أنثى">
                            <label for="female-s">أنثى</label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">كلمة المرور <span style="color:#ef4444">*</span></label>
                        <input type="password" name="password" required minlength="6" placeholder="••••••••" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">تأكيد كلمة المرور <span style="color:#ef4444">*</span></label>
                        <input type="password" name="password_confirmation" required placeholder="••••••••" class="form-input">
                    </div>
                </div>
                <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                    <button type="submit" class="btn-save">إضافة وحفظ</button>
                    <button type="button" onclick="closeModal('student-modal')" class="btn-cancel">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Add Parent --}}
    <div id="parent-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size:1.4rem;font-weight:800;margin-bottom:1.25rem;text-align:center;">إضافة حساب ولي أمر جديد</h4>
            <form action="{{ route('hod.accounts.store_parent') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">الاسم الكامل <span style="color:#ef4444">*</span></label>
                    <input type="text" name="full_name" required placeholder="مثال: يوسف الخالد" class="form-input">
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">رقم الهاتف <span style="color:#ef4444">*</span></label>
                        <input type="tel" name="phone" required placeholder="09xxxxxxxx" dir="ltr" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">اسم المستخدم <span style="color:#ef4444">*</span></label>
                        <input type="text" name="username" required placeholder="yousef_parent" dir="ltr" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني <span style="color:#ef4444">*</span></label>
                    <input type="email" name="email" required placeholder="parent@test.com" dir="ltr" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">عدد الأبناء</label>
                    <input type="number" id="children_count" min="1" max="10" value="1"
                           oninput="updateChildrenFields(parseInt(this.value)||1)"
                           class="form-input" placeholder="أدخل عدد الأبناء">
                </div>
                <div id="children-fields" style="margin-bottom:0.9rem;display:flex;flex-direction:column;gap:0.6rem;"></div>
                <div class="form-row">
                    <div>
                        <label class="form-label">كلمة المرور <span style="color:#ef4444">*</span></label>
                        <input type="password" name="password" required minlength="6" placeholder="••••••••" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">تأكيد كلمة المرور <span style="color:#ef4444">*</span></label>
                        <input type="password" name="password_confirmation" required placeholder="••••••••" class="form-input">
                    </div>
                </div>
                <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                    <button type="submit" class="btn-save">إضافة وحفظ</button>
                    <button type="button" onclick="closeModal('parent-modal')" class="btn-cancel">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Assign Advisor --}}
    <div id="advisor-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size:1.4rem;font-weight:800;margin-bottom:1.25rem;text-align:center;">إدارة مربي الدورة لـ <span id="advisor-teacher-name" style="color:var(--accent-color);"></span></h4>
            <form action="{{ route('hod.accounts.advisor') }}" method="POST">
                @csrf
                <input type="hidden" name="teacher_id" id="advisor-teacher-id">
                
                <div class="form-group">
                    <label class="form-label">الإجراء <span style="color:#ef4444">*</span></label>
                    <select name="action" id="advisor-action" required class="form-input" onchange="toggleAdvisorAction(this.value)">
                        <option value="assign">تعيين كمربي دورة</option>
                        <option value="remove">إزالة صفة المربي</option>
                    </select>
                </div>

                <div id="advisor-assign-fields">
                    <div class="form-group">
                        <label class="form-label">الفرع <span style="color:#ef4444">*</span></label>
                        <select name="branch" id="advisor-branch-select" class="form-input">
                            <option value="" disabled selected>اختر الفرع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->name }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">السنة الدراسية <span style="color:#ef4444">*</span></label>
                        <select name="year" id="advisor-year-select" class="form-input">
                            <option value="" disabled selected>اختر السنة الدراسية</option>
                            <option value="السنة الأولى">السنة الأولى</option>
                            <option value="السنة الثانية">السنة الثانية</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">الشعبة (اختياري)</label>
                        <select name="section" id="advisor-section-select" class="form-input">
                            <option value="">لا يوجد شعبة محددة</option>
                            <option value="شعبة 1">شعبة 1</option>
                            <option value="شعبة 2">شعبة 2</option>
                            <option value="شعبة 3">شعبة 3</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
                    <button type="submit" class="btn-save" style="background:#9333ea;color:#fff;">حفظ التغييرات</button>
                    <button type="button" onclick="closeModal('advisor-modal')" class="btn-cancel">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Edit Account --}}
    <div id="edit-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size:1.4rem;font-weight:800;margin-bottom:1.25rem;text-align:center;">تعديل بيانات الحساب</h4>
            <form id="edit-account-form" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">الاسم الكامل <span style="color:#ef4444">*</span></label>
                    <input type="text" id="edit_full_name" name="full_name" required class="form-input">
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">البريد الإلكتروني <span style="color:#ef4444">*</span></label>
                        <input type="email" id="edit_email" name="email" required dir="ltr" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">رقم الهاتف</label>
                        <input type="tel" id="edit_phone" name="phone" dir="ltr" class="form-input">
                    </div>
                </div>
                
                <div class="form-group" id="edit_teacher_courses" style="display:none; margin-top: 1rem;">
                    <label class="form-label">المواد التي يدرسها (للتعديل) <span style="color:#ef4444">*</span></label>
                    <div class="courses-grid">
                        @foreach($all_courses as $course)
                            <label class="course-check edit-course-label" data-assigned="{{ $course->is_assigned ? 'true' : 'false' }}">
                                <input type="checkbox" name="courses[]" value="{{ $course->course_id }}" class="edit-course-checkbox">
                                <span>{{ $course->title }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">كلمة المرور <span style="color:#94a3b8; font-size:0.8rem;">(اختياري - اتركها فارغة إذا لم ترد التغيير)</span></label>
                    <input type="password" name="password" minlength="6" placeholder="••••••••" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">تأكيد كلمة المرور</label>
                    <input type="password" name="password_confirmation" placeholder="••••••••" class="form-input">
                </div>
                <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                    <button type="submit" class="btn-save">حفظ التعديلات</button>
                    <button type="button" onclick="closeModal('edit-modal')" class="btn-cancel">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    let activeTab = 'teachers';

    const coursesByBranch = @json($coursesByBranch ?? []);

    function filterTeacherCourses() {
        const checkboxes = document.querySelectorAll('.branch-checkbox:checked');
        const selectedBranches = Array.from(checkboxes).map(cb => cb.value);
        const grid = document.getElementById('teacher-courses-grid');
        grid.innerHTML = ''; // Clear current

        if (selectedBranches.length === 0) {
            grid.innerHTML = '<span style="color:var(--text-secondary); font-size:0.9rem;">يرجى اختيار فرع أو أكثر لعرض المواد المتاحة.</span>';
            return;
        }

        const uniqueCourses = new Map();

        selectedBranches.forEach(branchName => {
            if (coursesByBranch[branchName]) {
                coursesByBranch[branchName].forEach(course => {
                    uniqueCourses.set(course.id, course.title);
                });
            }
        });

        if (uniqueCourses.size === 0) {
            grid.innerHTML = '<span style="color:var(--text-secondary); font-size:0.9rem;">لا توجد مواد لهذه الفروع.</span>';
            return;
        }

        uniqueCourses.forEach((title, id) => {
            const label = document.createElement('label');
            label.className = 'course-check';
            label.innerHTML = `
                <input type="checkbox" name="courses[]" value="${id}">
                <span>${title}</span>
            `;
            grid.appendChild(label);
        });
    }

    function switchTab(tab) {
        activeTab = tab;
        ['teachers','students','parents'].forEach(t => {
            document.getElementById('tab-' + t).style.display = (t === tab) ? 'block' : 'none';
            document.getElementById('btn-' + t).classList.toggle('active', t === tab);
        });
        const labels = { teachers: 'مدرب جديد', students: 'طالب جديد', parents: 'ولي أمر جديد' };
        document.getElementById('add-btn-text').innerText = 'إضافة حساب ' + labels[tab];
    }

    function openAddModal() {
        const map = { teachers: 'teacher-modal', students: 'student-modal', parents: 'parent-modal' };
        openModal(map[activeTab]);
        if (activeTab === 'parents') updateChildrenFields(1);
    }

    function openModal(id)  { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    // ===== Advisor Modal =====
    function openAdvisorModal(teacherId, teacherName, isAdvisor) {
        document.getElementById('advisor-teacher-id').value = teacherId;
        document.getElementById('advisor-teacher-name').innerText = teacherName;
        
        const actionSelect = document.getElementById('advisor-action');
        if (isAdvisor) {
            actionSelect.value = 'remove';
        } else {
            actionSelect.value = 'assign';
        }
        toggleAdvisorAction(actionSelect.value);
        openModal('advisor-modal');
    }

    function toggleAdvisorAction(action) {
        const assignFields = document.getElementById('advisor-assign-fields');
        const branchSelect = document.getElementById('advisor-branch-select');
        const yearSelect = document.getElementById('advisor-year-select');
        if (action === 'assign') {
            assignFields.style.display = 'block';
            branchSelect.required = true;
            yearSelect.required = true;
        } else {
            assignFields.style.display = 'none';
            branchSelect.required = false;
            yearSelect.required = false;
        }
    }

    // ===== Children Fields (Parent Modal) =====
    const ordinals = ['الأول','الثاني','الثالث','الرابع','الخامس','السادس','السابع','الثامن','التاسع','العاشر'];
    let lookupTimers = {};

    function openEditModal(userId, fullName, email, phone, role = '', courseIds = []) {
        const form = document.getElementById('edit-account-form');
        form.action = `/hod/accounts/update/${userId}`;
        document.getElementById('edit_full_name').value = fullName;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_phone').value = phone || '';
        
        const coursesSection = document.getElementById('edit_teacher_courses');
        if (role === 'teacher') {
            coursesSection.style.display = 'block';
            const numCourseIds = (courseIds || []).map(id => parseInt(id));
            
            document.querySelectorAll('.edit-course-checkbox').forEach(cb => {
                cb.checked = false;
                const isAssigned = cb.parentElement.getAttribute('data-assigned') === 'true';
                const isCurrentTeacherCourse = numCourseIds.includes(parseInt(cb.value));
                
                if (isAssigned && !isCurrentTeacherCourse) {
                    cb.parentElement.style.display = 'none';
                } else {
                    cb.parentElement.style.display = '';
                }
            });
            if (courseIds && courseIds.length) {
                courseIds.forEach(id => {
                    const cb = document.querySelector(`.edit-course-checkbox[value="${id}"]`);
                    if (cb) cb.checked = true;
                });
            }
        } else {
            coursesSection.style.display = 'none';
        }
        
        openModal('edit-modal');
    }

    function updateChildrenFields(count) {
        count = Math.max(1, Math.min(count, 10));
        const container = document.getElementById('children-fields');
        container.innerHTML = '';
        for (let i = 1; i <= count; i++) {
            container.innerHTML += `
            <div>
                <label class="form-label">الرقم الجامعي للابن ${ordinals[i-1] || i}</label>
                <input name="children_university_ids[]" type="text" dir="ltr"
                       placeholder="مثال: 2023xxxx" id="uid-input-${i}"
                       oninput="lookupStudent(${i}, this.value)"
                       ${i===1?'required':''} class="form-input">
                <div id="uid-result-${i}" class="uid-result"></div>
            </div>`;
        }
    }

    function lookupStudent(index, uid) {
        const result = document.getElementById('uid-result-' + index);
        clearTimeout(lookupTimers[index]);
        if (!uid || uid.length < 4) { result.style.display='none'; return; }
        lookupTimers[index] = setTimeout(() => {
            fetch('/api/student/info/' + encodeURIComponent(uid))
                .then(r => r.json())
                .then(data => {
                    if (data && data.full_name) {
                        result.className = 'uid-result uid-ok';
                        result.textContent = '✓ ' + data.full_name + (data.department ? ' — ' + data.department : '') + (data.level ? ' — ' + data.level : '');
                    } else {
                        result.className = 'uid-result uid-err';
                        result.textContent = '✗ الرقم الجامعي غير موجود';
                    }
                    result.style.display = 'block';
                })
                .catch(() => { result.style.display='none'; });
        }, 500);
    }
</script>
@endpush
