@extends('layouts.student')
@section('title', 'الإشعارات')

@push('styles')
<style>
    .notif-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 0.75rem;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        border-right: 4px solid transparent;
        transition: all 0.2s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        position: relative;
    }
    .notif-card.unread { border-right-color: var(--accent-color); background: linear-gradient(to left, var(--bg-secondary), rgba(252, 227, 0, 0.05)); }
    .notif-card:hover { transform: translateX(-3px); box-shadow: 0 6px 24px rgba(0,0,0,0.1); }
    .notif-icon { width: 46px; height: 46px; border-radius: 1rem; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }

    .mark-read-btn {
        background: var(--bg-secondary);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        padding: 0.6rem 1.2rem;
        border-radius: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
        font-family: inherit;
        font-size: 0.9rem;
    }
    .mark-read-btn:hover {
        background: var(--accent-color);
        color: #1a1a1a;
        border-color: transparent;
    }
    .single-read-btn {
        background: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        padding: 0.3rem 0.7rem;
        border-radius: 0.5rem;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        font-family: inherit;
    }
    .single-read-btn:hover {
        background: var(--accent-color);
        color: #1a1a1a;
        border-color: transparent;
    }
</style>
@endpush

@section('content')
    @if(session('success'))
        <div style="background: rgba(16,185,129,0.1); border: 1px solid #10b981; border-radius: 0.75rem; padding: 0.8rem 1.2rem; margin-bottom: 1.25rem; color: #10b981; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 0.6rem; margin: 0;">
                <i class="fa-solid fa-bell" style="color: var(--accent-color);"></i> الإشعارات
            </h2>
            <p style="color: var(--text-secondary); margin-top: 0.25rem; font-size: 0.9rem;">
                لديك <span style="font-weight: 800; color: var(--text-primary);">{{ $notifications->count() }}</span> إشعار 
                @if(isset($unreadCount) && $unreadCount > 0)
                    (<span style="color: var(--accent-color); font-weight: 800;">{{ $unreadCount }} غير مقروء</span>)
                @endif
            </p>
        </div>
        
        @if($notifications->filter(fn($n) => !$n->is_read)->count() > 0)
            <form method="POST" action="{{ route('student.notifications.read_all') }}">
                @csrf
                <button type="submit" class="mark-read-btn">
                    <i class="fa-solid fa-check-double"></i> تمييز الكل كمقروءة
                </button>
            </form>
        @endif
    </div>

    @forelse($notifications as $n)
        @php
            $isRead = $n->is_read ?? false;
            $type   = $n->type ?? 'general';
            $iconMap = [
                'assignment' => ['icon' => 'fa-book-open',  'color' => '#ffe600', 'bg' => '#fffbe6'],
                'message'    => ['icon' => 'fa-envelope',   'color' => '#3b82f6', 'bg' => '#eff6ff'],
                'admin'      => ['icon' => 'fa-calendar',   'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
                'grade'      => ['icon' => 'fa-check',      'color' => '#10b981', 'bg' => '#ecfdf5'],
                'attendance' => ['icon' => 'fa-clipboard-user', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
                'general'    => ['icon' => 'fa-bell',       'color' => '#f59e0b', 'bg' => '#fffbeb'],
            ];
            $style = $iconMap[$type] ?? $iconMap['general'];

            $linkMap = [
                'assignment' => '/student/assignments',
                'grade'      => '/student/grades',
                'attendance' => '/student/attendance',
                'message'    => '/student/messages',
                'admin'      => '/student/dashboard',
                'general'    => '/student/notifications',
            ];
            $link = $linkMap[$type] ?? '/student/notifications';
        @endphp
        <div class="notif-card {{ !$isRead ? 'unread' : '' }}" onclick="handleNotifClick(event, '{{ $link }}', {{ $n->id }}, {{ !$isRead ? 'true' : 'false' }})">
            <div class="notif-icon" style="background: {{ $style['bg'] }}; color: {{ $style['color'] }};">
                <i class="fa-solid {{ $style['icon'] }}"></i>
            </div>
            <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                    <div>
                        @if(isset($n->category))
                            <span style="font-size: 0.78rem; font-weight: 700; color: {{ $style['color'] }};">{{ $n->category }}</span>
                            <br>
                        @endif
                        <span style="font-weight: {{ $isRead ? '600' : '800' }}; font-size: 0.97rem;">{{ $n->title }}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.78rem; color: var(--text-secondary); white-space: nowrap;">
                            {{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}
                        </span>
                        @if(!$isRead)
                            <form method="POST" action="{{ route('student.notifications.read', $n->id) }}" style="display: inline;" onclick="event.stopPropagation();">
                                @csrf
                                <button type="submit" class="single-read-btn" title="تمييز كمقروء">
                                    <i class="fa-solid fa-check"></i> مقروءة
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.4rem; line-height: 1.5;">
                    {{ $n->body ?? $n->message ?? '' }}
                </div>
            </div>
            @if(!$isRead)
                <div style="width: 9px; height: 9px; border-radius: 50%; background: var(--accent-color); flex-shrink: 0; margin-top: 4px;"></div>
            @endif
        </div>
    @empty
        <div style="text-align: center; padding: 4rem; background: var(--bg-secondary); border-radius: 1.5rem; color: var(--text-secondary);">
            <i class="fa-regular fa-bell-slash" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--accent-color);"></i>
            <p style="font-size: 1.1rem; font-weight: 600;">لا توجد إشعارات حتى الآن</p>
        </div>
    @endforelse
@endsection

@push('scripts')
<script>
    function handleNotifClick(event, link, notifId, isUnread) {
        const card = event.currentTarget;
        if (isUnread) {
            card.classList.remove('unread');
            const singleBtn = card.querySelector('.single-read-btn');
            if (singleBtn) singleBtn.style.display = 'none';
            const dot = card.querySelector('div[style*="border-radius: 50%"]');
            if (dot) dot.remove();

            fetch(`/student/notifications/${notifId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            }).finally(() => {
                if (link && link !== '/student/notifications' && link !== '#') {
                    window.location.href = link;
                }
            });
        } else {
            if (link && link !== '/student/notifications' && link !== '#') {
                window.location.href = link;
            }
        }
    }
</script>
@endpush
