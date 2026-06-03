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
    .announce-image-area img { width: 100%; height: 160px; object-fit: cover; }
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
                        <img src="{{ $imgUrl }}" style="width: 100%; height: 100%; object-fit: cover;">
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
                        <img src="{{ $imgUrl }}" style="width: 100%; height: 100%; object-fit: cover;">
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
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-weight: 800; font-size: 1.1rem;">
                <i class="fa-solid fa-book-open" style="color: var(--accent-color);"></i>
                موادي الدراسية
            </h3>
            <button class="modal-close" onclick="closeModal('courses-modal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        @forelse($courses as $c)
            <div class="list-item">
                <div class="list-icon"><i class="fa-solid fa-chalkboard"></i></div>
                <div style="flex: 1;">
                    <div class="list-item-title">{{ $c->title }}</div>
                    <div class="list-item-sub">
                        @if($c->level)
                            <i class="fa-solid fa-layer-group"></i> {{ $c->level }}
                        @endif
                        @if($c->credits ?? false)
                            &nbsp; Â· &nbsp; {{ $c->credits }} ساعة
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <i class="fa-solid fa-book-open" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
                لا توجد مواد مسندة حالياً
            </div>
        @endforelse
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
        if (e.target === this) closeModal(this.id);
    });
});
</script>
@endpush


