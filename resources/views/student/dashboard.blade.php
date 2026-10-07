@extends('layouts.student')
@section('title', 'الرئيسية')
@section('subtitle', 'مرحباً، ' . (auth()->user()->full_name ?? 'الطالب'))

@push('styles')
<style>
    .student-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.1rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 1200px) {
        .student-stats-grid {
            grid-template-columns: repeat(5, 1fr);
        }
    }

    .stat-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color, rgba(0,0,0,0.06));
        border-radius: 1.25rem;
        padding: 1.25rem 1.4rem;
        box-shadow: 0 4px 15px -2px rgba(0,0,0,0.04);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 1rem;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        color: inherit;
        position: relative;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 25px -4px rgba(0,0,0,0.1);
        border-color: var(--accent-color, #eab308);
    }

    .stat-card-body {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
    }

    .stat-info {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        min-width: 0;
    }

    .stat-label {
        color: var(--text-secondary);
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.1;
        color: var(--text-primary);
        letter-spacing: -0.02em;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        transition: transform 0.25s ease;
    }
    .stat-card:hover .stat-icon {
        transform: scale(1.1);
    }

    .stat-icon-yellow { background: rgba(234, 179, 8, 0.15); color: #ca8a04; }
    .stat-icon-blue   { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
    .stat-icon-emerald{ background: rgba(16, 185, 129, 0.15); color: #059669; }
    .stat-icon-purple { background: rgba(168, 85, 247, 0.15); color: #9333ea; }
    .stat-icon-rose   { background: rgba(244, 63, 94, 0.15); color: #e11d48; }

    html.dark .stat-icon-yellow { background: rgba(234, 179, 8, 0.2); color: #facc15; }
    html.dark .stat-icon-blue   { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
    html.dark .stat-icon-emerald{ background: rgba(16, 185, 129, 0.2); color: #34d399; }
    html.dark .stat-icon-purple { background: rgba(168, 85, 247, 0.2); color: #c084fc; }
    html.dark .stat-icon-rose   { background: rgba(244, 63, 94, 0.2); color: #fb7185; }

    .stat-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: auto;
        padding-top: 0.6rem;
        border-top: 1px dashed var(--border-color, rgba(0,0,0,0.08));
    }
    .stat-hint {
        font-size: 0.75rem;
        color: var(--text-secondary);
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: color 0.2s;
    }
    .stat-card:hover .stat-hint {
        color: var(--accent-color, #ca8a04);
    }
    .stat-arrow {
        font-size: 0.75rem;
        opacity: 0.5;
        transition: transform 0.2s, opacity 0.2s;
    }
    .stat-card:hover .stat-arrow {
        transform: translateX(-3px);
        opacity: 1;
        color: var(--accent-color, #ca8a04);
    }

    /* Attendance bar */
    .att-bar-wrap {
        background: var(--bg-primary);
        border-radius: 2rem;
        height: 6px;
        overflow: hidden;
        margin-top: 0.4rem;
        width: 100%;
    }
    .att-bar {
        height: 100%;
        border-radius: 2rem;
        background: linear-gradient(90deg, #10b981, #34d399);
        transition: width 0.5s;
    }

    .section-title {
        font-size: 1.05rem;
        font-weight: 800;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
</style>
@endpush

@section('content')

{{-- Professional 5 Stats Cards Grid --}}
<div class="student-stats-grid">

    {{-- 1. موادي الدراسية --}}
    <a href="{{ route('student.courses') }}" class="stat-card">
        <div class="stat-card-body">
            <div class="stat-info">
                <span class="stat-label">المواد الدراسية</span>
                <div class="stat-value">{{ $courses->count() }}</div>
            </div>
            <div class="stat-icon stat-icon-blue">
                <i class="fa-solid fa-book-open"></i>
            </div>
        </div>
        <div class="stat-footer">
            <span class="stat-hint">عرض المقررات</span>
            <i class="fa-solid fa-arrow-left stat-arrow"></i>
        </div>
    </a>

    {{-- 2. الواجبات القادمة --}}
    <a href="{{ route('student.assignments') }}" class="stat-card">
        <div class="stat-card-body">
            <div class="stat-info">
                <span class="stat-label">الواجبات القادمة</span>
                <div class="stat-value">{{ $assignments->count() }}</div>
            </div>
            <div class="stat-icon stat-icon-yellow">
                <i class="fa-solid fa-file-pen"></i>
            </div>
        </div>
        <div class="stat-footer">
            <span class="stat-hint">تسليم الواجبات</span>
            <i class="fa-solid fa-arrow-left stat-arrow"></i>
        </div>
    </a>

    {{-- 3. نسبة الحضور --}}
    <a href="{{ route('student.attendance') }}" class="stat-card">
        <div class="stat-card-body">
            <div class="stat-info" style="width: 100%;">
                <span class="stat-label">نسبة الحضور</span>
                <div class="stat-value">{{ $attendanceRate !== null ? $attendanceRate . '%' : 'غير متاح' }}</div>
                @if($attendanceRate !== null)
                <div class="att-bar-wrap">
                    <div class="att-bar" style="width: {{ min(100, max(0, $attendanceRate)) }}%;"></div>
                </div>
                @endif
            </div>
            <div class="stat-icon stat-icon-emerald">
                <i class="fa-solid fa-clipboard-user"></i>
            </div>
        </div>
        <div class="stat-footer">
            <span class="stat-hint">سجل الحضور</span>
            <i class="fa-solid fa-arrow-left stat-arrow"></i>
        </div>
    </a>

    {{-- 4. متوسط الدرجات --}}
    <a href="{{ route('student.grades') }}" class="stat-card">
        <div class="stat-card-body">
            <div class="stat-info">
                <span class="stat-label">متوسط الدرجات</span>
                <div class="stat-value">{{ $avgGrade !== null ? $avgGrade . '%' : 'غير متاح' }}</div>
            </div>
            <div class="stat-icon stat-icon-purple">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>
        <div class="stat-footer">
            <span class="stat-hint">تقرير الأداء</span>
            <i class="fa-solid fa-arrow-left stat-arrow"></i>
        </div>
    </a>

    {{-- 5. كشف العلامات / بطاقة الطالب --}}
    <a href="{{ route('student.grades') }}" class="stat-card">
        <div class="stat-card-body">
            <div class="stat-info">
                <span class="stat-label">بطاقة الطالب</span>
                <div class="stat-value" style="font-size: 1.25rem;">كشف العلامات</div>
            </div>
            <div class="stat-icon stat-icon-rose">
                <i class="fa-solid fa-id-card"></i>
            </div>
        </div>
        <div class="stat-footer">
            <span class="stat-hint">السجل الأكاديمي</span>
            <i class="fa-solid fa-arrow-left stat-arrow"></i>
        </div>
    </a>

</div>

{{-- Announcements --}}
<div style="margin-bottom: 2rem;">
    <p class="section-title">
        <i class="fa-solid fa-bullhorn" style="color: var(--accent-color, #eab308);"></i>
        آخر الأخبار والإعلانات
    </p>

    @forelse($announcements as $ann)
        @php
            $imgsArr = [];
            if (!empty($ann->images)) {
                $imgsArr = is_string($ann->images) ? json_decode($ann->images, true) : $ann->images;
            }
            if (empty($imgsArr) && !empty($ann->image)) {
                $imgsArr = [$ann->image];
            }

            $formattedImgs = [];
            if (is_array($imgsArr)) {
                foreach ($imgsArr as $img) {
                    if ($img) {
                        $formattedImgs[] = str_starts_with($img, 'http') ? $img : asset('storage/' . ltrim($img, '/'));
                    }
                }
            }
        @endphp

        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color, rgba(0,0,0,0.06)); border-radius: 1.25rem; margin-bottom: 1.25rem; padding: 1.25rem; display: flex; gap: 0.75rem; box-shadow: 0 4px 15px -2px rgba(0,0,0,0.04);">
            <!-- Avatar column -->
            <div style="flex-shrink: 0;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background-color: var(--accent-color, #1d9bf0); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #000; font-size: 1.1rem;">
                    إ
                </div>
            </div>
            
            <!-- Content column -->
            <div style="flex: 1; min-width: 0;">
                <!-- Header -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                        <span style="font-weight: 700; color: var(--text-dark); font-size: 0.95rem;">الإدارة</span>
                        <i class="fa-solid fa-circle-check" style="color: #1d9bf0; font-size: 0.85rem;"></i>
                        <span style="color: var(--text-muted); font-size: 0.85rem;" dir="ltr">@admin · {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans(null, true) }}</span>
                    </div>
                </div>

                <!-- Text Content -->
                <div style="color: var(--text-dark); font-size: 0.95rem; line-height: 1.6; margin-bottom: 0.75rem; white-space: pre-line;">
                    @if($ann->title)
                    <strong style="display: block; margin-bottom: 0.25rem; font-size: 1.05rem;">{{ $ann->title }}</strong>
                    @endif
                    {{ $ann->content }}
                </div>

                <!-- Image Attachment -->
                @include('partials.image_slider', ['images' => $formattedImgs])
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 2.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary); border: 1px solid var(--border-color, rgba(0,0,0,0.06));">
            <i class="fa-solid fa-bullhorn" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; color: var(--accent-color, #eab308); opacity: 0.5;"></i>
            لا توجد إعلانات حالياً
        </div>
    @endforelse
</div>

@endsection
