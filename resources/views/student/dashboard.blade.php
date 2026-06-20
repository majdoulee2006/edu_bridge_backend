@extends('layouts.student')
@section('title', 'الرئيسية')
@section('subtitle', 'مرحباً، ' . (auth()->user()->full_name ?? 'الطالب'))

@push('styles')
<style>
    .stat-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        gap: 1.25rem;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 2px solid transparent;
        text-decoration: none;
        color: inherit;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        border-color: var(--accent-color);
    }
    .stat-card-plain { cursor: default; }
    .stat-card-plain:hover { transform: none; border-color: transparent; }

    .stat-icon {
        width: 56px; height: 56px;
        border-radius: 1rem;
        background: var(--accent-color);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #1a1a1a; flex-shrink: 0;
    }
    .stat-value { font-size: 2rem; font-weight: 800; line-height: 1; }
    .stat-label { color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem; }
    .stat-hint  { font-size: 0.75rem; color: var(--accent-color); margin-top: 0.3rem; font-weight: 600; }

    .section-title { font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; }

    .notif-card { background: var(--bg-secondary); border-radius: 1rem; padding: 1.25rem; box-shadow: var(--shadow); margin-bottom: 0.75rem; display: flex; gap: 1rem; align-items: flex-start; }
    .notif-dot  { width: 10px; height: 10px; border-radius: 50%; background: var(--accent-color); flex-shrink: 0; margin-top: 5px; }

    .list-item {
        background: var(--bg-primary);
        border-radius: 0.875rem;
        padding: 1rem 1.25rem;
        margin-bottom: 0.6rem;
        display: flex; align-items: center; gap: 1rem;
        border-right: 3px solid var(--accent-color);
    }
    .list-icon {
        width: 40px; height: 40px; border-radius: 0.75rem;
        background: var(--accent-color);
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; color: #1a1a1a; flex-shrink: 0;
    }
    .list-item-title { font-weight: 700; font-size: 0.95rem; }
    .list-item-sub   { color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.15rem; }

    .badge { padding: 0.2rem 0.6rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; }
    .badge-pending { background: hsl(30,70%,90%);  color: hsl(30,50%,30%); }
    .badge-submitted { background: hsl(200,70%,90%); color: hsl(200,50%,30%); }
    .badge-graded  { background: hsl(120,70%,90%); color: hsl(120,50%,30%); }
    .badge-late    { background: hsl(0,70%,90%);   color: hsl(0,50%,30%); }

    /* Mobile notice card */
    .mobile-notice {
        background: linear-gradient(135deg, #1a2633, #243447);
        border-radius: 1.25rem;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        color: white;
    }
    .mobile-notice i { color: var(--accent-color); font-size: 1.5rem; flex-shrink: 0; }
    .mobile-notice-text { font-size: 0.85rem; line-height: 1.6; }
    .mobile-notice-title { font-weight: 800; font-size: 0.95rem; margin-bottom: 0.2rem; color: var(--accent-color); }

    /* Attendance bar */
    .att-bar-wrap { background: var(--bg-primary); border-radius: 2rem; height: 10px; overflow: hidden; margin-top: 0.5rem; }
    .att-bar { height: 100%; border-radius: 2rem; background: var(--accent-color); transition: width 0.5s; }

    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.5); z-index: 1000;
        align-items: center; justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        width: 94%; max-width: 560px;
        max-height: 85vh; overflow-y: auto;
        padding: 2rem;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        animation: slideUp 0.25s ease;
    }
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    .modal-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1.5rem; padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    .modal-close { background: none; border: none; font-size: 1.3rem; color: var(--text-secondary); cursor: pointer; }
</style>
@endpush

@section('content')

{{-- Mobile-only notice --}}
<div class="mobile-notice">
    <i class="fa-solid fa-mobile-screen-button"></i>
    <div class="mobile-notice-text">
        <div class="mobile-notice-title">تسجيل الحضور عبر التطبيق فقط</div>
        تسجيل الحضور يتم عن طريق مسح رمز QR أو التعرف على الوجه في تطبيق الجوال.
    </div>
</div>

{{-- Stats --}}
<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem; margin-bottom: 2rem;">

    <a href="{{ route('student.courses') }}" class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
        <div>
            <div class="stat-value">{{ $courses->count() }}</div>
            <div class="stat-label">موادي الدراسية</div>
            <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
        </div>
    </a>

    <a href="{{ route('student.assignments') }}" class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-file-pen"></i></div>
        <div>
            <div class="stat-value">{{ $assignments->count() }}</div>
            <div class="stat-label">الواجبات القادمة</div>
            <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
        </div>
    </a>

    <div class="stat-card stat-card-plain">
        <div class="stat-icon"><i class="fa-solid fa-chart-bar"></i></div>
        <div style="flex: 1;">
            <div class="stat-value">{{ $avgGrade }}%</div>
            <div class="stat-label">متوسط الدرجات</div>
        </div>
    </div>

    <div class="stat-card stat-card-plain">
        <div class="stat-icon"><i class="fa-solid fa-clipboard-user"></i></div>
        <div style="flex: 1;">
            <div class="stat-value">{{ $attendanceRate }}%</div>
            <div class="stat-label">نسبة الحضور</div>
            <div class="att-bar-wrap">
                <div class="att-bar" style="width: {{ $attendanceRate }}%;"></div>
            </div>
        </div>
    </div>

</div>

{{-- Upcoming Assignments --}}
<div style="margin-bottom: 2rem;">
    <p class="section-title">
        <i class="fa-solid fa-file-pen" style="color: var(--accent-color);"></i>
        الواجبات القادمة
    </p>

    @forelse($assignments as $a)
        @php
            $dueDate = \Carbon\Carbon::parse($a->due_date);
            $isOverdue = $dueDate->isPast();
        @endphp
        <div class="list-item">
            <div class="list-icon"><i class="fa-solid fa-file-lines"></i></div>
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                    @if($isOverdue)
                        <span class="badge badge-late">متأخر</span>
                    @else
                        <span class="badge badge-pending">نشط</span>
                    @endif
                    <span class="list-item-title">{{ $a->title }}</span>
                </div>
                <div class="list-item-sub">
                    <i class="fa-solid fa-book"></i> {{ $a->course_title }}
                    &nbsp;·&nbsp;
                    <i class="fa-solid fa-calendar"></i> {{ $dueDate->format('Y-m-d') }}
                </div>
            </div>
            <a href="{{ route('student.assignments') }}" style="color: var(--accent-color); font-size: 1rem;">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
        </div>
    @empty
        <div style="text-align: center; padding: 2.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
            <i class="fa-solid fa-check-circle" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; color: var(--accent-color); opacity: 0.5;"></i>
            لا توجد واجبات قادمة
        </div>
    @endforelse
</div>

{{-- Announcements --}}
<div style="margin-bottom: 2rem;">
    <p class="section-title">
        <i class="fa-solid fa-bullhorn" style="color: var(--accent-color);"></i>
        آخر الأخبار والإعلانات
    </p>

    @forelse($announcements as $ann)
        @php
            $imgUrl = ($ann->image ?? false) ? asset('storage/' . $ann->image) : null;
            $gradients = [
                'linear-gradient(135deg,#1a2633,#f2f20d33)',
                'linear-gradient(135deg,#0f2027,#203a43,#2c5364)',
                'linear-gradient(135deg,#1a1a2e,#16213e,#0f3460)',
                'linear-gradient(135deg,#2d1b69,#11998e)',
                'linear-gradient(135deg,#232526,#414345)',
            ];
            $grad = $gradients[$loop->index % count($gradients)];
            $icons = ['fa-bullhorn','fa-bell','fa-star','fa-bookmark','fa-flag'];
            $icon  = $icons[$loop->index % count($icons)];
        @endphp

        @if($loop->first)
        <div style="display: flex; flex-direction: row-reverse; border-radius: 1.25rem; overflow: hidden; background: var(--bg-secondary); box-shadow: var(--shadow); margin-bottom: 1.25rem; min-height: 200px;">
            <div style="width: 38%; flex-shrink: 0; background: {{ $grad }}; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                @if($imgUrl)
                    <img src="{{ $imgUrl }}" style="width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;"
                         onerror="this.style.display='none'">
                @endif
                <i class="fa-solid {{ $icon }}" style="font-size: 4rem; color: rgba(242,242,13,0.25);"></i>
            </div>
            <div style="flex: 1; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <span style="background: var(--accent-color); color: #1a1a1a; padding: 0.2rem 0.75rem; border-radius: 2rem; font-size: 0.78rem; font-weight: 700; display: inline-block; margin-bottom: 0.75rem;">إعلان هام</span>
                    <h4 style="font-size: 1.05rem; font-weight: 800; margin-bottom: 0.5rem;">{{ $ann->title }}</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6;">{{ Str::limit($ann->content, 200) }}</p>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.75rem;">
                    <i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}
                </div>
            </div>
        </div>
        @else
        <div style="display: flex; flex-direction: row-reverse; border-radius: 1.25rem; overflow: hidden; background: var(--bg-secondary); box-shadow: var(--shadow); margin-bottom: 0.75rem; min-height: 110px;">
            <div style="width: 150px; flex-shrink: 0; background: {{ $grad }}; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                @if($imgUrl)
                    <img src="{{ $imgUrl }}" style="width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;"
                         onerror="this.style.display='none'">
                @endif
                <i class="fa-solid {{ $icon }}" style="font-size: 2.5rem; color: rgba(242,242,13,0.3);"></i>
            </div>
            <div style="flex: 1; padding: 1rem 1.25rem; display: flex; flex-direction: column; justify-content: center;">
                <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 0.3rem;">{{ $ann->title }}</h4>
                <p style="font-size: 0.8rem; color: var(--text-secondary); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $ann->content }}</p>
                <span style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.3rem;">{{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}</span>
            </div>
        </div>
        @endif
    @empty
        <div style="text-align: center; padding: 2.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
            <i class="fa-solid fa-bullhorn" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; color: var(--accent-color); opacity: 0.5;"></i>
            لا توجد إعلانات حالياً
        </div>
    @endforelse
</div>

@endsection
