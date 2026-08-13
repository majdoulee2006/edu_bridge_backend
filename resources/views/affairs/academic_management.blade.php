@extends('layouts.affairs')
@section('title', 'الأعوام والفصول الدراسية')
@section('subtitle', 'إدارة الفصول النشطة، الترفيع الأكاديمي، وتعديل السنة الدراسية للطلاب بالمعهد')

@push('styles')
<style>
    .academic-header {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255,255,255,0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        background: var(--bg-primary);
        padding: 0.6rem 1.2rem;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.08);
    }
    .stat-pill-icon {
        width: 38px; height: 38px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }

    /* Collapsible Accordion Cards */
    .collapsible-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255,255,255,0.08);
        overflow: hidden;
        transition: border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .collapsible-card.open {
        border-color: rgba(255,255,255,0.18);
        box-shadow: 0 8px 25px rgba(0,0,0,0.25);
    }

    .collapsible-header {
        padding: 1.25rem 1.5rem;
        cursor: pointer;
        user-select: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        transition: background 0.2s ease;
    }

    .collapsible-header:hover {
        background: rgba(255, 255, 255, 0.02);
    }

    .card-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .toggle-arrow-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: var(--text-secondary);
        transition: transform 0.3s ease, background 0.2s ease, color 0.2s ease;
    }

    .collapsible-card.open .toggle-arrow-btn {
        transform: rotate(180deg);
        background: var(--accent-color);
        color: #1a1a1a;
    }

    .collapsible-body {
        display: none;
        padding: 1.25rem 1.5rem 1.5rem;
        border-top: 1px solid rgba(255,255,255,0.08);
        background: rgba(0, 0, 0, 0.1);
    }

    .collapsible-card.open .collapsible-body {
        display: block;
    }

    /* Table Styles */
    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 1rem;
    }
    .custom-table th {
        background: var(--bg-primary);
        color: var(--text-secondary);
        font-weight: 700;
        font-size: 0.85rem;
        padding: 0.9rem 1rem;
        text-align: right;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .custom-table td {
        padding: 1rem;
        font-size: 0.9rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        color: var(--text-primary);
        font-weight: 600;
    }
    .custom-table tr:last-child td {
        border-bottom: none;
    }

    .level-badge-1 {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
        border: 1px solid rgba(59, 130, 246, 0.3);
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        font-weight: 800;
    }
    .level-badge-2 {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        font-weight: 800;
    }

    .btn-action {
        padding: 0.45rem 0.9rem;
        border-radius: 0.6rem;
        font-size: 0.82rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s ease;
    }

    /* Laravel Pagination Dark Mode Fix */
    .pagination-wrapper nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255,255,255,0.08);
        color: var(--text-secondary);
        font-size: 0.85rem;
    }
    .pagination-wrapper svg {
        width: 1.2rem !important;
        height: 1.2rem !important;
        max-width: 1.2rem !important;
        max-height: 1.2rem !important;
        display: inline-block !important;
        fill: currentColor;
    }
    .pagination-wrapper a, .pagination-wrapper span.page-link, .pagination-wrapper span.page-item {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.4rem 0.85rem;
        border-radius: 0.5rem;
        background: var(--bg-primary);
        color: var(--text-primary);
        text-decoration: none;
        font-weight: 700;
        border: 1px solid rgba(255,255,255,0.1);
        font-size: 0.85rem;
    }
    .pagination-wrapper .page-item.active .page-link {
        background: var(--accent-color) !important;
        color: #1a1a1a !important;
        border-color: var(--accent-color) !important;
    }
    .pagination-wrapper .pagination {
        display: flex;
        list-style: none;
        gap: 0.4rem;
        padding: 0;
        margin: 0;
    }
</style>
@endpush

@section('content')

{{-- ── 1️⃣ Header Overview & Quick Stats ── --}}
<div class="academic-header">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="width: 54px; height: 54px; border-radius: 16px; background: rgba(252, 227, 0, 0.15); color: var(--accent-color); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; box-shadow: 0 4px 14px rgba(0,0,0,0.2);">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
            <h2 style="font-size: 1.3rem; font-weight: 900; margin: 0; color: var(--text-primary);">لوحة الإدارة الأكاديمية بالمعهد</h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0.2rem 0 0; font-weight: 600;">التحكم بالفصول الدراسية النشطة، ترحيل الدفعات، وتحديد السنوات الأكاديمية للطلاب</p>
        </div>
    </div>

    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <div class="stat-pill">
            <div class="stat-pill-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
                <i class="fa-solid fa-user-graduate"></i>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: var(--text-secondary); font-weight: 700;">طلاب السنة الأولى</div>
                <div style="font-size: 1.1rem; font-weight: 900; color: #3b82f6;">{{ $firstYearCount }} طالباً</div>
            </div>
        </div>

        <div class="stat-pill">
            <div class="stat-pill-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: var(--text-secondary); font-weight: 700;">طلاب السنة الثانية</div>
                <div style="font-size: 1.1rem; font-weight: 900; color: #10b981;">{{ $secondYearCount }} طالباً</div>
            </div>
        </div>
    </div>
</div>


{{-- ── 2️⃣ إدارة وتفعيل الفصول الدراسية (Collapsible Card 1) ── --}}
<div class="collapsible-card" id="card-semesters">
    <div class="collapsible-header" onclick="toggleAcademicCard('card-semesters')">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <div class="card-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-primary); margin: 0;">1. إدارة وتفعيل الفصول الدراسية</h3>
                <div style="font-size: 0.78rem; color: var(--text-secondary); font-weight: 600; margin-top: 0.15rem;">ضبط الفصل الشغّال بالمعهد لتلقين المناهج والجدول والحضور والغياب</div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 1rem;">
            @if($activeSemester)
                <span style="background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.4rem 0.9rem; border-radius: 2rem; font-size: 0.82rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i class="fa-solid fa-circle" style="font-size: 0.45rem;"></i>
                    الفصل النشط: {{ $activeSemester->name }}
                </span>
            @else
                <span style="background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.4rem 0.9rem; border-radius: 2rem; font-size: 0.82rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i class="fa-solid fa-circle-exclamation"></i> لا يوجد فصل نشط
                </span>
            @endif

            <div class="toggle-arrow-btn">
                <i class="fa-solid fa-chevron-down"></i>
            </div>
        </div>
    </div>

    <div class="collapsible-body">
        {{-- نموذج تفعيل وتعديل الفصل الحالي --}}
        <form action="{{ route('affairs.semesters.activate') }}" method="POST" style="display: flex; align-items: flex-end; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; background: var(--bg-primary); padding: 1.25rem; border-radius: 1rem; border: 1px solid rgba(255,255,255,0.05);">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 0.35rem; flex: 1; min-width: 220px;">
                <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-secondary);">الفصل الدراسي المراد تفعيله:</label>
                <select name="semester_id" style="padding: 0.6rem 0.85rem; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.15); background: var(--bg-secondary); color: var(--text-primary); font-weight: 700; outline: none; width: 100%;">
                    <option value="none">-- إيقاف تفعيل جميع الفصول --</option>
                    @foreach($semestersList as $sem)
                        <option value="{{ $sem->semester_id }}" {{ $sem->is_active ? 'selected' : '' }}>
                            {{ $sem->name }} {{ $sem->is_active ? '(نشط)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-secondary);">تاريخ بداية الفصل (من):</label>
                <input type="date" name="start_date" value="{{ $activeSemester?->start_date }}" style="padding: 0.55rem 0.85rem; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.15); background: var(--bg-secondary); color: var(--text-primary); font-weight: 700; outline: none;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-secondary);">تاريخ نهاية الفصل (إلى):</label>
                <input type="date" name="end_date" value="{{ $activeSemester?->end_date }}" style="padding: 0.55rem 0.85rem; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.15); background: var(--bg-secondary); color: var(--text-primary); font-weight: 700; outline: none;">
            </div>

            <div>
                <button type="submit" style="background: #3b82f6; color: white; border: none; padding: 0.65rem 1.4rem; border-radius: 0.6rem; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; height: 42px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                    <i class="fa-solid fa-check-double"></i> اعتماد وتفعيل الفصل
                </button>
            </div>
        </form>

        {{-- إنشاء فصل دراسي جديد --}}
        <details style="margin-top: 0.5rem;">
            <summary style="cursor: pointer; font-size: 0.88rem; font-weight: 800; color: #3b82f6; display: inline-flex; align-items: center; gap: 0.4rem;">
                <i class="fa-solid fa-plus-circle"></i> إنشاء فصل دراسي جديد (مثال: الفصل الأول 2027)
            </summary>
            <form action="{{ route('affairs.semesters.store') }}" method="POST" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: 0.85rem; background: rgba(0,0,0,0.15); padding: 1rem; border-radius: 0.8rem; border: 1px dashed rgba(255,255,255,0.15);">
                @csrf
                <input type="text" name="name" placeholder="اسم الفصل الدراسي (مثال: الفصل الأول 2027)" required style="padding: 0.55rem 0.85rem; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.15); background: var(--bg-primary); color: var(--text-primary); font-weight: 700; outline: none; min-width: 250px;">
                <input type="date" name="start_date" style="padding: 0.55rem 0.85rem; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.15); background: var(--bg-primary); color: var(--text-primary); font-weight: 700; outline: none;">
                <input type="date" name="end_date" style="padding: 0.55rem 0.85rem; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.15); background: var(--bg-primary); color: var(--text-primary); font-weight: 700; outline: none;">
                <button type="submit" style="background: rgba(255,255,255,0.12); color: var(--text-primary); border: 1px solid rgba(255,255,255,0.2); padding: 0.55rem 1.2rem; border-radius: 0.6rem; font-weight: 700; cursor: pointer;">
                    حفظ الفصل الجديد
                </button>
            </form>
        </details>
    </div>
</div>


{{-- ── 3️⃣ الترفيع الأكاديمي والترحيل الجماعي للطلاب (Collapsible Card 2) ── --}}
<div class="collapsible-card" id="card-batch-promotion" style="border: 1px solid rgba(16, 185, 129, 0.25);">
    <div class="collapsible-header" onclick="toggleAcademicCard('card-batch-promotion')" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.04) 0%, rgba(5, 150, 105, 0.08) 100%);">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <div class="card-icon" style="background: #10b981; color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);">
                <i class="fa-solid fa-angles-up"></i>
            </div>
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    2. الترفيع الأكاديمي والترحيل الجماعي للدفعة
                    <span style="font-size: 0.72rem; background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 0.15rem 0.6rem; border-radius: 1rem; font-weight: 800;">إجراء سنوي</span>
                </h3>
                <div style="font-size: 0.78rem; color: var(--text-secondary); font-weight: 600; margin-top: 0.15rem;">ترقية جميع طلاب دفعة (السنة الأولى ➔ السنة الثانية) وتلقائياً تسجيلهم في مواد المرحلة الجديدة</div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.4rem 0.9rem; border-radius: 2rem; font-size: 0.82rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem;">
                المستحقون: {{ $firstYearCount }} طالباً
            </span>

            <div class="toggle-arrow-btn">
                <i class="fa-solid fa-chevron-down"></i>
            </div>
        </div>
    </div>

    <div class="collapsible-body" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.02) 0%, rgba(0, 0, 0, 0.15) 100%);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.2rem;">
            <div style="max-width: 600px;">
                <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-primary); margin: 0 0 0.4rem;">آلية الترفيع الجماعي للدفعة:</h4>
                <div style="font-size: 0.82rem; color: var(--text-secondary); font-weight: 600; line-height: 1.6;">
                    عند الضغط على الزر، سيتم تغيير السنة الدراسية لجميع طلاب السنة الأولى البالغ عددهم ({{ $firstYearCount }} طالباً) ليكونوا في السنة الثانية، وسيتم إضافتهم تلقائياً لجميع المواد المسجلة للسنة الثانية بالمعهد.
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap;">
                <div style="text-align: center; background: rgba(0,0,0,0.25); padding: 0.6rem 1.2rem; border-radius: 0.85rem; border: 1px solid rgba(255,255,255,0.08);">
                    <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 700;">المستحقون للترقية حالياً:</div>
                    <div style="font-size: 1.3rem; font-weight: 900; color: #10b981; line-height: 1.2;">{{ $firstYearCount }} طالباً</div>
                </div>

                <form action="{{ route('affairs.students.promote') }}" method="POST" onsubmit="return confirm('تأكيد عملية الترفيع الجماعية:\n\nهل أنت متأكد من ترفيع جميع طلاب السنة الأولى (عدد {{ $firstYearCount }} طالباً) إلى السنة الثانية وتلقين موادهم التلقائية؟');">
                    @csrf
                    <button type="submit" {{ $firstYearCount == 0 ? 'disabled' : '' }} style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 0.75rem 1.4rem; border-radius: 0.75rem; font-weight: 800; cursor: {{ $firstYearCount == 0 ? 'not-allowed' : 'pointer' }}; opacity: {{ $firstYearCount == 0 ? '0.5' : '1' }}; display: flex; align-items: center; gap: 0.6rem; font-size: 0.92rem; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);">
                        <i class="fa-solid fa-graduation-cap"></i> تنفيذ الترفيع لجميع طلاب السنة الأولى
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


{{-- ── 4️⃣ إدارة السنة الدراسية للطلاب فردياً (Collapsible Card 3) ── --}}
@php
    $hasSearchOrFilter = request('search') || request('level_filter') || request('page');
@endphp
<div class="collapsible-card {{ $hasSearchOrFilter ? 'open' : '' }}" id="card-individual-students">
    <div class="collapsible-header" onclick="toggleAcademicCard('card-individual-students')">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <div class="card-icon" style="background: rgba(252,227,0,0.15); color: var(--accent-color);">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-primary); margin: 0;">3. التعديل الفردي للسنة الدراسية للطلاب</h3>
                <div style="font-size: 0.78rem; color: var(--text-secondary); font-weight: 600; margin-top: 0.15rem;">تخصيص السنة الأكاديمية لكل طالب يدوياً (سنة أولى / سنة ثانية) عند التسجيل أو إعادة السنة</div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="background: rgba(252, 227, 0, 0.12); color: var(--accent-color); border: 1px solid rgba(252, 227, 0, 0.3); padding: 0.4rem 0.9rem; border-radius: 2rem; font-size: 0.82rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem;">
                <i class="fa-solid fa-list"></i> قائم المعرض: {{ $students->total() }} طالباً
            </span>

            <div class="toggle-arrow-btn">
                <i class="fa-solid fa-chevron-down"></i>
            </div>
        </div>
    </div>

    <div class="collapsible-body">
        {{-- محرك البحث والفلترة --}}
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; background: var(--bg-primary); padding: 0.85rem 1.1rem; border-radius: 0.85rem; border: 1px solid rgba(255,255,255,0.06);">
            <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-primary);">البحث والفلترة السريعة:</div>
            <form action="{{ route('affairs.academic_management') }}" method="GET" style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث باسم الطالب أو كوده..." style="padding: 0.5rem 0.85rem; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.15); background: var(--bg-secondary); color: var(--text-primary); font-size: 0.85rem; outline: none; min-width: 220px;">
                
                <select name="level_filter" onchange="this.form.submit()" style="padding: 0.5rem 0.85rem; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.15); background: var(--bg-secondary); color: var(--text-primary); font-size: 0.85rem; font-weight: 700; outline: none;">
                    <option value="">جميع السنوات الأكاديمية</option>
                    <option value="first_year" {{ request('level_filter') == 'first_year' ? 'selected' : '' }}>السنة الأولى ({{ $firstYearCount }})</option>
                    <option value="second_year" {{ request('level_filter') == 'second_year' ? 'selected' : '' }}>السنة الثانية ({{ $secondYearCount }})</option>
                </select>

                <button type="submit" style="background: var(--accent-color); color: #1a1a1a; border: none; padding: 0.5rem 1rem; border-radius: 0.6rem; font-weight: 800; cursor: pointer;">
                    <i class="fa-solid fa-magnifying-glass"></i> بحث
                </button>
                @if(request('search') || request('level_filter'))
                    <a href="{{ route('affairs.academic_management') }}" style="color: #ef4444; font-size: 0.85rem; font-weight: 700; text-decoration: none; padding: 0.4rem 0.6rem;">إلغاء الفلتر</a>
                @endif
            </form>
        </div>

        {{-- جدول الطلاب --}}
        <div style="overflow-x: auto;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>الكود الجامعي</th>
                        <th>اسم الطالب</th>
                        <th>البريد الإلكتروني</th>
                        <th>التخصص / الفرع</th>
                        <th>السنة الدراسية الحالية</th>
                        <th style="text-align: center;">إجراءات تعديل السنة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $st)
                        @php
                            $isFirstYear = in_array($st->level, ['السنة الأولى', 'أولى', '1']) || empty($st->level);
                        @endphp
                        <tr>
                            <td>
                                <span style="font-family: monospace; font-weight: 800; color: var(--accent-color);">
                                    {{ $st->student_code ?? 'غير مسجل' }}
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: var(--text-primary);">
                                    {{ $st->user->full_name ?? 'بدون اسم' }}
                                </div>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.82rem;">
                                {{ $st->user->email ?? '-' }}
                            </td>
                            <td>
                                <span style="font-size: 0.82rem; color: var(--text-secondary);">
                                    {{ $st->program->name ?? 'العام' }}
                                </span>
                            </td>
                            <td>
                                @if($isFirstYear)
                                    <span class="level-badge-1"><i class="fa-solid fa-1"></i> السنة الأولى</span>
                                @else
                                    <span class="level-badge-2"><i class="fa-solid fa-2"></i> السنة الثانية</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <form action="{{ route('affairs.students.update-level', $st->student_id) }}" method="POST" style="display: inline-flex; gap: 0.4rem;">
                                    @csrf
                                    @if($isFirstYear)
                                        <input type="hidden" name="level" value="السنة الثانية">
                                        <button type="submit" class="btn-action" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                                            <i class="fa-solid fa-arrow-up"></i> تحويل إلى السنة الثانية
                                        </button>
                                    @else
                                        <input type="hidden" name="level" value="السنة الأولى">
                                        <button type="submit" class="btn-action" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                            <i class="fa-solid fa-arrow-down"></i> إرجاع للسنة الأولى
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                <i class="fa-solid fa-user-slash" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.4; display: block;"></i>
                                لا يوجد طلاب ينطبق عليهم البحث الحالي.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- الترقيم (Pagination) --}}
        <div class="pagination-wrapper" style="margin-top: 1.25rem;">
            {{ $students->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function toggleAcademicCard(cardId) {
        const card = document.getElementById(cardId);
        if (card) {
            card.classList.toggle('open');
        }
    }
</script>
@endpush

