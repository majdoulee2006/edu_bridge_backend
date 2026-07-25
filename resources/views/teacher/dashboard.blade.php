@extends('layouts.teacher')
@section('title', 'الرئيسية')
@section('subtitle', 'مرحباً، ' . (auth()->user()->full_name ?? 'أستاذ'))

@push('styles')
<style>
    /* ===== Stat Cards ===== */
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
        position: relative;
        overflow: hidden;
    }
    .stat-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--accent-color);
        opacity: 0;
        transition: opacity 0.2s;
        border-radius: inherit;
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

    /* ===== Section title ===== */
    .section-title { font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; }

    /* ===== Notif & Schedule ===== */
    .notif-card { background: var(--bg-secondary); border-radius: 1rem; padding: 1.25rem; box-shadow: var(--shadow); margin-bottom: 0.75rem; display: flex; gap: 1rem; align-items: flex-start; }
    .notif-dot  { width: 10px; height: 10px; border-radius: 50%; background: var(--accent-color); flex-shrink: 0; margin-top: 5px; }
    .schedule-row { background: var(--bg-secondary); border-radius: 1rem; padding: 1rem 1.25rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 1rem; box-shadow: var(--shadow); border-right: 4px solid var(--accent-color); }

    /* ===== Announcement cards ===== */
    .announce-card {
        border-radius: 1.25rem; overflow: hidden;
        background-color: var(--bg-secondary);
        box-shadow: var(--shadow); margin-bottom: 1.25rem;
        transition: transform 0.2s;
    }
    .announce-card:hover { transform: translateY(-2px); }
    .announce-image-area {
        width: 100%; height: 160px;
        background-color: #fce30020;
        display: flex; align-items: center; justify-content: center;
        color: #ca8a04; position: relative;
    }
    .announce-image-area img { width: 100%; height: 160px; object-fit: fill; }
    .announce-badge {
        position: absolute; top: 0.75rem; right: 0.75rem;
        background-color: var(--accent-color); color: #1a1a1a;
        font-size: 0.78rem; font-weight: 700;
        padding: 0.2rem 0.65rem; border-radius: 2rem;
    }
    .announce-body { padding: 1.25rem 1.25rem 1rem; }
    .announce-meta { display: flex; justify-content: space-between; color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.5rem; }
    .announce-title { font-size: 1rem; font-weight: 800; margin-bottom: 0.35rem; color: var(--text-primary); }
    .announce-excerpt { color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6; }

    /* ===== Modals ===== */
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
    .modal-close {
        background: none; border: none; font-size: 1.3rem;
        color: var(--text-secondary); cursor: pointer;
    }
    .modal-close:hover { color: var(--text-primary); }

    /* Course list item */
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

    /* Assignment status badges */
    .badge-graded  { background: hsl(120,70%,90%); color: hsl(120,50%,30%); }
    .badge-pending { background: hsl(30,70%,90%);  color: hsl(30,50%,30%);  }
    .badge-active  { background: var(--accent-color); color: #1a1a1a; }
    .status-badge  { padding: 0.2rem 0.6rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; }

    /* ===== Modal screens transition styles ===== */
    .modal-screen {
        display: none;
        flex-direction: column;
        animation: fadeIn 0.2s ease-in-out;
    }
    .modal-screen.active {
        display: flex;
    }
    
    .modal-list-item-btn {
        width: 100%;
        text-align: right;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 0.875rem;
        padding: 1rem 1.25rem;
        margin-bottom: 0.6rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        font-family: inherit;
        color: var(--text-primary);
        transition: transform 0.2s, border-color 0.2s;
        border-right: 4px solid var(--accent-color);
    }
    
    .modal-list-item-btn:hover {
        transform: translateY(-2px);
        border-color: var(--accent-color);
    }
    
    .back-btn-modal {
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        font-size: 1.1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-family: inherit;
        font-weight: 700;
        transition: color 0.2s;
    }
    
    .back-btn-modal:hover {
        color: var(--accent-color);
    }

    .modal-lecture-card {
        background: var(--bg-primary);
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        margin-bottom: 0.6rem;
        border: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
    
    .modal-upload-area {
        border: 2px dashed var(--border-color);
        border-radius: 0.875rem;
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
        position: relative;
        background: var(--bg-primary);
        margin-bottom: 1.25rem;
    }

    .modal-upload-area:hover, .modal-upload-area.drag-over {
        border-color: var(--accent-color);
        background: color-mix(in srgb, var(--accent-color) 6%, var(--bg-primary));
    }

    .modal-upload-area input[type="file"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .modal-upload-icon {
        font-size: 2rem;
        color: var(--accent-color);
        margin-bottom: 0.5rem;
    }

    .modal-upload-text {
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 0.25rem;
    }

    .modal-upload-hint {
        font-size: 0.78rem;
        color: var(--text-secondary);
    }

    .modal-file-preview {
        display: none;
        align-items: center;
        gap: 0.75rem;
        background: var(--bg-primary);
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        margin-bottom: 1.25rem;
        border: 1px solid var(--border-color);
    }

    .modal-file-preview.visible {
        display: flex;
    }

    .modal-file-preview-icon {
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    .modal-file-preview-name {
        flex: 1;
        font-size: 0.88rem;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .modal-file-preview-remove {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 1rem;
    }
    
    .modal-form-input {
        width: 100%;
        padding: 0.85rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: inherit;
        font-size: 0.95rem;
        transition: border-color 0.2s;
        margin-bottom: 1rem;
    }

    .modal-form-input:focus {
        outline: none;
        border-color: var(--accent-color);
    }
</style>
@endpush

@section('content')

{{-- ===== Stats (2 cards) ===== --}}
<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem; margin-bottom: 2rem;">

    <div class="stat-card" onclick="openModal('courses-modal')">
        <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
        <div>
            <div class="stat-value">{{ $courses->count() }}</div>
            <div class="stat-label">المواد الدراسية</div>
            <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
        </div>
    </div>

    <div class="stat-card" onclick="openModal('assignments-modal')">
        <div class="stat-icon"><i class="fa-solid fa-file-pen"></i></div>
        <div>
            <div class="stat-value">{{ $recentAssignments->count() }}</div>
            <div class="stat-label">الواجبات النشطة</div>
            <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
        </div>
    </div>

</div>

{{-- ===== Announcements ===== --}}
<div style="margin-bottom: 2rem;">
    <p class="section-title">
        <i class="fa-solid fa-bullhorn" style="color: var(--accent-color);"></i>
        آخر الأخبار والإعلانات
    </p>

    @forelse($announcements as $ann)
        @php
            $imgUrl  = ($ann->image ?? false) ? asset('storage/' . $ann->image) : null;
            $isOwner = isset($ann->user_id) && $ann->user_id == Auth::id();
            $annId   = $ann->announcement_id ?? $ann->id;
        @endphp

        @if($loop->first)
        {{-- كارت كبير --}}
        <div style="display: flex; flex-direction: row-reverse; border-radius: 1.25rem; overflow: hidden; background: var(--bg-secondary); box-shadow: var(--shadow); margin-bottom: 1.25rem; min-height: 200px;">
            {{-- صورة يسار --}}
            <div style="width: 38%; flex-shrink: 0; background: #1e293b; position: relative; overflow: hidden;">
                @if($imgUrl)
                    <a href="{{ $imgUrl }}" target="_blank" download style="display: block; position: absolute; inset: 0;">
                        <img src="{{ $imgUrl }}" style="width: 100%; height: 100%; object-fit: fill;">
                        <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0); transition: background 0.2s;"
                             onmouseover="this.style.background='rgba(0,0,0,0.35)'"
                             onmouseout="this.style.background='rgba(0,0,0,0)'">
                            <i class="fa-solid fa-download" style="color: white; font-size: 1.8rem; opacity: 0;"></i>
                        </div>
                    </a>
                @else
                    <i class="fa-solid fa-bullhorn" style="position: absolute; inset: 0; margin: auto; font-size: 4rem; color: rgba(255,255,255,0.08); width: fit-content; height: fit-content;"></i>
                @endif
            </div>
            {{-- نص يمين --}}
            <div style="flex: 1; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <span style="background: var(--accent-color); color: #1a1a1a; padding: 0.2rem 0.75rem; border-radius: 2rem; font-size: 0.78rem; font-weight: 700;">إعلان هام</span>
                        @if($isOwner)
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('teacher.announcements.edit', $annId) }}"
                               style="display: flex; align-items: center; gap: 0.25rem; padding: 0.3rem 0.6rem; border-radius: 0.5rem; background: #eff6ff; color: #1d4ed8; font-size: 0.75rem; font-weight: 700; text-decoration: none;">
                                <i class="fa-solid fa-pen" style="font-size: 0.7rem;"></i> تعديل
                            </a>
                            <form action="{{ route('teacher.announcements.delete', $annId) }}" method="POST" onsubmit="return confirm('حذف الإعلان؟')" style="margin: 0;">
                                @csrf
                                <button type="submit" style="display: flex; align-items: center; gap: 0.25rem; padding: 0.3rem 0.6rem; border-radius: 0.5rem; background: #fef2f2; color: #dc2626; font-size: 0.75rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit;">
                                    <i class="fa-solid fa-trash" style="font-size: 0.7rem;"></i> حذف
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                    <h4 style="font-size: 1.05rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--text-primary);">{{ $ann->title }}</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6;">{{ Str::limit($ann->content, 200) }}</p>
                </div>
                <div style="margin-top: 0.75rem; font-size: 0.78rem; color: var(--text-secondary);">
                    <i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}
                </div>
            </div>
        </div>
        @else
        {{-- كروت أخرى --}}
        <div style="display: flex; flex-direction: row-reverse; border-radius: 1.25rem; overflow: hidden; background: var(--bg-secondary); box-shadow: var(--shadow); margin-bottom: 0.75rem; min-height: 110px;">
            <div style="width: 150px; flex-shrink: 0; background: #1e293b; position: relative; overflow: hidden;">
                @if($imgUrl)
                    <a href="{{ $imgUrl }}" target="_blank" download style="display: block; position: absolute; inset: 0;">
                        <img src="{{ $imgUrl }}" style="width: 100%; height: 100%; object-fit: fill;">
                    </a>
                @else
                    <i class="fa-solid fa-bullhorn" style="position: absolute; inset: 0; margin: auto; font-size: 2rem; color: rgba(255,255,255,0.1); width: fit-content; height: fit-content;"></i>
                @endif
            </div>
            <div style="flex: 1; padding: 1rem 1.25rem; display: flex; flex-direction: column; justify-content: center;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem;">
                    <span style="font-size: 0.72rem; font-weight: 700; color: var(--text-secondary);">إداري</span>
                    @if($isOwner)
                    <div style="display: flex; gap: 0.4rem;">
                        <a href="{{ route('teacher.announcements.edit', $annId) }}"
                           style="padding: 0.25rem 0.5rem; border-radius: 0.4rem; background: #eff6ff; color: #1d4ed8; font-size: 0.7rem; text-decoration: none;">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <form action="{{ route('teacher.announcements.delete', $annId) }}" method="POST" onsubmit="return confirm('حذف؟')" style="margin: 0;">
                            @csrf
                            <button type="submit" style="padding: 0.25rem 0.5rem; border-radius: 0.4rem; background: #fef2f2; color: #dc2626; font-size: 0.7rem; border: none; cursor: pointer;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.3rem;">{{ $ann->title }}</h4>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.3rem; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $ann->content }}</p>
                <span style="font-size: 0.75rem; color: var(--text-secondary);">{{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}</span>
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




{{-- ================================================================ --}}
{{--  MODAL: قائمة المواد الدراسية                                    --}}
{{-- ================================================================ --}}
<div id="courses-modal" class="modal-overlay">
    <div class="modal-card" style="max-width: 600px;">
        
        <!-- Screen 1: Course List -->
        <div id="modal-screen-courses" class="modal-screen active">
            <div class="modal-header">
                <h3 style="font-weight: 800; font-size: 1.15rem; margin: 0;">
                    <i class="fa-solid fa-book-open" style="color: var(--accent-color);"></i>
                    موادي الدراسية
                </h3>
                <button class="modal-close" onclick="closeCoursesModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div style="max-height: 450px; overflow-y: auto; padding-inline: 0.2rem;">
                @forelse($courses as $c)
                    @php
                        $courseLecturesCount = $lectures->where('course_id', $c->course_id)->count();
                    @endphp
                    <button class="modal-list-item-btn" onclick="showCourseLectures({{ $c->course_id }}, '{{ addslashes($c->title) }}')">
                        <div class="list-icon"><i class="fa-solid fa-chalkboard"></i></div>
                        <div style="flex: 1; text-align: right;">
                            <div class="list-item-title">{{ $c->title }}</div>
                            <div class="list-item-sub" style="font-size: 0.8rem; margin-top: 0.2rem;">
                                @if($c->level)
                                    <i class="fa-solid fa-layer-group"></i> {{ $c->level }}
                                @endif
                                &nbsp;&middot;&nbsp;
                                <i class="fa-solid fa-file-video"></i> {{ $courseLecturesCount }} محاضرة مرفوعة
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-left" style="color: var(--text-secondary); margin-inline-start: auto;"></i>
                    </button>
                @empty
                    <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                        <i class="fa-solid fa-book-open" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
                        لا توجد مواد مسندة حالياً
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Screen 2: Lectures list of selected Course -->
        <div id="modal-screen-lectures" class="modal-screen">
            <div class="modal-header" style="margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <button class="back-btn-modal" onclick="goBackToCourses()">
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <h3 id="selected-course-title-modal" style="font-weight: 800; font-size: 1.15rem; margin: 0;">المحاضرات</h3>
                </div>
                <button class="modal-close" onclick="closeCoursesModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <span style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 700;" id="lectures-count-modal">0 محاضرة</span>
                <button onclick="showUploadScreen()" style="background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.5rem; padding: 0.5rem 1rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.82rem; display: flex; align-items: center; gap: 0.4rem;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> رفع محاضرة جديدة
                </button>
            </div>

            <div style="max-height: 400px; overflow-y: auto; padding-inline: 0.2rem;" id="modal-lectures-container">
                @foreach($lectures as $l)
                    @php
                        $iconColor = 'var(--accent-color)';
                        $iconClass = 'fa-chalkboard';
                        if ($l->file_path) {
                            if ($l->file_type === 'image') {
                                $iconClass = 'fa-image';
                                $iconColor = '#3b82f6';
                            } elseif ($l->file_type === 'video') {
                                $iconClass = 'fa-circle-play';
                                $iconColor = '#a855f7';
                            } else {
                                $iconClass = 'fa-file-lines';
                                $iconColor = '#eab308';
                            }
                        }
                    @endphp
                    <div class="modal-lecture-card" data-course-id="{{ $l->course_id }}">
                        <div style="display: flex; gap: 0.85rem; align-items: center; width: 80%;">
                            <div style="width: 40px; height: 40px; border-radius: 0.75rem; background: color-mix(in srgb, {{ $iconColor }} 12%, var(--bg-primary)); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: {{ $iconColor }}; flex-shrink: 0; border: 1px solid var(--border-color);">
                                <i class="fa-solid {{ $iconClass }}"></i>
                            </div>
                            <div style="overflow: hidden;">
                                <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-primary); text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">{{ $l->title }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.15rem; display: flex; gap: 0.5rem;">
                                    <span><i class="fa-regular fa-calendar"></i> {{ \Carbon\Carbon::parse($l->created_at)->format('Y-m-d') }}</span>
                                    @if($l->file_path)
                                        <span>&middot;</span>
                                        <a href="{{ asset('storage/' . $l->file_path) }}" target="_blank" style="color: var(--accent-color); font-weight: 700; text-decoration: none;">📎 عرض الملف</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <form action="{{ route('teacher.lectures.delete', $l->lesson_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه المحاضرة؟')" style="margin: 0;">
                            @csrf
                            <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.35rem; font-size: 0.95rem; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform='scale(1)'">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
                
                <div id="no-lectures-modal-message" style="display: none; text-align: center; padding: 3rem 1.5rem; color: var(--text-secondary);">
                    <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; color: var(--accent-color); opacity: 0.5;"></i>
                    <p style="font-size: 0.95rem; font-weight: 700; margin: 0 0 0.2rem 0; color: var(--text-primary);">لا توجد محاضرات مرفوعة</p>
                    <p style="font-size: 0.8rem; margin: 0;">انقر على الزر أعلاه لرفع أول محاضرة لهذه المادة.</p>
                </div>
            </div>
        </div>

        <!-- Screen 3: Upload Lecture Form -->
        <div id="modal-screen-upload" class="modal-screen">
            <div class="modal-header" style="margin-bottom: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <button class="back-btn-modal" onclick="goBackToLectures()">
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <h3 style="font-weight: 800; font-size: 1.15rem; margin: 0;">رفع محاضرة جديدة</h3>
                </div>
                <button class="modal-close" onclick="closeCoursesModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <form id="modal-upload-lecture-form" action="{{ route('teacher.lectures.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="course_id" id="modal-upload-course-id">

                <div>
                    <label style="display:block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.85rem;">عنوان المحاضرة</label>
                    <input type="text" name="title" class="modal-form-input" placeholder="أدخل عنوان المحاضرة هنا" required>
                </div>

                <div>
                    <label style="display:block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.85rem;">الوصف (اختياري)</label>
                    <textarea name="description" class="modal-form-input" rows="2" placeholder="ملاحظات أو وصف مختصر..." style="resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.85rem;">الملف المرفق</label>
                    <div class="modal-upload-area" id="modal-upload-area"
                         ondragover="event.preventDefault(); this.classList.add('drag-over')"
                         ondragleave="this.classList.remove('drag-over')"
                         ondrop="this.classList.remove('drag-over')">
                        <input type="file" name="attachment" id="modal-file-input"
                               accept="image/*,video/*,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
                               onchange="previewModalFile(this)">
                        <div id="modal-upload-placeholder">
                            <div class="modal-upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                            <div class="modal-upload-text" style="font-size: 0.88rem;">اسحب وأفلت الملف هنا أو انقر للاختيار</div>
                            <div class="modal-upload-hint" style="font-size: 0.72rem;">صورة، فيديو، مستند، أو ZIP (حجم غير محدود)</div>
                        </div>
                    </div>

                    <div class="modal-file-preview" id="modal-file-preview">
                        <span class="modal-file-preview-icon" id="modal-preview-icon">📎</span>
                        <span class="modal-file-preview-name" id="modal-preview-name"></span>
                        <span class="modal-file-preview-size" id="modal-preview-size" style="color: var(--text-secondary); font-size: 0.75rem; white-space: nowrap;"></span>
                        <button type="button" class="modal-file-preview-remove" onclick="removeModalFile()" title="حذف الملف">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem;">
                    <button type="submit" style="flex: 1; padding: 0.75rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-family: inherit; font-size: 0.95rem;">
                        <i class="fa-solid fa-cloud-arrow-up"></i> رفع الآن
                    </button>
                    <button type="button" onclick="goBackToLectures()" style="flex: 1; padding: 0.75rem; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.95rem;">إلغاء</button>
                </div>
            </form>
        </div>

    </div>
</div>


{{-- ================================================================ --}}
{{--  MODAL: قائمة الواجبات                                           --}}
{{-- ================================================================ --}}
<div id="assignments-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-weight: 800; font-size: 1.1rem;">
                <i class="fa-solid fa-file-pen" style="color: var(--accent-color);"></i>
                الواجبات النشطة
            </h3>
            <button class="modal-close" onclick="closeModal('assignments-modal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        @forelse($recentAssignments as $a)
            @php
                $statusClass = 'badge-active';
                $statusText  = 'نشط';
                if(($a->graded_count ?? 0) >= ($a->submissions_count ?? 1) && ($a->submissions_count ?? 0) > 0) {
                    $statusClass = 'badge-graded';
                    $statusText  = 'تم التصحيح';
                } elseif(($a->submissions_count ?? 0) > 0) {
                    $statusClass = 'badge-pending';
                    $statusText  = 'قيد التصحيح';
                }
            @endphp
            <div class="list-item">
                <div class="list-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.2rem;">
                        <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                        <span class="list-item-title">{{ $a->title }}</span>
                    </div>
                    <div class="list-item-sub">
                        <i class="fa-solid fa-book"></i> {{ $a->course_title }}
                        &nbsp;Â·&nbsp;
                        <i class="fa-solid fa-calendar"></i> {{ \Carbon\Carbon::parse($a->due_date)->format('Y-m-d') }}
                        @if(isset($a->submissions_count))
                            &nbsp;Â·&nbsp;
                            <i class="fa-solid fa-users"></i> {{ $a->submissions_count }} تسليم
                        @endif
                    </div>
                </div>
                <a href="{{ route('teacher.assignments.submissions', $a->assignment_id) }}"
                   style="color: var(--accent-color); font-size: 1rem;"
                   title="عرض الردود"
                   onclick="closeModal('assignments-modal')">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            </div>
        @empty
            <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <i class="fa-solid fa-file-circle-plus" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
                لا توجد واجبات حالياً
            </div>
        @endforelse

        <div style="margin-top: 1rem; text-align: center;">
            <a href="{{ route('teacher.assignments') }}"
               onclick="closeModal('assignments-modal')"
               style="color: var(--accent-color); font-weight: 700; font-size: 0.9rem; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> عرض كل الواجبات
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
// Close on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            if (this.id === 'courses-modal') {
                closeCoursesModal();
            } else {
                closeModal(this.id);
            }
        }
    });
});

/* ===== Courses Modal Interactive flow ===== */
let currentSelectedCourseId = null;
let currentSelectedCourseTitle = "";

function showCourseLectures(courseId, courseTitle) {
    currentSelectedCourseId = courseId;
    currentSelectedCourseTitle = courseTitle;
    
    document.getElementById('selected-course-title-modal').textContent = courseTitle;
    document.getElementById('modal-upload-course-id').value = courseId;
    
    let count = 0;
    document.querySelectorAll('.modal-lecture-card').forEach(card => {
        if (card.getAttribute('data-course-id') == courseId) {
            card.style.display = 'flex';
            count++;
        } else {
            card.style.display = 'none';
        }
    });
    
    document.getElementById('lectures-count-modal').textContent = count + ' محاضرة مرفوعة';
    
    if (count === 0) {
        document.getElementById('no-lectures-modal-message').style.display = 'block';
    } else {
        document.getElementById('no-lectures-modal-message').style.display = 'none';
    }
    
    document.getElementById('modal-screen-courses').classList.remove('active');
    document.getElementById('modal-screen-lectures').classList.add('active');
}

function goBackToCourses() {
    document.getElementById('modal-screen-lectures').classList.remove('active');
    document.getElementById('modal-screen-courses').classList.add('active');
}

function showUploadScreen() {
    document.getElementById('modal-screen-lectures').classList.remove('active');
    document.getElementById('modal-screen-upload').classList.add('active');
}

function goBackToLectures() {
    document.getElementById('modal-screen-upload').classList.remove('active');
    document.getElementById('modal-screen-lectures').classList.add('active');
    removeModalFile();
}

function closeCoursesModal() {
    closeModal('courses-modal');
    setTimeout(() => {
        document.getElementById('modal-screen-lectures').classList.remove('active');
        document.getElementById('modal-screen-upload').classList.remove('active');
        document.getElementById('modal-screen-courses').classList.add('active');
        removeModalFile();
    }, 300);
}

/* ===== File preview logic inside modal ===== */
const modalIconMap = {
    image:    { icon: '🖼️', label: 'صورة' },
    video:    { icon: '🎬', label: 'فيديو' },
    pdf:      { icon: '📄', label: 'PDF' },
    word:     { icon: '📝', label: 'Word' },
    ppt:      { icon: '📊', label: 'PowerPoint' },
    excel:    { icon: '📊', label: 'Excel' },
    zip:      { icon: '📦', label: 'ZIP' },
    default:  { icon: '📎', label: 'ملف' },
};

function getModalFileIcon(mime, name) {
    if (mime.startsWith('image/'))  return modalIconMap.image;
    if (mime.startsWith('video/'))  return modalIconMap.video;
    if (mime === 'application/pdf') return modalIconMap.pdf;
    if (mime.includes('word') || name.endsWith('.doc') || name.endsWith('.docx')) return modalIconMap.word;
    if (mime.includes('presentation') || name.endsWith('.ppt') || name.endsWith('.pptx')) return modalIconMap.word;
    if (mime.includes('excel') || name.endsWith('.xls') || name.endsWith('.xlsx')) return modalIconMap.excel;
    if (name.endsWith('.zip'))      return modalIconMap.zip;
    return modalIconMap.default;
}

function formatModalSize(bytes) {
    if (bytes < 1024)       return bytes + ' B';
    if (bytes < 1048576)    return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function previewModalFile(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const info = getModalFileIcon(file.type, file.name);

    document.getElementById('modal-preview-icon').textContent = info.icon;
    document.getElementById('modal-preview-name').textContent = file.name;
    document.getElementById('modal-preview-size').textContent = formatModalSize(file.size);
    document.getElementById('modal-upload-placeholder').style.display = 'none';
    document.getElementById('modal-file-preview').classList.add('visible');
}

function removeModalFile() {
    const input = document.getElementById('modal-file-input');
    if (input) input.value = '';
    const placeholder = document.getElementById('modal-upload-placeholder');
    if (placeholder) placeholder.style.display = 'block';
    const preview = document.getElementById('modal-file-preview');
    if (preview) preview.classList.remove('visible');
}

/* ===== AJAX Submit inside modal with real progress bar ===== */
document.getElementById('modal-upload-lecture-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const cancelBtn = form.querySelector('button[type="button"]');
    
    let progressContainer = document.getElementById('modal-upload-progress-container');
    if (!progressContainer) {
        progressContainer = document.createElement('div');
        progressContainer.id = 'modal-upload-progress-container';
        progressContainer.style.marginTop = '1rem';
        progressContainer.style.background = 'var(--bg-primary)';
        progressContainer.style.borderRadius = '0.75rem';
        progressContainer.style.padding = '0.85rem';
        progressContainer.style.border = '1px solid var(--border-color)';
        progressContainer.innerHTML = `
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.4rem;">
                <span id="modal-upload-status-text">جاري رفع الملف...</span>
                <span id="modal-upload-percentage" style="color: var(--accent-color);">0%</span>
            </div>
            <div style="width: 100%; height: 6px; background: var(--bg-secondary); border-radius: 3px; overflow: hidden; border: 1px solid var(--border-color);">
                <div id="modal-upload-progress-bar" style="width: 0%; height: 100%; background: var(--accent-color); transition: width 0.1s;"></div>
            </div>
            <div id="modal-upload-size-info" style="font-size: 0.72rem; color: var(--text-secondary); margin-top: 0.4rem; text-align: left; font-weight: 600;">0 / 0 MB</div>
        `;
        form.appendChild(progressContainer);
    }
    
    progressContainer.style.display = 'block';
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.6';
    cancelBtn.disabled = true;
    cancelBtn.style.opacity = '0.6';
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(event) {
        if (event.lengthComputable) {
            const percentComplete = (event.loaded / event.total) * 100;
            document.getElementById('modal-upload-progress-bar').style.width = percentComplete.toFixed(0) + '%';
            document.getElementById('modal-upload-percentage').textContent = percentComplete.toFixed(0) + '%';
            
            const loadedMB = (event.loaded / 1048576).toFixed(1);
            const totalMB = (event.total / 1048576).toFixed(1);
            document.getElementById('modal-upload-size-info').textContent = `${loadedMB} MB / ${totalMB} MB`;
            
            if (percentComplete === 100) {
                document.getElementById('modal-upload-status-text').textContent = 'جاري المعالجة وحفظ الملف على السيرفر...';
            }
        }
    });
    
    xhr.addEventListener('load', function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            window.location.reload();
        } else {
            let errorMsg = 'حدث خطأ أثناء الرفع.';
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.errors && response.errors.attachment) {
                    errorMsg = response.errors.attachment[0];
                } else if (response.errors && response.errors.title) {
                    errorMsg = response.errors.title[0];
                } else if (response.message) {
                    errorMsg = response.message;
                }
            } catch(e) {}
            alert(errorMsg);
            resetUploadState();
        }
    });
    
    xhr.addEventListener('error', function() {
        alert('انقطع الاتصال أو حدث خطأ بالشبكة.');
        resetUploadState();
    });
    
    function resetUploadState() {
        progressContainer.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        cancelBtn.disabled = false;
        cancelBtn.style.opacity = '1';
    }
    
    xhr.open('POST', form.action, true);
    
    const csrfToken = form.querySelector('input[name="_token"]').value;
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.send(formData);
});
</script>
@endpush


