@extends('layouts.affairs')
@section('title', 'الرئيسية')
@section('subtitle', 'مرحباً، ' . (auth()->user()->full_name ?? 'موظف الشؤون'))

@push('styles')
<style>
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        margin-top: 1.5rem;
    }
    .section-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    .view-all {
        color: var(--accent-color);
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none;
    }
    .view-all:hover { text-decoration: underline; }

    /* â”€â”€ Stats â”€â”€ */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        gap: 1rem;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }
    .stat-icon {
        width: 50px; height: 50px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .stat-number { font-size: 2rem; font-weight: 900; line-height: 1; }
    .stat-label  { font-size: 0.85rem; color: var(--text-secondary); font-weight: 600; margin-top: 0.2rem; }

    /* â”€â”€ Alert Bar â”€â”€ */
    .alert-bar {
        background: rgba(239,68,68,0.08);
        border: 1px solid rgba(239,68,68,0.3);
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* â”€â”€ Carousel â”€â”€ */
    .announcements-carousel {
        display: flex;
        gap: 1rem;
        overflow-x: auto;
        padding-bottom: 1rem;
        scrollbar-width: none;
    }
    .announcements-carousel::-webkit-scrollbar { display: none; }

    .announcement-card {
        min-width: 280px;
        height: 160px;
        border-radius: 1rem;
        position: relative;
        overflow: hidden;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 1.25rem;
        box-shadow: var(--shadow);
    }
    .bg-gradient-1 { background: linear-gradient(135deg, #fce300 0%, #f59e0b 100%); }
    .bg-gradient-2 { background: linear-gradient(135deg, #111827 0%, #374151 100%); }
    .bg-gradient-3 { background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); }
    .bg-gradient-4 { background: linear-gradient(135deg, #065f46 0%, #10b981 100%); }
    .bg-gradient-5 { background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); }

    .ann-badge {
        position: absolute;
        top: 1rem; left: 1rem;
        background: rgba(0,0,0,0.3);
        color: white;
        padding: 0.2rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        backdrop-filter: blur(4px);
    }
    .announcement-card h3 {
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.4;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0,0,0,0.4);
    }
    .announcement-card p {
        font-size: 0.78rem;
        opacity: 0.85;
        margin: 0.3rem 0 0;
        text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Empty carousel */
    .empty-carousel {
        width: 100%;
        padding: 2rem;
        background: var(--bg-secondary);
        border-radius: 1rem;
        text-align: center;
        color: var(--text-secondary);
        font-weight: 600;
    }

    /* â”€â”€ Posts â”€â”€ */
    .post-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: var(--shadow);
    }
    .post-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .post-avatar {
        width: 42px; height: 42px;
        border-radius: 50%;
        background: var(--text-primary);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .post-author { font-weight: 800; font-size: 0.95rem; color: var(--text-primary); }
    .post-time   { font-size: 0.75rem; color: var(--text-secondary); }
    .post-category {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.15rem 0.6rem;
        border-radius: 1rem;
        background: rgba(252,227,0,0.2);
        color: #a16207;
        margin-bottom: 0.5rem;
    }
    .post-title   { font-size: 1.05rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--text-primary); }
    .post-content {
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .post-image {
        width: 100%;
        height: 180px;
        border-radius: 1rem;
        object-fit: cover;
        margin: 0.75rem 0 0;
    }
    .no-posts {
        text-align: center;
        padding: 3rem;
        color: var(--text-secondary);
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        font-weight: 600;
    }
    .no-posts i { font-size: 2.5rem; color: var(--accent-color); opacity: 0.4; margin-bottom: 0.75rem; display: block; }
</style>
@endpush

@section('content')

{{-- ── Stats ── --}}
<div class="stats-grid">
    <a href="{{ route('affairs.accounts') }}?role=student" class="stat-card">
        <div class="stat-icon" style="background:rgba(252,227,0,0.15); color:var(--accent-color);">
            <i class="fa-solid fa-user-graduate"></i>
        </div>
        <div>
            <div class="stat-number" style="color:var(--accent-color);">{{ $totalStudents }}</div>
            <div class="stat-label">إجمالي الطلاب</div>
        </div>
    </a>

    <a href="{{ route('affairs.accounts') }}?role=teacher" class="stat-card">
        <div class="stat-icon" style="background:rgba(59,130,246,0.1); color:#3b82f6;">
            <i class="fa-solid fa-chalkboard-user"></i>
        </div>
        <div>
            <div class="stat-number" style="color:#3b82f6;">{{ $totalTeachers }}</div>
            <div class="stat-label">إجمالي المعلمين</div>
        </div>
    </a>

    <a href="{{ route('affairs.leaves') }}" class="stat-card">
        <div class="stat-icon" style="background:rgba(239,68,68,0.1); color:#ef4444;">
            <i class="fa-solid fa-plane-departure"></i>
        </div>
        <div>
            <div class="stat-number" style="color:#ef4444;">{{ $pendingLeaves }}</div>
            <div class="stat-label">طلبات إجازة معلقة</div>
        </div>
    </a>
</div>





{{-- ── تنبيه طلبات الإجازة ── --}}
@if($pendingLeaves > 0)
<div class="alert-bar">
    <div style="display:flex; align-items:center; gap:0.75rem; color:#ef4444; font-weight:700;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        يوجد {{ $pendingLeaves }} طلب إجازة بانتظار المراجعة
    </div>
    <a href="{{ route('affairs.leaves') }}"
       style="background:#ef4444; color:white; padding:0.5rem 1.2rem; border-radius:0.5rem; font-weight:700; text-decoration:none; font-size:0.9rem;">
        مراجعة الطلبات
    </a>
</div>
@endif



{{-- ── منشورات الإدارة ── --}}
<div class="section-header">
    <h2 class="section-title">منشورات الإدارة</h2>
</div>

@forelse($posts as $post)
    @php
        $colors = ['#111827','#1d4ed8','#065f46','#7c3aed','#be123c','#b45309'];
        $color  = $colors[$loop->index % count($colors)];
        $initials = mb_substr($post->user->full_name ?? 'إ', 0, 1);

        $imgsArr = [];
        if (!empty($post->images)) {
            $imgsArr = is_string($post->images) ? json_decode($post->images, true) : $post->images;
        }
        if (empty($imgsArr) && !empty($post->image)) {
            $imgsArr = [$post->image];
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
        <div style="background: var(--bg-secondary); border: 1px solid rgba(128,128,128,0.2); border-radius: 1rem; margin-bottom: 1.5rem; padding: 1.25rem; display: flex; gap: 0.75rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <!-- Avatar column -->
            <div style="flex-shrink: 0;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background-color: {{ $color }}; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; font-size: 1.1rem;">
                    {{ $initials }}
                </div>
            </div>
            
            <!-- Content column -->
            <div style="flex: 1; min-width: 0;">
                <!-- Header -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                        <span style="font-weight: 700; color: var(--text-dark); font-size: 0.95rem;">{{ $post->user->full_name ?? 'الإدارة' }}</span>
                        @if(isset($post->user) && in_array($post->user->role, ['admin', 'hod', 'affairs']))
                            <i class="fa-solid fa-circle-check" style="color: #1d9bf0; font-size: 0.85rem;"></i>
                        @endif
                        <span style="color: var(--text-muted); font-size: 0.9rem;" dir="ltr">@admin · {{ $post->created_at->diffForHumans(null, true) }}</span>
                    </div>
            </div>
            
            <!-- Category/Tag (if any) -->
            @if($post->category)
            <div style="margin-bottom: 0.5rem;">
                <span style="background: rgba(29, 155, 240, 0.1); color: #1d9bf0; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 700;">{{ $post->category }}</span>
            </div>
            @endif

            <!-- Text Content -->
            <div style="color: var(--text-dark); font-size: 0.95rem; line-height: 1.6; margin-bottom: 0.75rem; white-space: pre-line;">
                @if($post->title)
                <strong style="display: block; margin-bottom: 0.25rem; font-size: 1.05rem;">{{ $post->title }}</strong>
                @endif
                {{ $post->content }}
            </div>

            <!-- Image Attachment -->
            @include('partials.image_slider', ['images' => $formattedImgs])
        </div>
    </div>
@empty
    <div class="no-posts">
        <i class="fa-regular fa-newspaper"></i>
        لا توجد منشورات حتى الآن
    </div>
@endforelse

@endsection

