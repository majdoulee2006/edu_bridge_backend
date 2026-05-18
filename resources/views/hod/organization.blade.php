@extends('layouts.hod')

@section('title', 'التنظيم')

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
        padding: 1rem;
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
        background-color: var(--bg-primary);
        color: var(--text-primary);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .action-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
        cursor: pointer;
        transition: transform 0.2s;
        text-decoration: none;
        color: inherit;
    }
    
    .action-card:hover {
        transform: translateY(-2px);
    }
    
    .action-icon {
        width: 60px;
        height: 60px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    
    .icon-view { background-color: #eff6ff; color: #3b82f6; }
    .icon-create { background-color: #fefce8; color: #ca8a04; }
    .icon-edit { background-color: #faf5ff; color: #a855f7; }
    
    .action-content h3 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .action-content p {
        color: var(--text-secondary);
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    .arrow-icon {
        margin-right: auto;
        color: var(--text-secondary);
        font-size: 1.25rem;
    }
    
    /* modal overlay styles */
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
        max-width: 550px;
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
    <p class="page-subtitle">إدارة الجداول الأكاديمية</p>

    <div class="type-switcher">
        <button class="type-btn active" id="btn-schedules" onclick="switchTab('schedules')"><i class="fa-solid fa-graduation-cap"></i> جدول دراسي</button>
        <button class="type-btn" id="btn-exams" onclick="switchTab('exams')"><i class="fa-solid fa-file-pen"></i> جدول امتحاني</button>
    </div>

    <!-- Weekly Schedule Tab Content -->
    <div id="tab-schedules" class="tab-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h4 style="font-weight: 800; font-size: 1.25rem;">الجدول الدراسي الأسبوعي الحالي</h4>
            <button onclick="openModal('schedule-modal')" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-plus"></i> إضافة حصة دراسية
            </button>
        </div>

        <div style="overflow-x: auto; background-color: var(--bg-secondary); border-radius: 1.5rem; box-shadow: var(--shadow); padding: 1rem;">
            <table style="width: 100%; border-collapse: collapse; text-align: right;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary); font-size: 0.9rem;">
                        <th style="padding: 1rem;">اليوم</th>
                        <th style="padding: 1rem;">التوقيت</th>
                        <th style="padding: 1rem;">المادة</th>
                        <th style="padding: 1rem;">المدرب</th>
                        <th style="padding: 1rem;">القاعة</th>
                        <th style="padding: 1rem;">الشعبة</th>
                        <th style="padding: 1rem; text-align: center;">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $s)
                    <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.95rem;">
                        <td style="padding: 1rem; font-weight: bold;">
                            @switch($s->day)
                                @case('Sunday') الأحد @break
                                @case('Monday') الاثنين @break
                                @case('Tuesday') الثلاثاء @break
                                @case('Wednesday') الأربعاء @break
                                @case('Thursday') الخميس @break
                                @case('Friday') الجمعة @break
                                @case('Saturday') السبت @break
                                @default {{ $s->day }}
                            @endswitch
                        </td>
                        <td style="padding: 1rem; color: var(--text-secondary);" dir="ltr">{{ \Carbon\Carbon::parse($s->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('h:i A') }}</td>
                        <td style="padding: 1rem;">{{ $s->course_title }}</td>
                        <td style="padding: 1rem;">{{ $s->teacher_name ?? 'غير محدد' }}</td>
                        <td style="padding: 1rem;"><span style="background-color: #f1f5f9; color: #334155; padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: bold;">{{ $s->room }}</span></td>
                        <td style="padding: 1rem; color: var(--text-secondary);">{{ $s->class_group ?? '-' }}</td>
                        <td style="padding: 1rem; text-align: center;">
                            <form action="{{ route('hod.organization.delete_schedule', $s->schedule_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذه الحصة؟')">
                                @csrf
                                <button type="submit" style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-size: 1rem;"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">لا توجد حصص دراسية مضافة حالياً.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Exams Tab Content -->
    <div id="tab-exams" class="tab-content" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h4 style="font-weight: 800; font-size: 1.25rem;">الجدول الامتحاني الحالي</h4>
            <button onclick="openModal('exam-modal')" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-plus"></i> إضافة امتحان جديد
            </button>
        </div>

        <div style="overflow-x: auto; background-color: var(--bg-secondary); border-radius: 1.5rem; box-shadow: var(--shadow); padding: 1rem;">
            <table style="width: 100%; border-collapse: collapse; text-align: right;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary); font-size: 0.9rem;">
                        <th style="padding: 1rem;">الامتحان</th>
                        <th style="padding: 1rem;">المادة</th>
                        <th style="padding: 1rem;">التاريخ والتوقيت</th>
                        <th style="padding: 1rem;">القاعة</th>
                        <th style="padding: 1rem;">الشعبة</th>
                        <th style="padding: 1rem; text-align: center;">الدرجة الكبرى</th>
                        <th style="padding: 1rem; text-align: center;">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exams as $e)
                    <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.95rem;">
                        <td style="padding: 1rem; font-weight: bold;">{{ $e->exam_name }}</td>
                        <td style="padding: 1rem;">{{ $e->course_title }}</td>
                        <td style="padding: 1rem; color: var(--text-secondary);" dir="ltr">{{ \Carbon\Carbon::parse($e->exam_date)->format('Y-m-d h:i A') }}</td>
                        <td style="padding: 1rem;"><span style="background-color: #f1f5f9; color: #334155; padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: bold;">{{ $e->room ?? '-' }}</span></td>
                        <td style="padding: 1rem; color: var(--text-secondary);">{{ $e->class_group ?? '-' }}</td>
                        <td style="padding: 1rem; text-align: center; font-weight: bold; color: var(--accent-color);">{{ $e->max_score }}</td>
                        <td style="padding: 1rem; text-align: center;">
                            <form action="{{ route('hod.organization.delete_exam', $e->exam_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا الامتحان؟')">
                                @csrf
                                <button type="submit" style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-size: 1rem;"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">لا توجد امتحانات مضافة حالياً.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div id="schedule-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center;">إضافة حصة دراسية جديدة</h4>
            <form action="{{ route('hod.organization.store_schedule') }}" method="POST">
                @csrf
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">المادة الدراسية</label>
                    <select name="course_id" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                        @foreach($courses as $c)
                            <option value="{{ $c->course_id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">المدرب (الأستاذ)</label>
                    <select name="teacher_id" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                        <option value="">غير محدد</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->teacher_id }}">{{ $t->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">اليوم</label>
                    <select name="day" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                        <option value="Sunday">الأحد</option>
                        <option value="Monday">الاثنين</option>
                        <option value="Tuesday">الثلاثاء</option>
                        <option value="Wednesday">الأربعاء</option>
                        <option value="Thursday">الخميس</option>
                        <option value="Friday">الجمعة</option>
                        <option value="Saturday">السبت</option>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">وقت البدء</label>
                        <input type="time" name="start_time" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">وقت الانتهاء</label>
                        <input type="time" name="end_time" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">القاعة</label>
                        <input type="text" name="room" placeholder="مثال: A1" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">الشعبة / المجموعة</label>
                        <input type="text" name="class_group" placeholder="مثال: شعبة 1" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; flex: 1; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem;">حفظ</button>
                    <button type="button" onclick="closeModal('schedule-modal')" class="btn" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); flex: 1; padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Exam Modal -->
    <div id="exam-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center;">إضافة امتحان جديد</h4>
            <form action="{{ route('hod.organization.store_exam') }}" method="POST">
                @csrf
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">اسم الامتحان</label>
                    <input type="text" name="exam_name" placeholder="مثال: الامتحان النصفي" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">المادة الدراسية</label>
                    <select name="course_id" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                        @foreach($courses as $c)
                            <option value="{{ $c->course_id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">تاريخ ووقت الامتحان</label>
                    <input type="datetime-local" name="exam_date" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">القاعة</label>
                        <input type="text" name="room" placeholder="مثال: مدرج 2" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">الشعبة</label>
                        <input type="text" name="class_group" placeholder="مثال: شعبة 2" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">الدرجة الكبرى</label>
                        <input type="number" name="max_score" value="100" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; flex: 1; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem;">حفظ</button>
                    <button type="button" onclick="closeModal('exam-modal')" class="btn" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); flex: 1; padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function switchTab(tab) {
        if (tab === 'schedules') {
            document.getElementById('tab-schedules').style.display = 'block';
            document.getElementById('tab-exams').style.display = 'none';
            document.getElementById('btn-schedules').classList.add('active');
            document.getElementById('btn-exams').classList.remove('active');
        } else {
            document.getElementById('tab-schedules').style.display = 'none';
            document.getElementById('tab-exams').style.display = 'block';
            document.getElementById('btn-schedules').classList.remove('active');
            document.getElementById('btn-exams').classList.add('active');
        }
    }

    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
</script>
@endpush
