@extends('layouts.hod')

@section('title', __('messages.organization'))

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

    .exam-filter-btn {
        padding: 0.5rem 1.5rem;
        border-radius: 2rem;
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-secondary);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        font-family: inherit;
        font-size: 0.9rem;
        white-space: nowrap;
    }
    .exam-filter-btn.active {
        background-color: var(--accent-color);
        color: #1a1a1a;
        border-color: var(--accent-color);
    }
    .exam-filter-label {
        font-size: 0.82rem;
        color: var(--text-secondary);
        font-weight: 700;
        display: block;
        margin-bottom: 0.4rem;
    }
</style>
@endpush

@section('content')
    <p class="page-subtitle">{{ __('messages.manage_academic_schedules') }}</p>

    <div class="type-switcher">
        <button class="type-btn active" id="btn-schedules" onclick="switchTab('schedules')"><i class="fa-solid fa-graduation-cap"></i> {{ __('messages.study_schedule_tab') }}</button>
        <button class="type-btn" id="btn-exams" onclick="switchTab('exams')"><i class="fa-solid fa-file-pen"></i> {{ __('messages.exam_schedule_tab') }}</button>
        <button class="type-btn" id="btn-weights" onclick="switchTab('weights')"><i class="fa-solid fa-balance-scale"></i> {{ __('messages.course_weights_tab') }}</button>
    </div>

    <!-- Weekly Schedule Tab Content -->
    <div id="tab-schedules" class="tab-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h4 style="font-weight: 800; font-size: 1.25rem; margin-bottom: 0.25rem;">{{ __('messages.weekly_study_schedule') }}</h4>
                <span style="color: var(--text-secondary); font-size: 0.85rem;">نظام الحصص المرن والمحاضرات المتتالية وفق لوائح معهد دمشق المتوسط (DTC)</span>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <button type="button" onclick="openStructureModal()" class="btn" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; padding: 0.55rem 1.15rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25); transition: transform 0.2s;">
                    <i class="fa-solid fa-sliders"></i> إعداد هيكل وتوقيت البرنامج الأسبوعي
                </button>
                <button onclick="openModal('schedule-modal')" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.55rem 1.15rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-plus"></i> {{ __('messages.add_schedule_slot') }}
                </button>
            </div>
        </div>

        <!-- Departments Navigation -->
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem; overflow-x: auto; padding-bottom: 0.5rem;">
            @foreach($branches as $index => $b)
                <button class="dept-btn {{ $index === 0 ? 'active' : '' }}" onclick="selectDept('{{ $b->name }}', this)">{{ $b->name }}</button>
            @endforeach
        </div>

        <!-- Years Navigation -->
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            <button class="year-btn active" onclick="selectYear('سنة أولى', this)">{{ __('messages.first_year') }}</button>
            <button class="year-btn" onclick="selectYear('سنة ثانية', this)">{{ __('messages.second_year') }}</button>
        </div>

        <div style="overflow-x: auto; background-color: var(--bg-secondary); border-radius: 1.5rem; box-shadow: var(--shadow); padding: 1rem;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0; text-align: center; min-width: 950px;" id="schedule-main-table">
                <thead id="grid-header">
                    <!-- Dynamic header generated by JavaScript based on dayTimelines / structure -->
                </thead>
                <tbody id="grid-body">
                    <!-- Javascript will render grid here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Course Weights Tab Content -->
    <div id="tab-weights" class="tab-content" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="font-weight: 800; font-size: 1.25rem;">{{ __('messages.course_weights_tab') }}</h4>
            <span style="color: var(--text-secondary); font-size: 0.9rem;">{{ __('messages.weights_mgmt_desc') }}</span>
        </div>

        {{-- فلتر القسم --}}
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem; overflow-x: auto; padding-bottom: 0.5rem;">
            @foreach($branches as $index => $b)
                <button class="dept-btn {{ $index === 0 ? 'active' : '' }}" onclick="selectWeightDept('{{ $b->name }}', this)">{{ $b->name }}</button>
            @endforeach
        </div>

        {{-- فلتر السنة --}}
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            <button class="year-btn active" onclick="selectWeightYear('سنة أولى', this)">{{ __('messages.first_year') }}</button>
            <button class="year-btn" onclick="selectWeightYear('سنة ثانية', this)">{{ __('messages.second_year') }}</button>
        </div>

        <div style="background-color: var(--bg-secondary); border-radius: 1.5rem; box-shadow: var(--shadow); padding: 1.5rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;" id="weights-grid">
                @forelse($allCourses as $course)
                    <div class="weight-card" data-dept="{{ $course->branch_name }}" data-year="{{ $course->year == 2 ? 'سنة ثانية' : 'سنة أولى' }}" style="background: var(--bg-primary); padding: 1.25rem; border-radius: 1rem; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <h5 style="font-weight: 800; font-size: 1.1rem; color: var(--text-primary); margin-bottom: 0.25rem;">{{ $course->title }}</h5>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <span style="font-size: 0.75rem; background: var(--bg-secondary); padding: 0.2rem 0.6rem; border-radius: 1rem; color: var(--text-secondary);"><i class="fa-solid fa-clock"></i> {{ $course->hours }} {{ __('messages.hour') ?? 'ساعة' }}</span>
                                <span style="font-size: 0.75rem; background: var(--bg-secondary); padding: 0.2rem 0.6rem; border-radius: 1rem; color: var(--text-secondary);"><i class="fa-solid fa-layer-group"></i> {{ $course->semester_id }}</span>
                            </div>
                        </div>
                        
                        <form action="{{ route('hod.organization.course_weight', $course->course_id) }}" method="POST" style="display: flex; gap: 0.5rem; align-items: flex-end;">
                            @csrf
                            <div style="flex: 1;">
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.4rem;">{{ __('messages.course_weight_label') }}</label>
                                <input type="number" name="weight" value="{{ $course->weight ?? 1 }}" min="1" required style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary); font-weight: 700; text-align: center;">
                            </div>
                            <button type="submit" style="background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.5rem; padding: 0.6rem 1rem; font-weight: 700; cursor: pointer; transition: transform 0.2s;">
                                <i class="fa-solid fa-save"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--text-secondary);">
                        <i class="fa-solid fa-book-open" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p style="font-weight: 700;">{{ __('messages.no_courses_available') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Exams Tab Content -->
    <div id="tab-exams" class="tab-content" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="font-weight: 800; font-size: 1.25rem;">{{ __('messages.exam_schedule_tab') }}</h4>
            <button onclick="openModal('exam-modal')" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-plus"></i> {{ __('messages.add_exam') }}
            </button>
        </div>

        {{-- فلتر القسم --}}
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem; overflow-x: auto; padding-bottom: 0.5rem;">
            @foreach($branches as $index => $b)
                <button class="dept-btn {{ $index === 0 ? 'active' : '' }}" onclick="selectExamDept('{{ $b->name }}', this)">{{ $b->name }}</button>
            @endforeach
        </div>

        {{-- فلتر السنة --}}
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            <button class="year-btn active" onclick="selectExamYear('سنة أولى', this)">{{ __('messages.first_year') }}</button>
            <button class="year-btn" onclick="selectExamYear('سنة ثانية', this)">{{ __('messages.second_year') }}</button>
        </div>

        <div style="overflow-x: auto; background-color: var(--bg-secondary); border-radius: 1.5rem; box-shadow: var(--shadow); padding: 1rem;">
            <table style="width: 100%; border-collapse: collapse; text-align: right;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary); font-size: 0.9rem;">
                        <th style="padding: 1rem;">{{ __('messages.course') ?? 'المادة' }}</th>
                        <th style="padding: 1rem;">{{ __('messages.date_and_time') }}</th>
                        <th style="padding: 1rem;">{{ __('messages.room_or_hall') }}</th>
                        <th style="padding: 1rem; text-align: center;">{{ __('messages.max_score_label') }}</th>
                        <th style="padding: 1rem; text-align: center;">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="exam-table-body">
                    {{-- يُعبأ عبر JavaScript --}}
                </tbody>
            </table>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div id="schedule-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center;">{{ __('messages.add_new_schedule_slot') }}</h4>
            <form action="{{ route('hod.organization.store_schedule') }}" method="POST">
                @csrf
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.course') ?? 'المادة الدراسية' }}</label>
                    <select id="modal-course-select" name="course_id" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                        <option value="">{{ __('messages.select_course_option') }}</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.academic_semester') }}</label>
                    <select id="modal-semester-select" name="semester" onchange="populateScheduleModal()" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                        <option value="1">{{ __('messages.first_semester') }}</option>
                        <option value="2">{{ __('messages.second_semester') }}</option>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.department') }}</label>
                        <select name="department" id="modal-dept-select" onchange="onScheduleDeptOrYearChange()" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                            @foreach($branches as $b)
                                <option value="{{ $b->name }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.academic_year') }}</label>
                        <select name="year" id="modal-year-select" onchange="onScheduleDeptOrYearChange()" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                            <option value="سنة أولى" selected>{{ __('messages.first_year') }}</option>
                            <option value="سنة ثانية">{{ __('messages.second_year') }}</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="flex: 1.2;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.day_period') }}</label>
                        <select name="day" id="modal-day-select" onchange="updatePeriodRangeOptions()" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                            <option value="Sunday">{{ __('messages.day_sunday') }}</option>
                            <option value="Monday">{{ __('messages.day_monday') }}</option>
                            <option value="Tuesday">{{ __('messages.day_tuesday') }}</option>
                            <option value="Wednesday">{{ __('messages.day_wednesday') }}</option>
                            <option value="Thursday">{{ __('messages.day_thursday') }}</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">من الحصة</label>
                        <select name="from_period" id="modal-from-period-select" onchange="onFromPeriodChange()" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                            <!-- Filled dynamically -->
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">إلى الحصة</label>
                        <select name="to_period" id="modal-to-period-select" onchange="onPeriodRangeChange()" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                            <!-- Filled dynamically -->
                        </select>
                    </div>
                </div>

                {{-- شارة التوقيت المحسوب تلقائياً --}}
                <div id="modal-period-hint" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #93c5fd; color: #1e40af; border-radius: 0.75rem; padding: 0.6rem 1rem; margin-bottom: 1rem; font-size: 0.88rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-clock"></i> <span id="modal-period-hint-text">جاري احتساب التوقيت...</span>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.room_or_hall') }}</label>
                    <input type="text" name="room" placeholder="مثال: A1" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" id="modal-schedule-submit-btn" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; flex: 1; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem;">{{ __('messages.save') }}</button>
                    <button type="button" onclick="closeModal('schedule-modal')" class="btn" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); flex: 1; padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">{{ __('messages.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Exam Modal -->
    <div id="edit-exam-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center;">{{ __('messages.edit_exam_title') }}</h4>
            <form id="edit-exam-form" method="POST">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:bold; color:var(--text-secondary);">{{ __('messages.course') ?? 'المادة الدراسية' }}</label>
                    <select name="course_id" id="edit-exam-course" style="width:100%; padding:0.75rem; border-radius:0.75rem; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary);">
                        @foreach($courses as $c)
                            <option value="{{ $c->course_id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:bold; color:var(--text-secondary);">{{ __('messages.date_and_time') }}</label>
                    <input type="datetime-local" name="exam_date" id="edit-exam-date" style="width:100%; padding:0.75rem; border-radius:0.75rem; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary);">
                </div>
                <div style="display:flex; gap:1rem; margin-bottom:1.5rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:bold; color:var(--text-secondary);">{{ __('messages.room_or_hall') }}</label>
                        <input type="text" name="room" id="edit-exam-room" style="width:100%; padding:0.75rem; border-radius:0.75rem; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary);">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:bold; color:var(--text-secondary);">{{ __('messages.max_score_label') }}</label>
                        <input type="number" name="max_score" id="edit-exam-score" style="width:100%; padding:0.75rem; border-radius:0.75rem; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary);">
                    </div>
                </div>
                <div style="display:flex; gap:1rem;">
                    <button type="submit" style="background:var(--accent-color); color:#1a1a1a; flex:1; padding:0.75rem; border-radius:0.75rem; border:none; font-weight:700; cursor:pointer;">{{ __('messages.save') }}</button>
                    <button type="button" onclick="closeModal('edit-exam-modal')" style="background:transparent; border:1px solid var(--border-color); color:var(--text-primary); flex:1; padding:0.75rem; border-radius:0.75rem; font-weight:700; cursor:pointer;">{{ __('messages.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Exam Modal -->
    <div id="exam-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center;">{{ __('messages.add_new_exam_title') }}</h4>
            <form action="{{ route('hod.organization.store_exam') }}" method="POST">
                @csrf
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.exam_name_label') }}</label>
                    <input type="text" name="exam_name" placeholder="{{ __('messages.exam_name_placeholder') }}" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.academic_semester') }}</label>
                    <select id="exam-modal-semester-select" name="semester" onchange="populateExamModal()" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                        <option value="1">{{ __('messages.first_semester') }}</option>
                        <option value="2">{{ __('messages.second_semester') }}</option>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.department') }}</label>
                        <select id="exam-modal-dept-select" name="department" onchange="populateExamModal()" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                            @foreach($branches as $b)
                                <option value="{{ $b->name }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.academic_year') }}</label>
                        <select id="exam-modal-year-select" name="year" onchange="populateExamModal()" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                            <option value="سنة أولى" selected>{{ __('messages.first_year') }}</option>
                            <option value="سنة ثانية">{{ __('messages.second_year') }}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.course') ?? 'المادة الدراسية' }}</label>
                    <select id="exam-modal-course-select" name="course_id" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                        <option value="">{{ __('messages.select_course_option') }}</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.date_and_time') }}</label>
                    <input type="datetime-local" name="exam_date" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.room_or_hall') }}</label>
                        <input type="text" name="room" placeholder="مثال: مدرج 2" style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">{{ __('messages.max_score_label') }}</label>
                        <input type="number" name="max_score" value="100" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; flex: 1; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem;">{{ __('messages.save') }}</button>
                    <button type="button" onclick="closeModal('exam-modal')" class="btn" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); flex: 1; padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">{{ __('messages.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedule Structure Settings Modal (إعداد هيكل وتوقيت البرنامج الأسبوعي) -->
    <div id="schedule-structure-modal" class="modal-overlay" style="align-items: flex-start; padding: 2rem 1rem; overflow-y: auto;">
        <div class="modal-card" style="max-width: 880px; width: 95%; max-height: 90vh; overflow-y: auto; padding: 2rem; border-radius: 1.5rem; background: var(--bg-secondary); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
                <div>
                    <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.25rem;">
                        <i class="fa-solid fa-sliders" style="color: #3b82f6;"></i> إعداد وتحديد هيكل وتوقيت الحصص الأسبوعي
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 0.88rem;">
                        حدد نمط الدوام، مدة الحصة بالدقائق، ومواعيد الاستراحات (البريك) لكل مجموعة أيام. يمكنك إضافة شروط مختلفة لأيام معينة أو تحديد أيام العطلة.
                    </p>
                </div>
                <button type="button" onclick="closeModal('schedule-structure-modal')" style="background: none; border: none; font-size: 1.4rem; color: var(--text-secondary); cursor: pointer; padding: 0.25rem;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Cards Container -->
            <div id="structure-cards-container" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <!-- Cards dynamically rendered here -->
            </div>

            <!-- Add Card Button -->
            <div style="margin-top: 1.25rem; display: flex; justify-content: flex-start;">
                <button type="button" id="btn-add-structure-card" onclick="addNewStructureCard()" style="background-color: var(--bg-primary); border: 2px dashed #3b82f6; color: #2563eb; padding: 0.75rem 1.5rem; border-radius: 1rem; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                    <i class="fa-solid fa-plus-circle"></i> + إضافة بطاقة / شروط لأيام أخرى
                </button>
            </div>

            <!-- Live Timeline Preview Box -->
            <div id="structure-preview-box" style="margin-top: 1.75rem; background: var(--bg-primary); border-radius: 1rem; border: 1px solid var(--border-color); padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-eye" style="color: #10b981;"></i>
                        <span style="font-weight: 800; font-size: 0.95rem; color: var(--text-primary);">معاينة الحصص والبريكات الناتجة ليوم:</span>
                        <select id="preview-day-select" onchange="updateLivePreview()" style="padding: 0.35rem 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); font-size: 0.88rem; font-weight: 700;">
                            <option value="Sunday">الأحد</option>
                            <option value="Monday">الإثنين</option>
                            <option value="Tuesday">الثلاثاء</option>
                            <option value="Wednesday">الأربعاء</option>
                            <option value="Thursday">الخميس</option>
                        </select>
                    </div>
                    <span id="preview-holiday-badge" style="display: none; background: #fef3c7; color: #b45309; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.82rem; font-weight: 700;">🏖️ عطلة رسمية (لا يوجد حصص)</span>
                </div>
                <div id="preview-timeline-strips" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                    <!-- Filled by live JS -->
                </div>
            </div>

            <!-- Modal Actions -->
            <div style="display: flex; gap: 1rem; margin-top: 1.75rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                <button type="button" id="btn-save-structure" onclick="saveStructureToServer()" class="btn" style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; flex: 1.5; padding: 0.85rem; border-radius: 0.75rem; border: none; font-weight: 800; cursor: pointer; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);">
                    <i class="fa-solid fa-floppy-disk"></i> حفظ واعتماد هيكل التوقيت
                </button>
                <button type="button" onclick="closeModal('schedule-structure-modal')" class="btn" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); flex: 1; padding: 0.85rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">
                    {{ __('messages.cancel') }}
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    const allSchedules     = @json($schedules);
    const allCoursesData   = @json($allCourses);   // [{course_id, title, branch_name}]
    const allTeachersData  = @json($allTeachers);  // [{teacher_id, full_name, department}]
    const csrfToken        = '{{ csrf_token() }}';

    // هيكل البرنامج وتوقيت الأيام المعتمد
    let scheduleStructure = @json($scheduleStructure ?? []);
    let dayTimelines      = @json($dayTimelines ?? []);

    const allSupportedDays = [
        { key: 'Sunday', name: 'الأحد' },
        { key: 'Monday', name: 'الإثنين' },
        { key: 'Tuesday', name: 'الثلاثاء' },
        { key: 'Wednesday', name: 'الأربعاء' },
        { key: 'Thursday', name: 'الخميس' }
    ];

    const firstBranch = '{{ $branches->first()->name ?? "معلوماتية" }}';
    let currentDept   = firstBranch;
    let currentYear   = 'سنة أولى';

    function selectDept(dept, btnElement) {
        currentDept = dept;
        document.querySelectorAll('#tab-schedules .dept-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');
        const activeYearBtn = document.querySelector('#tab-schedules .year-btn.active');
        if (activeYearBtn) {
            currentYear = activeYearBtn.innerText.trim();
        }
        renderGrid();
        updatePeriodRangeOptions();
    }

    function selectYear(year, btnElement) {
        currentYear = year;
        document.querySelectorAll('.year-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');
        renderGrid();
        updatePeriodRangeOptions();
    }

    // =======================================================
    // 🌟 رسم وتحديث جدول الحصص الأسبوعي (Dynamic Grid)
    // =======================================================
    function renderGrid() {
        const thead = document.getElementById('grid-header');
        const tbody = document.getElementById('grid-body');
        if (!thead || !tbody) return;

        thead.innerHTML = '';
        tbody.innerHTML = '';

        const classGroup = currentDept + ' - ' + currentYear;

        // 1. حساب الحد الأقصى للحصص في الأيام الفعالة
        let maxSlots = 5;
        Object.keys(dayTimelines).forEach(day => {
            const tl = dayTimelines[day];
            if (tl && !tl.is_holiday && tl.periods) {
                const count = Object.keys(tl.periods).length;
                if (count > maxSlots) maxSlots = count;
            }
        });
        if (maxSlots > 9) maxSlots = 9;

        // 2. بناء رأس الجدول الديناميكي
        let headerTr = document.createElement('tr');
        headerTr.style.borderBottom = '2px solid var(--border-color)';
        headerTr.style.color = 'var(--text-secondary)';
        headerTr.style.fontSize = '0.9rem';

        let thDay = document.createElement('th');
        thDay.style.padding = '1rem';
        thDay.style.textAlign = 'right';
        thDay.style.width = '12%';
        thDay.innerText = 'اليوم / الحصة';
        headerTr.appendChild(thDay);

        for (let p = 1; p <= maxSlots; p++) {
            let th = document.createElement('th');
            th.style.padding = '0.85rem 0.5rem';
            th.style.fontWeight = '800';
            th.innerHTML = `الحصة ${p}`;
            headerTr.appendChild(th);
        }
        thead.appendChild(headerTr);

        // 3. بناء صفوف الأيام
        allSupportedDays.forEach(dayObj => {
            const dayKey = dayObj.key;
            const dayName = dayObj.name;
            const tl = dayTimelines[dayKey] || { is_holiday: false, periods: {} };

            let tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--border-color)';

            let dayTd = document.createElement('td');
            dayTd.style.padding = '1rem 0.75rem';
            dayTd.style.fontWeight = '800';
            dayTd.style.textAlign = 'right';
            dayTd.style.color = 'var(--text-primary)';
            dayTd.innerHTML = `<div>${dayName}</div>`;
            tr.appendChild(dayTd);

            // حالة اليوم عطلة رسمية
            if (tl.is_holiday) {
                let holTd = document.createElement('td');
                holTd.colSpan = maxSlots;
                holTd.style.padding = '1rem';
                holTd.style.backgroundColor = 'rgba(245, 158, 11, 0.08)';
                holTd.style.color = '#b45309';
                holTd.style.fontWeight = '700';
                holTd.style.borderRadius = '0.5rem';
                holTd.innerHTML = `<span>🏖️ عطلة أسبوعية رسمية (لا يوجد حصص)</span>`;
                tr.appendChild(holTd);
                tbody.appendChild(tr);
                return;
            }

            // جلب حصص هذا اليوم الخاصة بالشعبة المحددة
            const daySchedules = allSchedules.filter(s => s.day === dayKey && s.class_group === classGroup);

            // نمر على الحصص 1..maxSlots ونأخذ بالاعتبار الـ colspan للمحاضرات المتتالية
            let p = 1;
            while (p <= maxSlots) {
                // فحص هل الحصة موجودة في توقيت هذا اليوم
                const periodInfo = tl.periods ? tl.periods[p] : null;

                // هل تبدأ محاضرة عند هذه الحصة؟
                // نطابق إما من through from_period أو بمقارنة start_time
                const sch = daySchedules.find(s => {
                    if (s.from_period && s.from_period == p) return true;
                    if (!s.from_period && periodInfo && s.start_time && s.start_time.startsWith(periodInfo.start.substring(0, 5))) return true;
                    return false;
                });

                if (sch) {
                    const fromP = sch.from_period ? parseInt(sch.from_period) : p;
                    const toP   = sch.to_period ? parseInt(sch.to_period) : fromP;
                    const span  = Math.max(1, (toP - fromP + 1));

                    let td = document.createElement('td');
                    td.colSpan = span;
                    td.style.padding = '0.6rem 0.4rem';
                    td.style.verticalAlign = 'top';

                    const timeLabel = (sch.start_time ? sch.start_time.substring(0, 5) : '') + ' - ' + (sch.end_time ? sch.end_time.substring(0, 5) : '');
                    const periodLabel = span > 1 ? `الحصص (${fromP} - ${toP})` : `الحصة ${fromP}`;

                    td.innerHTML = `
                        <div style="background: linear-gradient(135deg, #eff6ff, #f8fafc); padding: 0.75rem 0.6rem; border-radius: 0.75rem; border-right: 4px solid #3b82f6; text-align: right; box-shadow: 0 2px 4px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; border-right-width: 4px; border-right-color: #3b82f6;">
                            <div style="font-weight: 800; color: #1e40af; font-size: 0.92rem; margin-bottom: 0.25rem;">${sch.course_title}</div>
                            <div style="display: flex; gap: 0.4rem; align-items: center; font-size: 0.78rem; color: #2563eb; font-weight: 700; margin-bottom: 0.35rem;">
                                <span style="background: #dbeafe; padding: 0.15rem 0.5rem; border-radius: 0.4rem;">${periodLabel}</span>
                                <span><i class="fa-solid fa-clock"></i> ${timeLabel}</span>
                            </div>
                            <div style="font-size: 0.8rem; color: #475569; margin-bottom: 0.2rem;"><i class="fa-solid fa-user-tie"></i> ${sch.teacher_name || 'غير محدد'}</div>
                            <div style="font-size: 0.8rem; color: #475569; margin-bottom: 0.4rem;"><i class="fa-solid fa-door-open"></i> قاعة: ${sch.room}</div>
                            <form action="/hod/organization/schedule/delete/${sch.schedule_id}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه المحاضرة؟')" style="margin: 0;">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size: 0.78rem; padding: 0; font-weight: 700;">
                                    <i class="fa-solid fa-trash-can"></i> حذف
                                </button>
                            </form>
                        </div>
                    `;
                    tr.appendChild(td);
                    p += span; // تجاوز الحصص التي تم دمجها
                } else {
                    let td = document.createElement('td');
                    td.style.padding = '0.8rem 0.4rem';
                    td.style.verticalAlign = 'middle';
                    if (periodInfo) {
                        td.innerHTML = `<span style="color: #cbd5e1; font-weight: 600;">-</span>`;
                    } else {
                        td.innerHTML = `<span style="color: #e2e8f0; font-size: 0.75rem;">انتهاء</span>`;
                    }
                    tr.appendChild(td);
                    p++;
                }
            }

            tbody.appendChild(tr);
        });
    }

    // =======================================================
    // 🌟 إعداد هيكل البرنامج والبطاقات (Structure Modal Logic)
    // =======================================================
    let localCards = [];

    function openStructureModal() {
        // نسخ الهيكل الحالي إلى مصفوفة محلية للتحرير
        if (scheduleStructure && scheduleStructure.length > 0) {
            localCards = JSON.parse(JSON.stringify(scheduleStructure));
        } else {
            localCards = [
                {
                    days: ['Sunday', 'Monday', 'Tuesday'],
                    is_holiday: false,
                    first_period_start: '08:15',
                    last_period_end: '15:35',
                    period_duration_minutes: 45,
                    break1_after_period: 3,
                    break1_duration_minutes: 20,
                    break2_after_period: 6,
                    break2_duration_minutes: 15
                },
                {
                    days: ['Wednesday'],
                    is_holiday: false,
                    first_period_start: '08:15',
                    last_period_end: '13:50',
                    period_duration_minutes: 45,
                    break1_after_period: 3,
                    break1_duration_minutes: 20,
                    break2_after_period: null,
                    break2_duration_minutes: 0
                },
                {
                    days: ['Thursday'],
                    is_holiday: true,
                    first_period_start: '08:15',
                    last_period_end: '14:00',
                    period_duration_minutes: 45,
                    break1_after_period: null,
                    break1_duration_minutes: 0,
                    break2_after_period: null,
                    break2_duration_minutes: 0
                }
            ];
        }

        renderStructureCards();
        updateLivePreview();
        openModal('schedule-structure-modal');
    }

    function renderStructureCards() {
        const container = document.getElementById('structure-cards-container');
        if (!container) return;
        container.innerHTML = '';

        localCards.forEach((card, index) => {
            const cardEl = document.createElement('div');
            cardEl.className = 'structure-card-item';
            cardEl.dataset.index = index;
            cardEl.style.background = 'var(--bg-primary)';
            cardEl.style.borderRadius = '1.25rem';
            cardEl.style.border = '1px solid var(--border-color)';
            cardEl.style.padding = '1.25rem';
            cardEl.style.boxShadow = '0 2px 4px rgba(0,0,0,0.03)';

            const cardTitle = `بطاقة نمط الدوام #${index + 1}`;
            const isHoliday = !!card.is_holiday;

            // بناء خانات اختيار الأيام
            let daysHtml = allSupportedDays.map(d => {
                const checked = (card.days && card.days.includes(d.key)) ? 'checked' : '';
                return `
                    <label style="display: flex; align-items: center; gap: 0.4rem; background: var(--bg-secondary); padding: 0.4rem 0.8rem; border-radius: 0.6rem; cursor: pointer; font-weight: 700; font-size: 0.88rem; border: 1px solid var(--border-color); user-select: none;">
                        <input type="checkbox" value="${d.key}" ${checked} onchange="onCardDaysChanged(${index}, this)">
                        <span>${d.name}</span>
                    </label>
                `;
            }).join('');

            cardEl.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="background: #3b82f6; color: #fff; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem;">${index + 1}</span>
                        <h4 style="font-weight: 800; font-size: 1.05rem; color: var(--text-primary); margin: 0;">${cardTitle}</h4>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 800; font-size: 0.9rem; color: #d97706;">
                            <input type="checkbox" ${isHoliday ? 'checked' : ''} onchange="toggleCardHoliday(${index}, this.checked)">
                            <span>🏖️ يوم عطلة دائم</span>
                        </label>
                        ${localCards.length > 1 ? `
                            <button type="button" onclick="removeStructureCard(${index})" style="background: none; border: none; color: #ef4444; font-size: 1.1rem; cursor: pointer;" title="حذف هذه البطاقة">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">الأيام التي ينطبق عليها هذا النمط:</label>
                    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                        ${daysHtml}
                    </div>
                </div>

                <div class="card-holiday-message" style="display: ${isHoliday ? 'block' : 'none'}; background: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 0.85rem 1rem; border-radius: 0.75rem; font-weight: 700; font-size: 0.9rem;">
                    🏖️ تم تحديد هذه الأيام كعطلة رسمية بالكامل؛ تم إقفال أوقات الحصص ولن يظهر فيها أي دوام بالجدول.
                </div>

                <div class="card-time-settings" style="display: ${isHoliday ? 'none' : 'block'};">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.35rem;">⏰ وقت بداية أول حصة</label>
                            <input type="time" value="${card.first_period_start || '08:15'}" onchange="updateCardField(${index}, 'first_period_start', this.value)" style="width: 100%; padding: 0.65rem; border-radius: 0.6rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); font-weight: 700;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.35rem;">⏰ وقت نهاية الدوام</label>
                            <input type="time" value="${card.last_period_end || '15:35'}" onchange="updateCardField(${index}, 'last_period_end', this.value)" style="width: 100%; padding: 0.65rem; border-radius: 0.6rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); font-weight: 700;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.35rem;">⏱️ مدة الحصة الواحدة (بالدقائق)</label>
                            <input type="number" min="20" max="120" value="${card.period_duration_minutes || 45}" onchange="updateCardField(${index}, 'period_duration_minutes', this.value)" style="width: 100%; padding: 0.65rem; border-radius: 0.6rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); font-weight: 700; text-align: center;">
                        </div>
                    </div>

                    <div style="background: var(--bg-secondary); padding: 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color);">
                        <h5 style="font-weight: 800; font-size: 0.9rem; color: var(--text-primary); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="fa-solid fa-mug-hot" style="color: #f59e0b;"></i> أوقات ومواعيد الاستراحات (البريك):
                        </h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.3rem;">الاستراحة الأولى بعد الحصة:</label>
                                <select onchange="updateCardField(${index}, 'break1_after_period', this.value)" style="width: 100%; padding: 0.55rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                                    <option value="2" ${card.break1_after_period == 2 ? 'selected' : ''}>بعد الحصة 2</option>
                                    <option value="3" ${card.break1_after_period == 3 ? 'selected' : ''}>بعد الحصة 3</option>
                                    <option value="4" ${card.break1_after_period == 4 ? 'selected' : ''}>بعد الحصة 4</option>
                                    <option value="" ${!card.break1_after_period ? 'selected' : ''}>بدون استراحة أولى</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.3rem;">مدة الاستراحة الأولى (دقائق):</label>
                                <input type="number" min="5" max="60" value="${card.break1_duration_minutes ?? 20}" onchange="updateCardField(${index}, 'break1_duration_minutes', this.value)" style="width: 100%; padding: 0.55rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700; text-align: center;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.3rem;">الاستراحة الثانية بعد الحصة:</label>
                                <select onchange="updateCardField(${index}, 'break2_after_period', this.value)" style="width: 100%; padding: 0.55rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                                    <option value="" ${!card.break2_after_period ? 'selected' : ''}>بدون استراحة ثانية</option>
                                    <option value="5" ${card.break2_after_period == 5 ? 'selected' : ''}>بعد الحصة 5</option>
                                    <option value="6" ${card.break2_after_period == 6 ? 'selected' : ''}>بعد الحصة 6</option>
                                    <option value="7" ${card.break2_after_period == 7 ? 'selected' : ''}>بعد الحصة 7</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.3rem;">مدة الاستراحة الثانية (دقائق):</label>
                                <input type="number" min="5" max="60" value="${card.break2_duration_minutes ?? 15}" onchange="updateCardField(${index}, 'break2_duration_minutes', this.value)" style="width: 100%; padding: 0.55rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700; text-align: center;">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(cardEl);
        });
    }

    function toggleCardHoliday(index, isChecked) {
        if (!localCards[index]) return;
        localCards[index].is_holiday = isChecked;
        renderStructureCards();
        updateLivePreview();
    }

    function onCardDaysChanged(cardIndex, checkboxEl) {
        const dayVal = checkboxEl.value;
        if (!localCards[cardIndex].days) localCards[cardIndex].days = [];

        if (checkboxEl.checked) {
            // إزالة اليوم من أي بطاقة أخرى منعاً للتكرار
            localCards.forEach((c, idx) => {
                if (idx !== cardIndex && c.days) {
                    c.days = c.days.filter(d => d !== dayVal);
                }
            });
            if (!localCards[cardIndex].days.includes(dayVal)) {
                localCards[cardIndex].days.push(dayVal);
            }
        } else {
            localCards[cardIndex].days = localCards[cardIndex].days.filter(d => d !== dayVal);
        }

        renderStructureCards();
        updateLivePreview();
    }

    function updateCardField(cardIndex, field, value) {
        if (!localCards[cardIndex]) return;
        localCards[cardIndex][field] = value;
        updateLivePreview();
    }

    function addNewStructureCard() {
        // العثور على الأيام التي لم تختر بعد في أي بطاقة
        const chosenDays = [];
        localCards.forEach(c => {
            if (c.days) chosenDays.push(...c.days);
        });
        const remainingDays = allSupportedDays.map(d => d.key).filter(k => !chosenDays.includes(k));

        localCards.push({
            days: remainingDays.length > 0 ? [remainingDays[0]] : [],
            is_holiday: false,
            first_period_start: '08:15',
            last_period_end: '14:30',
            period_duration_minutes: 45,
            break1_after_period: 3,
            break1_duration_minutes: 20,
            break2_after_period: null,
            break2_duration_minutes: 0
        });

        renderStructureCards();
        updateLivePreview();
    }

    function removeStructureCard(index) {
        if (localCards.length <= 1) return;
        localCards.splice(index, 1);
        renderStructureCards();
        updateLivePreview();
    }

    // =======================================================
    // 🌟 معاينة تفاعلية حية للحصص والبريكات في الـ Modal
    // =======================================================
    function updateLivePreview() {
        const previewSelect = document.getElementById('preview-day-select');
        const holidayBadge  = document.getElementById('preview-holiday-badge');
        const stripsBox     = document.getElementById('preview-timeline-strips');
        if (!previewSelect || !stripsBox) return;

        const day = previewSelect.value;
        const matchingCard = localCards.find(c => c.days && c.days.includes(day)) || localCards[0];

        if (!matchingCard) {
            stripsBox.innerHTML = '<span style="color: var(--text-secondary);">لا توجد إعدادات مخصصة</span>';
            return;
        }

        if (matchingCard.is_holiday) {
            if (holidayBadge) holidayBadge.style.display = 'inline-block';
            stripsBox.innerHTML = '<span style="color: #b45309; font-weight: 700; padding: 0.5rem;">🏖️ هذا اليوم عطلة رسمية كاملة (لا يوجد حصص).</span>';
            return;
        }

        if (holidayBadge) holidayBadge.style.display = 'none';

        // حساب الحصص والبريكات
        const firstStartMin = timeToMinutes(matchingCard.first_period_start || '08:15');
        const durationMin   = Math.max(20, parseInt(matchingCard.period_duration_minutes || 45));
        const lastEndMin    = timeToMinutes(matchingCard.last_period_end || '16:00');
        const b1After       = matchingCard.break1_after_period ? parseInt(matchingCard.break1_after_period) : null;
        const b1Dur         = matchingCard.break1_duration_minutes ? parseInt(matchingCard.break1_duration_minutes) : 0;
        const b2After       = matchingCard.break2_after_period ? parseInt(matchingCard.break2_after_period) : null;
        const b2Dur         = matchingCard.break2_duration_minutes ? parseInt(matchingCard.break2_duration_minutes) : 0;

        let currentMin = firstStartMin;
        let html = '';

        for (let p = 1; p <= 9; p++) {
            let slotStart = currentMin;
            let slotEnd   = slotStart + durationMin;

            if (p > 5 && slotStart >= lastEndMin) break;

            html += `
                <div style="background: var(--bg-secondary); border: 1px solid #3b82f6; border-radius: 0.6rem; padding: 0.4rem 0.75rem; text-align: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <div style="font-weight: 800; color: #1e40af; font-size: 0.85rem;">الحصة ${p}</div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600;">${minutesToTime(slotStart)} - ${minutesToTime(slotEnd)}</div>
                </div>
            `;

            currentMin = slotEnd;

            // هل يوجد بريك؟
            if (b1After && p === b1After && b1Dur > 0) {
                let bStart = currentMin;
                let bEnd   = bStart + b1Dur;
                html += `
                    <div style="background: #fef3c7; border: 1px dashed #f59e0b; border-radius: 0.6rem; padding: 0.4rem 0.65rem; text-align: center; color: #92400e;">
                        <div style="font-weight: 800; font-size: 0.8rem;"><i class="fa-solid fa-mug-hot"></i> استراحة (1)</div>
                        <div style="font-size: 0.72rem; font-weight: 700;">${b1Dur} دقيقة (${minutesToTime(bStart)} - ${minutesToTime(bEnd)})</div>
                    </div>
                `;
                currentMin = bEnd;
            } else if (b2After && p === b2After && b2Dur > 0) {
                let bStart = currentMin;
                let bEnd   = bStart + b2Dur;
                html += `
                    <div style="background: #fef3c7; border: 1px dashed #f59e0b; border-radius: 0.6rem; padding: 0.4rem 0.65rem; text-align: center; color: #92400e;">
                        <div style="font-weight: 800; font-size: 0.8rem;"><i class="fa-solid fa-mug-hot"></i> استراحة (2)</div>
                        <div style="font-size: 0.72rem; font-weight: 700;">${b2Dur} دقيقة (${minutesToTime(bStart)} - ${minutesToTime(bEnd)})</div>
                    </div>
                `;
                currentMin = bEnd;
            }
        }

        stripsBox.innerHTML = html;
    }

    function timeToMinutes(t) {
        if (!t) return 0;
        const p = t.split(':');
        return (parseInt(p[0]) * 60) + parseInt(p[1]);
    }

    function minutesToTime(totalMin) {
        const h = Math.floor(totalMin / 60);
        const m = totalMin % 60;
        return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
    }

    // =======================================================
    // 💾 حفظ واعتماد هيكل البرنامج في السيرفر عبر AJAX
    // =======================================================
    async function saveStructureToServer() {
        const btn = document.getElementById('btn-save-structure');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الحفظ والاعتماد...';

        try {
            const res = await fetch("{{ route('hod.organization.save_schedule_structure') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({ rules: localCards })
            });

            const data = await res.json();
            if (res.ok && data.success) {
                // تحديث البيانات المحلية
                scheduleStructure = data.rules;

                // إعادة جلب التايم لاين وتحديث الجدول
                const getRes = await fetch("{{ route('hod.organization.get_schedule_structure') }}");
                if (getRes.ok) {
                    const getData = await getRes.json();
                    dayTimelines = getData.dayTimelines;
                }

                renderGrid();
                updatePeriodRangeOptions();
                closeModal('schedule-structure-modal');
                alert("✅ تم حفظ واعتماد هيكل وتوقيت الحصص الأسبوعي بنجاح!");
            } else {
                alert("❌ حدث خطأ أثناء الحفظ: " + (data.message || 'يرجى مراجعة المدخلات.'));
            }
        } catch (e) {
            console.error(e);
            alert("❌ تعذر الاتصال بالسيرفر لحفظ الهيكل.");
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> حفظ واعتماد هيكل التوقيت';
        }
    }

    // =======================================================
    // 🌟 حساب الحصص المحجوزة مسبقاً لنفس الشعبة واليوم
    // =======================================================
    function getOccupiedPeriodsFor(day, classGroup) {
        const occupied = {};
        const tl = dayTimelines[day] || { periods: {} };

        allSchedules.forEach(s => {
            if (s.day !== day || s.class_group !== classGroup) return;

            let fromP = s.from_period ? parseInt(s.from_period) : null;
            let toP   = s.to_period ? parseInt(s.to_period) : fromP;

            // مطابقة بالوقت إذا لم تكن مخزنة أرقام الحصص
            if (!fromP && s.start_time && tl.periods) {
                Object.keys(tl.periods).forEach(pk => {
                    const pInfo = tl.periods[pk];
                    if (pInfo && s.start_time.startsWith(pInfo.start.substring(0, 5))) {
                        fromP = parseInt(pk);
                        toP = parseInt(pk);
                    }
                });
            }

            if (fromP) {
                if (!toP || toP < fromP) toP = fromP;
                for (let p = fromP; p <= toP; p++) {
                    occupied[p] = {
                        course_title: s.course_title,
                        room: s.room,
                        teacher: s.teacher_name,
                        from: fromP,
                        to: toP
                    };
                }
            }
        });

        return occupied;
    }

    function onScheduleDeptOrYearChange() {
        populateScheduleModal();
        updatePeriodRangeOptions();
    }

    // =======================================================
    // 🌟 تحديث واحتساب خيارات الحصص في نافذة إضافة الحصة
    // =======================================================
    function updatePeriodRangeOptions() {
        const daySelect    = document.getElementById('modal-day-select');
        const deptSelect   = document.getElementById('modal-dept-select');
        const yearSelect   = document.getElementById('modal-year-select');
        const fromSelect   = document.getElementById('modal-from-period-select');
        const toSelect     = document.getElementById('modal-to-period-select');
        const submitBtn    = document.getElementById('modal-schedule-submit-btn');
        const hintBox      = document.getElementById('modal-period-hint');
        const hintText     = document.getElementById('modal-period-hint-text');

        if (!daySelect || !fromSelect || !toSelect) return;

        const day = daySelect.value;
        const selectedDept = deptSelect ? deptSelect.value : currentDept;
        const selectedYear = yearSelect ? yearSelect.value : currentYear;
        const classGroup   = selectedDept + ' - ' + selectedYear;

        const tl = dayTimelines[day] || { is_holiday: false, periods: {} };

        fromSelect.innerHTML = '';
        toSelect.innerHTML   = '';

        if (tl.is_holiday) {
            fromSelect.innerHTML = '<option value="">(يوم عطلة رسمية)</option>';
            toSelect.innerHTML   = '<option value="">(يوم عطلة رسمية)</option>';
            fromSelect.disabled  = true;
            toSelect.disabled    = true;
            if (submitBtn) submitBtn.disabled = true;
            if (hintBox) {
                hintBox.style.background = '#fef3c7';
                hintBox.style.borderColor = '#f59e0b';
                hintBox.style.color = '#92400e';
            }
            if (hintText) hintText.innerHTML = '<i class="fa-solid fa-umbrella-beach"></i> هذا اليوم معتمد كعطلة رسمية في الهيكل ولا يمكن إضافة حصص فيه.';
            return;
        }

        fromSelect.disabled = false;
        toSelect.disabled   = false;

        // 1. تحديد عدد الحصص الفعلي الدقيق لهذا اليوم بناءً على الهيكل
        const periodNumbers = Object.keys(tl.periods || {})
            .map(Number)
            .filter(n => !isNaN(n) && n > 0)
            .sort((a, b) => a - b);

        const maxPeriod = periodNumbers.length > 0 ? Math.max(...periodNumbers) : 0;

        if (maxPeriod === 0) {
            fromSelect.innerHTML = '<option value="">(لا توجد حصص محددة لهذا اليوم)</option>';
            toSelect.innerHTML   = '<option value="">(لا توجد حصص محددة لهذا اليوم)</option>';
            fromSelect.disabled  = true;
            toSelect.disabled    = true;
            if (submitBtn) submitBtn.disabled = true;
            if (hintText) hintText.innerHTML = '⚠️ لا توجد فترات دوام محددة لهذا اليوم في الهيكل.';
            return;
        }

        // 2. جلب الحصص المحجوزة مسبقاً لهذه الشعبة في هذا اليوم
        const occupied = getOccupiedPeriodsFor(day, classGroup);

        let firstAvailable = null;

        periodNumbers.forEach(p => {
            const pInfo = tl.periods ? tl.periods[p] : null;
            const timeSub = pInfo ? ` (${pInfo.start} - ${pInfo.end})` : '';
            const isOccupied = !!occupied[p];

            if (!isOccupied && firstAvailable === null) {
                firstAvailable = p;
            }

            const optFrom = document.createElement('option');
            optFrom.value = p;

            if (isOccupied) {
                const courseName = occupied[p].course_title || 'مادة أخرى';
                optFrom.disabled = true;
                optFrom.style.color = '#94a3b8';
                optFrom.style.backgroundColor = '#f1f5f9';
                optFrom.style.textDecoration = 'line-through';
                optFrom.textContent = `الحصة ${p}${timeSub} — (محجوزة: ${courseName})`;
            } else {
                optFrom.textContent = `الحصة ${p}${timeSub}`;
            }
            fromSelect.appendChild(optFrom);
        });

        if (firstAvailable !== null) {
            fromSelect.value = firstAvailable;
            if (submitBtn) submitBtn.disabled = false;
            onFromPeriodChange();
        } else {
            // جميع حصص اليوم محجوزة بالكامل
            toSelect.innerHTML = '<option value="">(جميع الحصص محجوزة)</option>';
            fromSelect.disabled = true;
            toSelect.disabled   = true;
            if (submitBtn) submitBtn.disabled = true;
            if (hintBox) {
                hintBox.style.background = '#fee2e2';
                hintBox.style.borderColor = '#ef4444';
                hintBox.style.color = '#991b1b';
            }
            if (hintText) hintText.innerHTML = `⚠️ جميع حصص يوم (${daySelect.options[daySelect.selectedIndex].text}) محجوزة بالكامل لشعبة (${classGroup}).`;
        }
    }

    // =======================================================
    // 🌟 عند تغيير "من الحصة": تحديث خيارات "إلى الحصة" بدقة
    // =======================================================
    function onFromPeriodChange() {
        const daySelect    = document.getElementById('modal-day-select');
        const deptSelect   = document.getElementById('modal-dept-select');
        const yearSelect   = document.getElementById('modal-year-select');
        const fromSelect   = document.getElementById('modal-from-period-select');
        const toSelect     = document.getElementById('modal-to-period-select');
        const submitBtn    = document.getElementById('modal-schedule-submit-btn');

        if (!daySelect || !fromSelect || !toSelect) return;

        const day = daySelect.value;
        const selectedDept = deptSelect ? deptSelect.value : currentDept;
        const selectedYear = yearSelect ? yearSelect.value : currentYear;
        const classGroup   = selectedDept + ' - ' + selectedYear;

        const tl = dayTimelines[day] || { periods: {} };
        const occupied = getOccupiedPeriodsFor(day, classGroup);

        const periodNumbers = Object.keys(tl.periods || {})
            .map(Number)
            .filter(n => !isNaN(n) && n > 0)
            .sort((a, b) => a - b);

        const fromP = parseInt(fromSelect.value);
        toSelect.innerHTML = '';

        if (isNaN(fromP)) {
            onPeriodRangeChange();
            return;
        }

        // بناء خيارات "إلى الحصة" من fromP فصاعداً
        // إذا واجهنا أي حصة محجوزة، تصبح معطلة هي وكل ما بعدها حتى لا يتمكن من تجاوز الحجز
        let blocked = false;

        periodNumbers.forEach(p => {
            if (p < fromP) return; // لا يمكن أن تنتهي الحصة قبل بدايتها

            const pInfo = tl.periods ? tl.periods[p] : null;
            const timeSub = pInfo ? ` (${pInfo.start} - ${pInfo.end})` : '';
            const isOccupied = !!occupied[p];

            if (isOccupied) {
                blocked = true;
            }

            const optTo = document.createElement('option');
            optTo.value = p;

            if (isOccupied) {
                const courseName = occupied[p].course_title || 'مادة أخرى';
                optTo.disabled = true;
                optTo.style.color = '#94a3b8';
                optTo.style.backgroundColor = '#f1f5f9';
                optTo.style.textDecoration = 'line-through';
                optTo.textContent = `الحصة ${p}${timeSub} — (محجوزة: ${courseName})`;
            } else if (blocked) {
                optTo.disabled = true;
                optTo.style.color = '#94a3b8';
                optTo.style.backgroundColor = '#f1f5f9';
                optTo.textContent = `الحصة ${p}${timeSub} — (تتخللها حصة محجوزة)`;
            } else {
                optTo.textContent = `الحصة ${p}${timeSub}`;
            }

            toSelect.appendChild(optTo);
        });

        toSelect.value = fromP;
        onPeriodRangeChange();
    }

    // =======================================================
    // 🌟 حساب التوقيت الحي والتأكد النهائي من عدم التضارب
    // =======================================================
    function onPeriodRangeChange() {
        const daySelect    = document.getElementById('modal-day-select');
        const deptSelect   = document.getElementById('modal-dept-select');
        const yearSelect   = document.getElementById('modal-year-select');
        const fromSelect   = document.getElementById('modal-from-period-select');
        const toSelect     = document.getElementById('modal-to-period-select');
        const submitBtn    = document.getElementById('modal-schedule-submit-btn');
        const hintBox      = document.getElementById('modal-period-hint');
        const hintText     = document.getElementById('modal-period-hint-text');

        if (!daySelect || !fromSelect || !toSelect || !hintText || !hintBox) return;

        const day = daySelect.value;
        const selectedDept = deptSelect ? deptSelect.value : currentDept;
        const selectedYear = yearSelect ? yearSelect.value : currentYear;
        const classGroup   = selectedDept + ' - ' + selectedYear;

        let fromP = parseInt(fromSelect.value);
        let toP   = parseInt(toSelect.value);

        if (isNaN(fromP)) return;
        if (isNaN(toP) || toP < fromP) {
            toP = fromP;
            toSelect.value = toP;
        }

        const tl = dayTimelines[day] || { periods: {} };
        const occupied = getOccupiedPeriodsFor(day, classGroup);

        // فحص هل يحتوي المجال المختار [fromP..toP] على أي حصة محجوزة مسبقاً
        let conflictingPeriod = null;
        for (let p = fromP; p <= toP; p++) {
            if (occupied[p]) {
                conflictingPeriod = { period: p, info: occupied[p] };
                break;
            }
        }

        if (conflictingPeriod) {
            if (submitBtn) submitBtn.disabled = true;
            hintBox.style.background = '#fee2e2';
            hintBox.style.borderColor = '#ef4444';
            hintBox.style.color = '#991b1b';
            hintText.innerHTML = `⚠️ تعارض: الحصة <b>${conflictingPeriod.period}</b> محجوزة مسبقاً لمادة (<b>${conflictingPeriod.info.course_title}</b>). يرجى اختيار مجال حصص غير متداخل.`;
            return;
        }

        // لا يوجد تعارض
        if (submitBtn) submitBtn.disabled = false;
        hintBox.style.background = 'linear-gradient(135deg, #eff6ff, #dbeafe)';
        hintBox.style.borderColor = '#93c5fd';
        hintBox.style.color = '#1e40af';

        const fromInfo = tl.periods ? tl.periods[fromP] : null;
        const toInfo   = tl.periods ? tl.periods[toP] : null;

        const startStr = fromInfo ? fromInfo.start : '08:15';
        const endStr   = toInfo ? toInfo.end : (fromInfo ? fromInfo.end : '09:00');
        const diff     = toP - fromP + 1;

        hintText.innerHTML = `⏰ التوقيت المحسوب: من <b>${startStr}</b> إلى <b>${endStr}</b> (${diff === 1 ? 'حصة واحدة' : diff + ' حصص متتالية'}).`;
    }

    // استدعاء التهيئة الأولية للجدول والخيارات
    document.addEventListener('DOMContentLoaded', () => {
        renderGrid();
        updatePeriodRangeOptions();
    });

    // ===== Exam Filter Logic =====
    const allExams = @json($exams);
    let examDept = firstBranch;
    let examYear = 'سنة أولى';

    function selectExamDept(dept, btn) {
        examDept = dept;
        document.querySelectorAll('#tab-exams .dept-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const activeYearBtn = document.querySelector('#tab-exams .year-btn.active');
        if (activeYearBtn) {
            examYear = activeYearBtn.innerText.trim();
        }
        renderExams();
    }

    function selectExamYear(year, btn) {
        examYear = year;
        document.querySelectorAll('#tab-exams .year-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderExams();
    }

    function renderExams() {
        const tbody = document.getElementById('exam-table-body');
        const group = examDept + ' - ' + examYear;
        const filtered = allExams.filter(e => !e.class_group || e.class_group === group || e.class_group === '');

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="padding:2rem; text-align:center; color:var(--text-secondary);">{{ __('messages.no_exams_for_dept_year') }}</td></tr>`;
            return;
        }

        tbody.innerHTML = filtered.map(e => `
            <tr style="border-bottom:1px solid var(--border-color); font-size:0.95rem;">
                <td style="padding:1rem; font-weight:bold;">${e.course_title}</td>
                <td style="padding:1rem; color:var(--text-secondary);" dir="ltr">${formatDate(e.exam_date)}</td>
                <td style="padding:1rem;">
                    <span style="background:#f1f5f9; color:#334155; padding:0.25rem 0.6rem; border-radius:0.5rem; font-size:0.85rem; font-weight:bold;">
                        ${e.room || '-'}
                    </span>
                </td>
                <td style="padding:1rem; text-align:center; font-weight:bold; color:var(--accent-color);">${e.max_score}</td>
                <td style="padding:1rem; text-align:center;">
                    <div style="display:flex; gap:0.5rem; justify-content:center;">
                        <button onclick="openEditExam(${JSON.stringify(e).replace(/"/g,'&quot;')})"
                                style="background:#eff6ff; border:none; color:#1d4ed8; border-radius:0.5rem; padding:0.35rem 0.65rem; cursor:pointer; font-size:0.8rem; font-weight:700;">
                            <i class="fa-solid fa-pen"></i> {{ __('messages.edit') }}
                        </button>
                        <form action="/hod/organization/exam/delete/${e.exam_id}" method="POST"
                              onsubmit="return confirm('{{ __('messages.delete_exam_confirm') }}')" style="margin:0;">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <button type="submit" style="background:#fef2f2; border:none; color:#dc2626; border-radius:0.5rem; padding:0.35rem 0.65rem; cursor:pointer; font-size:0.8rem; font-weight:700;">
                                <i class="fa-solid fa-trash"></i> {{ __('messages.delete') }}
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function formatDate(d) {
        if (!d) return '-';
        const dt = new Date(d);
        return dt.toLocaleDateString('{{ app()->getLocale() == "ar" ? "ar-SA" : "en-US" }}') + ' ' + dt.toLocaleTimeString('{{ app()->getLocale() == "ar" ? "ar-SA" : "en-US" }}', {hour:'2-digit', minute:'2-digit'});
    }

    function openEditExam(exam) {
        document.getElementById('edit-exam-course').value = exam.course_id;
        document.getElementById('edit-exam-date').value = exam.exam_date ? exam.exam_date.substring(0, 16) : '';
        document.getElementById('edit-exam-room').value = exam.room || '';
        document.getElementById('edit-exam-score').value = exam.max_score || 100;
        document.getElementById('edit-exam-form').action = `/hod/organization/exam/edit/${exam.exam_id}`;
        openModal('edit-exam-modal');
    }

    // Initial render
    document.addEventListener('DOMContentLoaded', () => {
        renderGrid();
        renderExams();
    });

    function switchTab(tab) {
        // Hide all tabs
        document.getElementById('tab-schedules').style.display = 'none';
        document.getElementById('tab-exams').style.display = 'none';
        if (document.getElementById('tab-weights')) document.getElementById('tab-weights').style.display = 'none';

        // Remove active class from all buttons
        document.getElementById('btn-schedules').classList.remove('active');
        document.getElementById('btn-exams').classList.remove('active');
        if (document.getElementById('btn-weights')) document.getElementById('btn-weights').classList.remove('active');

        // Show selected tab and set active class
        if (tab === 'schedules') {
            document.getElementById('tab-schedules').style.display = 'block';
            document.getElementById('btn-schedules').classList.add('active');
        } else if (tab === 'exams') {
            document.getElementById('tab-exams').style.display = 'block';
            document.getElementById('btn-exams').classList.add('active');
        } else if (tab === 'weights') {
            if (document.getElementById('tab-weights')) document.getElementById('tab-weights').style.display = 'block';
            if (document.getElementById('btn-weights')) document.getElementById('btn-weights').classList.add('active');
        }
    }

    function populateScheduleModal() {
        // === فلترة المواد حسب الفرع والسنة والفصل المختار في النافذة ===
        const courseSelect = document.getElementById('modal-course-select');
        const semesterSelect = document.getElementById('modal-semester-select');
        const deptSelect = document.getElementById('modal-dept-select');
        const yearSelect = document.getElementById('modal-year-select');

        if (!courseSelect) return;
        courseSelect.innerHTML = '<option value="">{{ __('messages.select_course_option') }}</option>';

        const selectedDept = deptSelect ? deptSelect.value : currentDept;
        const selectedYear = yearSelect ? yearSelect.value : currentYear;
        const yearInt = selectedYear === 'سنة أولى' ? 1 : (selectedYear === 'سنة ثانية' ? 2 : 3);
        const semesterInt = semesterSelect ? parseInt(semesterSelect.value) : 1;

        let filteredCourses = allCoursesData.filter(c => c.branch_name === selectedDept && c.year == yearInt && c.semester_id == semesterInt);
        
        // إذا لم توجد مواد مطابقة للفصل المحدد تماماً، نعرض مواد التخصص والسنة
        if (filteredCourses.length === 0) {
            filteredCourses = allCoursesData.filter(c => c.branch_name === selectedDept && c.year == yearInt);
        }

        filteredCourses.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.course_id;
            opt.textContent = c.title + (c.semester_id ? ' (فصل ' + c.semester_id + ')' : '');
            courseSelect.appendChild(opt);
        });

        if (filteredCourses.length === 0) {
            const opt = document.createElement('option');
            opt.value = "";
            opt.disabled = true;
            opt.textContent = 'لا توجد مواد مسندة لهذا التخصص والسنة حالياً';
            courseSelect.appendChild(opt);
        }
    }

    function populateExamModal() {
        const courseSelect = document.getElementById('exam-modal-course-select');
        const semesterSelect = document.getElementById('exam-modal-semester-select');
        const deptSelect = document.getElementById('exam-modal-dept-select');
        const yearSelect = document.getElementById('exam-modal-year-select');

        if (!courseSelect) return;
        courseSelect.innerHTML = '<option value="">{{ __('messages.select_course_option') }}</option>';

        const selectedDept = deptSelect ? deptSelect.value : examDept;
        const selectedYear = yearSelect ? yearSelect.value : examYear;
        const yearInt = selectedYear === 'سنة أولى' ? 1 : (selectedYear === 'سنة ثانية' ? 2 : 3);
        const semesterInt = semesterSelect ? parseInt(semesterSelect.value) : 1;

        let filteredCourses = allCoursesData.filter(c => c.branch_name === selectedDept && c.year == yearInt && c.semester_id == semesterInt);
        if (filteredCourses.length === 0) {
            filteredCourses = allCoursesData.filter(c => c.branch_name === selectedDept && c.year == yearInt);
        }

        filteredCourses.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.course_id;
            opt.textContent = c.title + (c.semester_id ? ' (فصل ' + c.semester_id + ')' : '');
            courseSelect.appendChild(opt);
        });

        if (filteredCourses.length === 0) {
            const opt = document.createElement('option');
            opt.value = "";
            opt.disabled = true;
            opt.textContent = 'لا توجد مواد مسندة لهذا التخصص والسنة حالياً';
            courseSelect.appendChild(opt);
        }
    }

    function openModal(modalId) {
        if (modalId === 'schedule-modal') {
            const deptSelect = document.getElementById('modal-dept-select');
            const yearSelect = document.getElementById('modal-year-select');
            if (deptSelect && currentDept) deptSelect.value = currentDept;
            if (yearSelect && currentYear) yearSelect.value = currentYear;
            populateScheduleModal();
            updatePeriodRangeOptions();
        } else if (modalId === 'exam-modal') {
            const deptSelect = document.getElementById('exam-modal-dept-select');
            const yearSelect = document.getElementById('exam-modal-year-select');
            if (deptSelect && examDept) deptSelect.value = examDept;
            if (yearSelect && examYear) yearSelect.value = examYear;
            populateExamModal();
        }
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    // ===== Weights Filter Logic =====
    let weightDept = firstBranch;
    let weightYear = 'سنة أولى';

    function selectWeightDept(dept, btn) {
        weightDept = dept;
        document.querySelectorAll('#tab-weights .dept-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const activeYearBtn = document.querySelector('#tab-weights .year-btn.active');
        if (activeYearBtn) {
            weightYear = activeYearBtn.innerText.trim();
        }
        filterWeights();
    }

    function selectWeightYear(year, btn) {
        weightYear = year;
        document.querySelectorAll('#tab-weights .year-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filterWeights();
    }

    function filterWeights() {
        const cards = document.querySelectorAll('.weight-card');
        let visibleCount = 0;
        cards.forEach(card => {
            if (card.dataset.dept === weightDept && card.dataset.year === weightYear) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show empty state if no cards visible
        let emptyState = document.getElementById('weights-empty-state');
        if (visibleCount === 0) {
            if (!emptyState) {
                const grid = document.getElementById('weights-grid');
                emptyState = document.createElement('div');
                emptyState.id = 'weights-empty-state';
                emptyState.style.gridColumn = '1 / -1';
                emptyState.style.textAlign = 'center';
                emptyState.style.padding = '3rem';
                emptyState.style.color = 'var(--text-secondary)';
                emptyState.innerHTML = '<i class="fa-solid fa-book-open" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i><p style="font-weight: 700;">{{ __('messages.no_courses_available') }}</p>';
                grid.appendChild(emptyState);
            }
            emptyState.style.display = 'block';
        } else if (emptyState) {
            emptyState.style.display = 'none';
        }
    }

    // Initial Filter
    document.addEventListener('DOMContentLoaded', () => {
        filterWeights();
    });
</script>
@endpush
