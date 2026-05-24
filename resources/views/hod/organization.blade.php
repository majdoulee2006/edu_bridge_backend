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

    .dept-btn, .year-btn {
        padding: 0.5rem 1.5rem;
        border-radius: 2rem;
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-secondary);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .dept-btn.active, .year-btn.active {
        background-color: var(--accent-color);
        color: #1a1a1a;
        border-color: var(--accent-color);
    }
</style>
@endpush

@section('content')
    <p class="page-subtitle">إدارة الجداول الأكاديمية</p>

    @if (session('success'))
        <div style="background-color: hsl(120, 70%, 95%); color: hsl(120, 50%, 30%); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background-color: hsl(0, 70%, 95%); color: hsl(0, 50%, 30%); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            {{ session('error') }}
        </div>
    @endif

    <div class="type-switcher">
        <button class="type-btn active" id="btn-schedules" onclick="switchTab('schedules')"><i class="fa-solid fa-graduation-cap"></i> جدول دراسي</button>
        <button class="type-btn" id="btn-exams" onclick="switchTab('exams')"><i class="fa-solid fa-file-pen"></i> جدول امتحاني</button>
    </div>

    <!-- Weekly Schedule Tab Content -->
    <div id="tab-schedules" class="tab-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h4 style="font-weight: 800; font-size: 1.25rem;">الجدول الدراسي الأسبوعي</h4>
            <button onclick="openModal('schedule-modal')" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-plus"></i> إضافة حصة دراسية
            </button>
        </div>

        <!-- Departments Navigation -->
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem; overflow-x: auto; padding-bottom: 0.5rem;">
            <button class="dept-btn active" onclick="selectDept('اتصالات', this)">اتصالات</button>
            <button class="dept-btn" onclick="selectDept('معلوماتية', this)">معلوماتية</button>
            <button class="dept-btn" onclick="selectDept('الكترون', this)">الكترون</button>
            <button class="dept-btn" onclick="selectDept('ذكاء', this)">ذكاء</button>
        </div>

        <!-- Years Navigation -->
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            <button class="year-btn active" onclick="selectYear('سنة أولى', this)">سنة أولى</button>
            <button class="year-btn" onclick="selectYear('سنة ثانية', this)">سنة ثانية</button>
        </div>

        <div style="overflow-x: auto; background-color: var(--bg-secondary); border-radius: 1.5rem; box-shadow: var(--shadow); padding: 1rem;">
            <table style="width: 100%; border-collapse: collapse; text-align: center; min-width: 800px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary); font-size: 0.9rem;">
                        <th style="padding: 1rem; text-align: right; width: 10%;">اليوم / الحصة</th>
                        <th style="padding: 1rem; width: 18%;">الأولى<br><small>08:00 - 09:30</small></th>
                        <th style="padding: 1rem; width: 18%;">الثانية<br><small>09:30 - 11:00</small></th>
                        <th style="padding: 1rem; width: 18%;">الثالثة<br><small>11:00 - 12:30</small></th>
                        <th style="padding: 1rem; width: 18%;">الرابعة<br><small>12:30 - 14:00</small></th>
                        <th style="padding: 1rem; width: 18%;">الخامسة<br><small>14:00 - 15:30</small></th>
                    </tr>
                </thead>
                <tbody id="grid-body">
                    <!-- Javascript will render grid here -->
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

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">القسم</label>
                        <select name="department" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                            <option value="اتصالات">اتصالات</option>
                            <option value="معلوماتية">معلوماتية</option>
                            <option value="الكترون">الكترون</option>
                            <option value="ذكاء">ذكاء</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">السنة الدراسية</label>
                        <select name="year" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                            <option value="سنة أولى">سنة أولى</option>
                            <option value="سنة ثانية">سنة ثانية</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">اليوم</label>
                        <select name="day" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                            <option value="Sunday">الأحد</option>
                            <option value="Monday">الاثنين</option>
                            <option value="Tuesday">الثلاثاء</option>
                            <option value="Wednesday">الأربعاء</option>
                            <option value="Thursday">الخميس</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">رقم الحصة</label>
                        <select name="period" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                            <option value="1">الحصة الأولى (8:00 - 9:30)</option>
                            <option value="2">الحصة الثانية (9:30 - 11:00)</option>
                            <option value="3">الحصة الثالثة (11:00 - 12:30)</option>
                            <option value="4">الحصة الرابعة (12:30 - 14:00)</option>
                            <option value="5">الحصة الخامسة (14:00 - 15:30)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">القاعة</label>
                    <input type="text" name="room" placeholder="مثال: A1" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
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
    const allSchedules = @json($schedules);
    const csrfToken = '{{ csrf_token() }}';

    let currentDept = 'اتصالات';
    let currentYear = 'سنة أولى';

    function selectDept(dept, btnElement) {
        currentDept = dept;
        document.querySelectorAll('.dept-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');
        renderGrid();
    }

    function selectYear(year, btnElement) {
        currentYear = year;
        document.querySelectorAll('.year-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');
        renderGrid();
    }

    function renderGrid() {
        const tbody = document.getElementById('grid-body');
        tbody.innerHTML = '';
        
        const classGroup = currentDept + ' - ' + currentYear;
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        const dayNames = {'Sunday':'الأحد', 'Monday':'الاثنين', 'Tuesday':'الثلاثاء', 'Wednesday':'الأربعاء', 'Thursday':'الخميس'};
        const periodStarts = ['08:00:00', '09:30:00', '11:00:00', '12:30:00', '14:00:00'];

        days.forEach(day => {
            let tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--border-color)';
            
            let dayTd = document.createElement('td');
            dayTd.style.padding = '1rem';
            dayTd.style.fontWeight = 'bold';
            dayTd.style.textAlign = 'right';
            dayTd.innerText = dayNames[day];
            tr.appendChild(dayTd);

            periodStarts.forEach(time => {
                let td = document.createElement('td');
                td.style.padding = '1rem';
                td.style.verticalAlign = 'top';

                // Find schedule (database might return "08:00:00" or "08:00")
                const timePrefix = time.substring(0, 5);
                const sch = allSchedules.find(s => {
                    if(!s.start_time) return false;
                    return s.day === day && s.start_time.startsWith(timePrefix) && s.class_group === classGroup;
                });
                
                if (sch) {
                    td.innerHTML = `
                        <div style="background-color: #eff6ff; padding: 0.75rem; border-radius: 0.5rem; border-right: 3px solid #3b82f6; text-align: right; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <div style="font-weight: 800; color: #1e40af; font-size: 0.95rem; margin-bottom: 0.25rem;">${sch.course_title}</div>
                            <div style="font-size: 0.8rem; color: #475569; margin-bottom: 0.25rem;"><i class="fa-solid fa-user-tie"></i> ${sch.teacher_name || 'غير محدد'}</div>
                            <div style="font-size: 0.8rem; color: #475569; margin-bottom: 0.5rem;"><i class="fa-solid fa-door-open"></i> قاعة: ${sch.room}</div>
                            <form action="/hod/organization/schedule/delete/${sch.schedule_id}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الحصة؟')">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size: 0.8rem; padding: 0;"><i class="fa-solid fa-trash-can"></i> حذف</button>
                            </form>
                        </div>
                    `;
                } else {
                    td.innerHTML = `<span style="color: #cbd5e1;">-</span>`;
                }
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
    }

    // Initial render
    document.addEventListener('DOMContentLoaded', () => {
        renderGrid();
    });

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
