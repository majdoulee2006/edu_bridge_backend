@extends('layouts.hod')

@section('title', 'الحسابات')

@push('styles')
<style>
    .page-subtitle {
        color: var(--text-secondary);
        font-size: 1rem;
        margin-top: -1.5rem;
        margin-bottom: 2rem;
    }

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
        font-size: 1.1rem;
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
    
    .type-btn.active {
        background-color: var(--accent-color);
        color: #1a1a1a;
    }

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
    }

    .add-btn:hover {
        border-color: var(--accent-color);
    }

    .add-icon {
        background-color: #fefce8;
        color: #ca8a04;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .account-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: var(--shadow);
        position: relative;
    }

    .account-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .account-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .avatar-blue { background-color: #dbeafe; color: #1d4ed8; }
    .avatar-purple { background-color: #f3e8ff; color: #7e22ce; }
    .avatar-orange { background-color: #ffedd5; color: #c2410c; }

    .account-info h4 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .account-info p {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .menu-dots {
        position: absolute;
        top: 1.5rem;
        left: 1.5rem;
        color: var(--text-secondary);
        cursor: pointer;
    }

    .chips-container {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .chip {
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        background-color: var(--bg-primary);
        color: var(--text-secondary);
    }
    
    .chip-highlight {
        background-color: #fefce8;
        color: #ca8a04;
        border: 1px solid #fef08a;
    }

    /* Modal styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .modal-overlay.active {
        opacity: 1;
        pointer-events: auto;
    }
    .modal-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 2.5rem;
        width: 90%;
        max-width: 500px;
        box-shadow: var(--shadow);
        transform: translateY(20px);
        transition: transform 0.3s ease;
    }
    .modal-overlay.active .modal-card {
        transform: translateY(0);
    }
</style>
@endpush

@section('content')
    <p class="page-subtitle">إدارة المستخدمين والصلاحيات</p>

    <div class="type-switcher">
        <button class="type-btn active" id="btn-teachers" onclick="switchTab('teachers')"><i class="fa-solid fa-graduation-cap"></i> مدرب</button>
        <button class="type-btn" id="btn-students" onclick="switchTab('students')"><i class="fa-solid fa-user-group"></i> طالب</button>
    </div>

    <button id="add-account-btn" class="add-btn" onclick="openAddModal()">
        <div class="add-icon"><i class="fa-solid fa-plus"></i></div>
        <span id="add-btn-text">إضافة حساب مدرب جديد</span>
    </button>

    <!-- Tab 1: Teachers List -->
    <div id="tab-teachers" class="tab-content">
        @forelse($teachers as $index => $teacher)
        @php
            $colors = ['blue', 'purple', 'orange'];
            $colorClass = 'avatar-' . $colors[$index % count($colors)];
            $initial = mb_substr($teacher->full_name, 0, 1, 'UTF-8');
        @endphp
        <!-- Account Card -->
        <div class="account-card">
            <form action="{{ route('hod.accounts.delete', $teacher->user_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا الحساب؟')" style="position: absolute; top: 1.5rem; left: 1.5rem;">
                @csrf
                <button type="submit" style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-size: 1.1rem;">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </form>
            <div class="account-header">
                <div class="account-avatar {{ $colorClass }}">{{ $initial }}</div>
                <div class="account-info">
                    <h4>{{ $teacher->full_name }}</h4>
                    <p>قسم {{ $teacher->specialization ?? 'عام' }} | اسم المستخدم: {{ $teacher->username }}</p>
                </div>
            </div>
            <div class="chips-container">
                @forelse($teacher->courses ?? [] as $course)
                    <span class="chip">{{ $course }}</span>
                @empty
                    <span class="chip" style="background-color: transparent; border: 1px dashed var(--border-color);">لا يوجد مواد مسندة</span>
                @endforelse
            </div>
            
            <!-- Advisor controls -->
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.9rem; color: var(--text-secondary);">
                    @if($teacher->is_advisor)
                        <span style="color: #10b981;"><i class="fa-solid fa-check-circle"></i> مربي لـ: {{ $teacher->advisor_course_title }}</span>
                    @else
                        <span><i class="fa-solid fa-times-circle"></i> ليس مربي دورة</span>
                    @endif
                </div>
                <button onclick="openAdvisorModal({{ $teacher->teacher_id }}, '{{ $teacher->full_name }}')" class="btn" style="background-color: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-user-tie"></i> تعيين مربي
                </button>
            </div>
        </div>
        @empty
        <div class="account-card" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
            لا توجد حسابات مدربين حالياً.
        </div>
        @endforelse
    </div>

    <!-- Tab 2: Students List -->
    <div id="tab-students" class="tab-content" style="display: none;">
        @forelse($students as $index => $student)
        @php
            $colors = ['blue', 'purple', 'orange'];
            $colorClass = 'avatar-' . $colors[$index % count($colors)];
            $initial = mb_substr($student->full_name, 0, 1, 'UTF-8');
        @endphp
        <!-- Account Card -->
        <div class="account-card">
            <form action="{{ route('hod.accounts.delete', $student->user_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا الحساب؟')" style="position: absolute; top: 1.5rem; left: 1.5rem;">
                @csrf
                <button type="submit" style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-size: 1.1rem;">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </form>
            <div class="account-header">
                <div class="account-avatar {{ $colorClass }}">{{ $initial }}</div>
                <div class="account-info">
                    <h4>{{ $student->full_name }}</h4>
                    <p>رمز الطالب: {{ $student->student_code }} | المستوى: {{ $student->level ?? 'غير محدد' }}</p>
                </div>
            </div>
            <div class="chips-container">
                <span class="chip">اسم المستخدم: {{ $student->username }}</span>
                @if($student->phone)
                    <span class="chip">{{ $student->phone }}</span>
                @endif
                @if($student->birth_date)
                    <span class="chip">تاريخ الميلاد: {{ $student->birth_date }}</span>
                @endif
            </div>
        </div>
        @empty
        <div class="account-card" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
            لا توجد حسابات طلاب حالياً.
        </div>
        @endforelse
    </div>

    <!-- Add Teacher Modal -->
    <div id="teacher-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center;">إضافة حساب مدرب جديد</h4>
            <form action="{{ route('hod.accounts.store_teacher') }}" method="POST">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">الاسم الكامل</label>
                    <input type="text" name="full_name" required placeholder="مثال: د. أحمد الرواد" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">اسم المستخدم</label>
                    <input type="text" name="username" required placeholder="مثال: ahmed_teacher" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">البريد الإلكتروني</label>
                        <input type="email" name="email" placeholder="teacher@domain.com" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">رقم الهاتف</label>
                        <input type="text" name="phone" placeholder="09xxxxxxx" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">التخصص / القسم</label>
                    <input type="text" name="specialization" required placeholder="مثال: هندسة البرمجيات" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">كلمة المرور</label>
                    <input type="password" name="password" minlength="6" required placeholder="••••••••" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; flex: 1; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem;">إضافة وحفظ</button>
                    <button type="button" onclick="closeModal('teacher-modal')" class="btn" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); flex: 1; padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="student-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center;">إضافة حساب طالب جديد</h4>
            <form action="{{ route('hod.accounts.store_student') }}" method="POST">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">الاسم الكامل</label>
                    <input type="text" name="full_name" required placeholder="مثال: يوسف المحمد" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">اسم المستخدم</label>
                    <input type="text" name="username" required placeholder="مثال: yousef_student" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">رمز الطالب (كود)</label>
                        <input type="text" name="student_code" required placeholder="مثال: STD005" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">المستوى</label>
                        <input type="text" name="level" placeholder="مثال: السنة الأولى" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">تاريخ الميلاد</label>
                        <input type="date" name="birth_date" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">رقم الهاتف</label>
                        <input type="text" name="phone" placeholder="09xxxxxxx" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">البريد الإلكتروني</label>
                    <input type="email" name="email" placeholder="student@domain.com" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">كلمة المرور</label>
                    <input type="password" name="password" minlength="6" required placeholder="••••••••" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; flex: 1; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem;">إضافة وحفظ</button>
                    <button type="button" onclick="closeModal('student-modal')" class="btn" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); flex: 1; padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Advisor Modal -->
    <div id="advisor-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; text-align: center;">إعداد مربي الدورة</h4>
            <p id="advisor-teacher-name" style="text-align: center; color: var(--text-secondary); margin-bottom: 1.5rem;"></p>
            
            <form action="{{ route('hod.accounts.advisor') }}" method="POST">
                @csrf
                <input type="hidden" name="teacher_id" id="advisor-teacher-id">
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">اختر الدورة / القاعة</label>
                    <select name="course_id" id="advisor-course-id" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                        <option value="">-- اختر الدورة --</option>
                        @foreach($all_courses ?? [] as $course)
                            <option value="{{ $course->course_id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                    <small style="color: var(--text-secondary); margin-top: 0.5rem; display: block;">ملاحظة: المعلم يمكن أن يكون مربياً لدورة واحدة فقط.</small>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" name="action" value="assign" class="btn" style="background-color: #10b981; color: white; flex: 1; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem;">تفعيل كمربي</button>
                    <button type="submit" name="action" value="remove" class="btn" style="background-color: #ef4444; color: white; flex: 1; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem;">إلغاء المربي</button>
                </div>
                <div style="margin-top: 1rem;">
                    <button type="button" onclick="closeModal('advisor-modal')" class="btn" style="width: 100%; background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">إغلاق</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    let activeTab = 'teachers';

    function switchTab(tab) {
        activeTab = tab;
        if (tab === 'teachers') {
            document.getElementById('tab-teachers').style.display = 'block';
            document.getElementById('tab-students').style.display = 'none';
            document.getElementById('btn-teachers').classList.add('active');
            document.getElementById('btn-students').classList.remove('active');
            document.getElementById('add-btn-text').innerText = 'إضافة حساب مدرب جديد';
        } else {
            document.getElementById('tab-teachers').style.display = 'none';
            document.getElementById('tab-students').style.display = 'block';
            document.getElementById('btn-teachers').classList.remove('active');
            document.getElementById('btn-students').classList.add('active');
            document.getElementById('add-btn-text').innerText = 'إضافة حساب طالب جديد';
        }
    }

    function openAddModal() {
        if (activeTab === 'teachers') {
            openModal('teacher-modal');
        } else {
            openModal('student-modal');
        }
    }

    function openAdvisorModal(teacherId, teacherName) {
        document.getElementById('advisor-teacher-id').value = teacherId;
        document.getElementById('advisor-teacher-name').innerText = "المعلم: " + teacherName;
        openModal('advisor-modal');
    }

    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
</script>
@endpush
