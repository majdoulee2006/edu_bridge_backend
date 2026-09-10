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

{{-- Web Attendance Quick Banner --}}
<a href="{{ route('student.attendance') }}" class="mobile-notice hover:scale-[1.01] transition-transform cursor-pointer">
    <i class="fa-solid fa-qrcode text-yellow-400 text-2xl"></i>
    <div class="mobile-notice-text flex-1">
        <div class="mobile-notice-title flex items-center justify-between">
            <span>تسجيل الحضور الفوري (QR والوجه)</span>
            <span class="bg-yellow-400 text-black text-xs font-extrabold px-3 py-1 rounded-full">متاح الآن بالويب</span>
        </div>
        افتح كاميرا الجوال أو اللابتوب مباشرة وقم بمسح رمز QR والتحقق من وجهك دون الحاجة لانتظار بناء APK.
    </div>
</a>

{{-- Stats --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">

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

    <a href="{{ route('student.grades') }}" class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-id-card"></i></div>
        <div>
            <div class="stat-value" style="font-size: 1.4rem;">كشف العلامات</div>
            <div class="stat-label">بطاقة الطالب الأكاديمية</div>
            <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
        </div>
    </a>

    <a href="{{ route('student.grades') }}" class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-chart-bar"></i></div>
        <div style="flex: 1;">
            <div class="stat-value">{{ $avgGrade !== null ? $avgGrade . '%' : 'غير متاح' }}</div>
            <div class="stat-label">متوسط الدرجات</div>
            <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
        </div>
    </a>

    <a href="{{ route('student.attendance') }}" class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-clipboard-user"></i></div>
        <div style="flex: 1;">
            <div class="stat-value">{{ $attendanceRate !== null ? $attendanceRate . '%' : 'غير متاح' }}</div>
            <div class="stat-label">نسبة الحضور</div>
            @if($attendanceRate !== null)
            <div class="att-bar-wrap">
                <div class="att-bar" style="width: {{ $attendanceRate }}%;"></div>
            </div>
            @endif
            <div class="stat-hint" style="margin-top: 0.4rem;"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
        </div>
    </a>

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
    @php
        $announcementsCol = collect($announcements ?? []);
        $todayAnnouncements = $announcementsCol->filter(fn($item) => \Carbon\Carbon::parse($item->created_at)->isToday());
        $previousAnnouncements = $announcementsCol->reject(fn($item) => \Carbon\Carbon::parse($item->created_at)->isToday());
    @endphp

    {{-- أخبار اليوم --}}
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; display: inline-block; box-shadow: 0 0 8px #22c55e;"></span>
            <p class="section-title" style="margin-bottom: 0;">
                <i class="fa-solid fa-calendar-day" style="color: var(--accent-color);"></i>
                أخبار اليوم
            </p>
        </div>

        @forelse($todayAnnouncements as $ann)
            @php
                $imgUrl = ($ann->image ?? false) ? asset('storage/' . $ann->image) : null;
                $gradients = [
                    'linear-gradient(135deg,#1a2633,#f2f20d33)',
                    'linear-gradient(135deg,#0f2027,#203a43,#2c5364)',
                    'linear-gradient(135deg,#1a1a2e,#16213e,#0f3460)',
                ];
                $grad = $gradients[$loop->index % count($gradients)];
                $icons = ['fa-bullhorn','fa-bell','fa-star'];
                $icon  = $icons[$loop->index % count($icons)];
            @endphp

            @if($loop->first)
            <div style="display: flex; flex-direction: row-reverse; border-radius: 1.25rem; overflow: hidden; background: var(--bg-secondary); box-shadow: var(--shadow); margin-bottom: 1rem; min-height: 180px; border: 1px solid rgba(34,197,94,0.3);">
                <div style="width: 35%; flex-shrink: 0; background: {{ $grad }}; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    @if($imgUrl)
                        <img src="{{ $imgUrl }}" style="width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;" onerror="this.style.display='none'">
                    @endif
                    <i class="fa-solid {{ $icon }}" style="font-size: 3.5rem; color: rgba(242,242,13,0.25);"></i>
                </div>
                <div style="flex: 1; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <span style="background: #22c55e; color: #ffffff; padding: 0.15rem 0.65rem; border-radius: 2rem; font-size: 0.72rem; font-weight: 800;">اليوم</span>
                            <span style="background: var(--accent-color); color: #1a1a1a; padding: 0.15rem 0.65rem; border-radius: 2rem; font-size: 0.72rem; font-weight: 700;">إعلان جديد</span>
                        </div>
                        <h4 style="font-size: 1.05rem; font-weight: 800; margin-bottom: 0.4rem;">{{ $ann->title }}</h4>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.5;">{{ Str::limit($ann->content, 180) }}</p>
                    </div>
                    <div style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.5rem;">
                        <i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}
                    </div>
                </div>
            </div>
            @else
            <div style="display: flex; flex-direction: row-reverse; border-radius: 1.25rem; overflow: hidden; background: var(--bg-secondary); box-shadow: var(--shadow); margin-bottom: 0.75rem; min-height: 100px;">
                <div style="width: 140px; flex-shrink: 0; background: {{ $grad }}; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    @if($imgUrl)
                        <img src="{{ $imgUrl }}" style="width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;" onerror="this.style.display='none'">
                    @endif
                    <i class="fa-solid {{ $icon }}" style="font-size: 2.2rem; color: rgba(242,242,13,0.3);"></i>
                </div>
                <div style="flex: 1; padding: 0.85rem 1.1rem; display: flex; flex-direction: column; justify-content: center;">
                    <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.2rem;">
                        <span style="background: #22c55e; color: #fff; padding: 0.1rem 0.5rem; border-radius: 1rem; font-size: 0.68rem; font-weight: 800;">اليوم</span>
                        <h4 style="font-size: 0.9rem; font-weight: 700; margin: 0;">{{ $ann->title }}</h4>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-secondary); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin: 0;">{{ $ann->content }}</p>
                    <span style="font-size: 0.74rem; color: var(--text-secondary); margin-top: 0.3rem;">{{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}</span>
                </div>
            </div>
            @endif
        @empty
            <div style="text-align: center; padding: 1.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary); border: 1px dashed var(--border-color); margin-bottom: 1.5rem;">
                <i class="fa-regular fa-calendar-check" style="font-size: 1.5rem; margin-bottom: 0.4rem; display: block; color: var(--text-secondary); opacity: 0.5;"></i>
                لا توجد أخبار جديدة نشرت اليوم.
            </div>
        @endforelse
    </div>

    {{-- الأخبار السابقة --}}
    <div>
        <p class="section-title" style="margin-bottom: 1rem;">
            <i class="fa-solid fa-clock-rotate-left" style="color: var(--text-secondary);"></i>
            الأخبار السابقة
        </p>

        @forelse($previousAnnouncements as $ann)
            @php
                $imgUrl = ($ann->image ?? false) ? asset('storage/' . $ann->image) : null;
                $gradients = [
                    'linear-gradient(135deg,#1a1a2e,#16213e,#0f3460)',
                    'linear-gradient(135deg,#232526,#414345)',
                    'linear-gradient(135deg,#0f2027,#203a43,#2c5364)',
                ];
                $grad = $gradients[$loop->index % count($gradients)];
                $icons = ['fa-bullhorn','fa-bookmark','fa-flag'];
                $icon  = $icons[$loop->index % count($icons)];
            @endphp

            <div style="display: flex; flex-direction: row-reverse; border-radius: 1.25rem; overflow: hidden; background: var(--bg-secondary); box-shadow: var(--shadow); margin-bottom: 0.75rem; min-height: 100px; opacity: 0.95;">
                <div style="width: 140px; flex-shrink: 0; background: {{ $grad }}; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    @if($imgUrl)
                        <img src="{{ $imgUrl }}" style="width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;" onerror="this.style.display='none'">
                    @endif
                    <i class="fa-solid {{ $icon }}" style="font-size: 2.2rem; color: rgba(255,255,255,0.2);"></i>
                </div>
                <div style="flex: 1; padding: 0.85rem 1.1rem; display: flex; flex-direction: column; justify-content: center;">
                    <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 0.2rem;">{{ $ann->title }}</h4>
                    <p style="font-size: 0.8rem; color: var(--text-secondary); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin: 0;">{{ $ann->content }}</p>
                    <span style="font-size: 0.74rem; color: var(--text-secondary); margin-top: 0.3rem;">
                        <i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($ann->created_at)->format('Y-m-d') }} ({{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }})
                    </span>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 1.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary); border: 1px dashed var(--border-color);">
                <i class="fa-solid fa-bullhorn" style="font-size: 1.5rem; margin-bottom: 0.4rem; display: block; color: var(--accent-color); opacity: 0.5;"></i>
                لا توجد أخبار سابقة.
            </div>
        @endforelse
    </div>
</div>

@endsection
