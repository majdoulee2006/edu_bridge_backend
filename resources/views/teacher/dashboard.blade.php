@extends('layouts.teacher')
@section('title', 'Ø§Ù„Ø±Ø¦ÙŠØ³ÙŠØ©')
@section('subtitle', 'Ù…Ø±Ø­Ø¨Ø§Ù‹ØŒ ' . (auth()->user()->full_name ?? 'Ø£Ø³ØªØ§Ø°'))

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

{{-- ===== Stats (3 cards) ===== --}}
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 2rem;">

    {{-- Card 1: Ø§Ù„Ù…ÙˆØ§Ø¯ Ø§Ù„Ø¯Ø±Ø§Ø³ÙŠØ© - clickable --}}
    <div class="stat-card" onclick="openModal('courses-modal')" title="Ø§Ø¶ØºØ· Ù„Ø¹Ø±Ø¶ Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ù…ÙˆØ§Ø¯">
        <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
        <div>
            <div class="stat-value">{{ $courses->count() }}</div>
            <div class="stat-label">Ø§Ù„Ù…ÙˆØ§Ø¯ Ø§Ù„Ø¯Ø±Ø§Ø³ÙŠØ©</div>
            <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> Ø§Ø¶ØºØ· Ù„Ù„Ø¹Ø±Ø¶</div>
        </div>
    </div>

    {{-- Card 2: Ø§Ù„ÙˆØ§Ø¬Ø¨Ø§Øª - clickable --}}
    <div class="stat-card" onclick="openModal('assignments-modal')" title="Ø§Ø¶ØºØ· Ù„Ø¹Ø±Ø¶ Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„ÙˆØ§Ø¬Ø¨Ø§Øª">
        <div class="stat-icon"><i class="fa-solid fa-file-pen"></i></div>
        <div>
            <div class="stat-value">{{ $recentAssignments->count() }}</div>
            <div class="stat-label">Ø§Ù„ÙˆØ§Ø¬Ø¨Ø§Øª Ø§Ù„Ù†Ø´Ø·Ø©</div>
            <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> Ø§Ø¶ØºØ· Ù„Ù„Ø¹Ø±Ø¶</div>
        </div>
    </div>

    {{-- Card 3: Ø­ØµØµ Ø§Ù„ÙŠÙˆÙ… - plain --}}
    <div class="stat-card stat-card-plain">
        <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
        <div>
            <div class="stat-value">{{ $todayCount }}</div>
            <div class="stat-label">Ø­ØµØµ Ø§Ù„ÙŠÙˆÙ…</div>
        </div>
    </div>

</div>

{{-- ===== Announcements ===== --}}
<div style="margin-bottom: 2rem;">
    <p class="section-title">
        <i class="fa-solid fa-bullhorn" style="color: var(--accent-color);"></i>
        Ø¢Ø®Ø± Ø§Ù„Ø£Ø®Ø¨Ø§Ø± ÙˆØ§Ù„Ø¥Ø¹Ù„Ø§Ù†Ø§Øª
    </p>

    @forelse($announcements as $ann)
        <div class="announce-card">
            <div class="announce-image-area">
                @if($ann->image ?? false)
                    <img src="{{ asset('storage/' . $ann->image) }}" alt="ØµÙˆØ±Ø© Ø§Ù„Ø¥Ø¹Ù„Ø§Ù†">
                @else
                    <i class="fa-solid fa-bullhorn" style="font-size: 2.5rem;"></i>
                @endif
                <span class="announce-badge">{{ $ann->category ?? 'Ø¥Ø¹Ù„Ø§Ù† Ù‡Ø§Ù…' }}</span>
            </div>
            <div class="announce-body">
                <div class="announce-meta">
                    <span><i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}</span>
                    <span>Ù…ÙˆØ¬Ù‡ Ø¥Ù„Ù‰:
                        @if(($ann->target_audience ?? '') === 'all' || ($ann->type ?? '') === 'general')
                            Ø§Ù„Ø¬Ù…ÙŠØ¹
                        @else
                            {{ $ann->target_audience ?? 'Ø§Ù„Ù…Ø¹Ù„Ù…ÙˆÙ†' }}
                        @endif
                    </span>
                </div>
                <h4 class="announce-title">{{ $ann->title }}</h4>
                <p class="announce-excerpt">{{ Str::limit($ann->content, 150) }}</p>
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 2.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
            <i class="fa-solid fa-bullhorn" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; color: var(--accent-color); opacity: 0.5;"></i>
            Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¥Ø¹Ù„Ø§Ù†Ø§Øª Ø­Ø§Ù„ÙŠØ§Ù‹
        </div>
    @endforelse
</div>




{{-- ================================================================ --}}
{{--  MODAL: Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ù…ÙˆØ§Ø¯ Ø§Ù„Ø¯Ø±Ø§Ø³ÙŠØ©                                    --}}
{{-- ================================================================ --}}
<div id="courses-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-weight: 800; font-size: 1.1rem;">
                <i class="fa-solid fa-book-open" style="color: var(--accent-color);"></i>
                Ù…ÙˆØ§Ø¯ÙŠ Ø§Ù„Ø¯Ø±Ø§Ø³ÙŠØ©
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
                            &nbsp; Â· &nbsp; {{ $c->credits }} Ø³Ø§Ø¹Ø©
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <i class="fa-solid fa-book-open" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
                Ù„Ø§ ØªÙˆØ¬Ø¯ Ù…ÙˆØ§Ø¯ Ù…Ø³Ù†Ø¯Ø© Ø­Ø§Ù„ÙŠØ§Ù‹
            </div>
        @endforelse
    </div>
</div>


{{-- ================================================================ --}}
{{--  MODAL: Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„ÙˆØ§Ø¬Ø¨Ø§Øª                                           --}}
{{-- ================================================================ --}}
<div id="assignments-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-weight: 800; font-size: 1.1rem;">
                <i class="fa-solid fa-file-pen" style="color: var(--accent-color);"></i>
                Ø§Ù„ÙˆØ§Ø¬Ø¨Ø§Øª Ø§Ù„Ù†Ø´Ø·Ø©
            </h3>
            <button class="modal-close" onclick="closeModal('assignments-modal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        @forelse($recentAssignments as $a)
            @php
                $statusClass = 'badge-active';
                $statusText  = 'Ù†Ø´Ø·';
                if(($a->graded_count ?? 0) >= ($a->submissions_count ?? 1) && ($a->submissions_count ?? 0) > 0) {
                    $statusClass = 'badge-graded';
                    $statusText  = 'ØªÙ… Ø§Ù„ØªØµØ­ÙŠØ­';
                } elseif(($a->submissions_count ?? 0) > 0) {
                    $statusClass = 'badge-pending';
                    $statusText  = 'Ù‚ÙŠØ¯ Ø§Ù„ØªØµØ­ÙŠØ­';
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
                            <i class="fa-solid fa-users"></i> {{ $a->submissions_count }} ØªØ³Ù„ÙŠÙ…
                        @endif
                    </div>
                </div>
                <a href="{{ route('teacher.assignments.submissions', $a->assignment_id) }}"
                   style="color: var(--accent-color); font-size: 1rem;"
                   title="Ø¹Ø±Ø¶ Ø§Ù„Ø±Ø¯ÙˆØ¯"
                   onclick="closeModal('assignments-modal')">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            </div>
        @empty
            <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <i class="fa-solid fa-file-circle-plus" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
                Ù„Ø§ ØªÙˆØ¬Ø¯ ÙˆØ§Ø¬Ø¨Ø§Øª Ø­Ø§Ù„ÙŠØ§Ù‹
            </div>
        @endforelse

        <div style="margin-top: 1rem; text-align: center;">
            <a href="{{ route('teacher.assignments') }}"
               onclick="closeModal('assignments-modal')"
               style="color: var(--accent-color); font-weight: 700; font-size: 0.9rem; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Ø¹Ø±Ø¶ ÙƒÙ„ Ø§Ù„ÙˆØ§Ø¬Ø¨Ø§Øª
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

