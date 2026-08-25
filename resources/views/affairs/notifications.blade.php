@extends('layouts.affairs')
@section('title', 'الإشعارات')

@push('styles')
<style>
    .notifications-container {
        max-width: 900px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    /* Header & Actions */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .page-header h2 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    .page-header h2 i {
        color: var(--accent-color);
        font-size: 1.5rem;
    }
    .mark-read-btn {
        background: var(--bg-primary);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        padding: 0.8rem 1.5rem;
        border-radius: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    .mark-read-btn:hover {
        background: var(--accent-color);
        color: var(--primary-dark);
        border-color: transparent;
    }

    /* Filters */
    .filters {
        display: flex;
        gap: 0.8rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
    }
    .filter-btn {
        background: none;
        color: var(--text-secondary);
        border: none;
        padding: 0.5rem 1rem;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        position: relative;
        transition: color 0.2s;
    }
    .filter-btn:hover { color: var(--text-primary); }
    .filter-btn.active { color: var(--text-primary); }
    .filter-btn.active::after {
        content: '';
        position: absolute;
        bottom: -1rem;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--accent-color);
        border-radius: 3px 3px 0 0;
    }
    .badge-count {
        background: var(--accent-color);
        color: var(--primary-dark);
        padding: 0.1rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.8rem;
        margin-right: 0.5rem;
    }

    /* Notification List */
    .notifications-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .notif-card {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 1.5rem;
        box-shadow: var(--shadow);
        transition: transform 0.2s, background 0.3s;
        border: 1px solid transparent;
        position: relative;
    }
    .notif-card:hover { transform: translateX(-5px); }
    
    .notif-card.unread {
        background: linear-gradient(to left, var(--bg-secondary), rgba(252, 227, 0, 0.05));
        border-right: 4px solid var(--accent-color);
    }

    .notif-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    /* Icon Colors based on type */
    .icon-leave { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .icon-msg { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
    .icon-alert { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
    .icon-system { background: rgba(252, 227, 0, 0.2); color: #d97706; }

    .notif-content { flex: 1; }
    .notif-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }
    .notif-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0;
    }
    .notif-time {
        font-size: 0.85rem;
        color: var(--text-secondary);
        white-space: nowrap;
    }
    .notif-body {
        font-size: 0.95rem;
        color: var(--text-secondary);
        line-height: 1.5;
        margin: 0 0 1rem 0;
    }

    .notif-actions {
        display: flex;
        gap: 0.8rem;
    }
    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: background 0.2s;
    }
    .action-primary { background: var(--accent-color); color: var(--primary-dark); }
    .action-primary:hover { opacity: 0.9; }
    .action-secondary { background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); }
    .action-secondary:hover { background: var(--hover-color); }

    .unread-dot {
        width: 10px;
        height: 10px;
        background: var(--accent-color);
        border-radius: 50%;
        box-shadow: 0 0 5px var(--accent-color);
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        display: none;
    }
    .notif-card.unread .unread-dot { display: block; }
    
    @media (max-width: 600px) {
        .notif-card { flex-direction: column; gap: 1rem; }
        .unread-dot { top: 1rem; right: 1rem; }
    }
</style>
@endpush

@section('content')
<div class="notifications-container">
    
    <!-- Header -->
    <div class="page-header">
        <h2><i class="fa-solid fa-bell"></i> مركز الإشعارات</h2>
        <form method="POST" action="{{ route('affairs.notifications.read_all') }}">
            @csrf
            <button type="submit" class="mark-read-btn">
                <i class="fa-solid fa-check-double"></i> تحديد الكل كمقروء
            </button>
        </form>
    </div>

    <!-- Filters -->
    <div class="filters">
        <button class="filter-btn active" data-filter="all">كل الإشعارات</button>
        <button class="filter-btn" data-filter="unread">غير مقروءة
            @if($unreadCount > 0)
                <span class="badge-count" id="unreadCount">{{ $unreadCount }}</span>
            @endif
    </div>

    <!-- Notifications List -->
    <div class="notifications-list" id="notifList">

        @forelse($notifications as $notif)
            @php
                $titleLower = mb_strtolower(($notif->title ?? '') . ' ' . ($notif->message ?? ''));
                $type = $notif->type ?? '';

                $targetUrl = null;
                $serviceTab = 'all';
                if (str_contains($titleLower, 'وثيقة') || str_contains($titleLower, 'كشف') || str_contains($titleLower, 'علامات')) {
                    $serviceTab = 'documents';
                } elseif (str_contains($titleLower, 'إكمال') || str_contains($titleLower, 'امتحان')) {
                    $serviceTab = 'makeup';
                } elseif (str_contains($titleLower, 'استرحام')) {
                    $serviceTab = 'mercy';
                } elseif (str_contains($titleLower, 'قفل') || str_contains($titleLower, 'جهاز')) {
                    $serviceTab = 'device-reset';
                } elseif (str_contains($titleLower, 'بصمة') || str_contains($titleLower, 'وجه')) {
                    $serviceTab = 'face-photo';
                }

                if (in_array($type, ['student_service', 'service', 'service_request']) || str_contains($titleLower, 'خدمة') || str_contains($titleLower, 'خدمات') || str_contains($titleLower, 'استرحام') || str_contains($titleLower, 'وثيقة') || str_contains($titleLower, 'كشف') || str_contains($titleLower, 'إكمال') || str_contains($titleLower, 'قفل')) {
                    $targetUrl = route('affairs.student_services') . '?tab=' . $serviceTab;
                } elseif (in_array($type, ['photo_request', 'photo_change_request']) || str_contains($titleLower, 'صورة') || str_contains($titleLower, 'وجه')) {
                    $targetUrl = route('affairs.photo_requests');
                } elseif (in_array($type, ['leave', 'leave_request']) || str_contains($titleLower, 'إجازة') || str_contains($titleLower, 'مبرر') || str_contains($titleLower, 'غياب')) {
                    $targetUrl = route('affairs.leaves');
                } elseif (in_array($type, ['pending_account', 'account', 'registration']) || str_contains($titleLower, 'تسجيل') || str_contains($titleLower, 'تفعيل') || str_contains($titleLower, 'حساب')) {
                    $targetUrl = route('affairs.pending_accounts');
                } elseif (in_array($type, ['message', 'chat']) || str_contains($titleLower, 'رسالة')) {
                    $targetUrl = route('affairs.messages');
                }

                $iconClass = match($type) {
                    'leave', 'leave_request' => 'icon-leave',
                    'message', 'chat'        => 'icon-msg',
                    'photo_request'          => 'icon-system',
                    'alert'                  => 'icon-alert',
                    default                  => 'icon-system',
                };
                $iconName = match($type) {
                    'leave', 'leave_request' => 'fa-plane-departure',
                    'message', 'chat'        => 'fa-envelope',
                    'photo_request'          => 'fa-camera',
                    'alert'                  => 'fa-triangle-exclamation',
                    default                  => 'fa-bell',
                };
            @endphp
            <div class="notif-card {{ !$notif->is_read ? 'unread' : '' }}" 
                 data-status="{{ !$notif->is_read ? 'unread' : 'read' }}"
                 style="cursor:pointer;"
                 onclick="handleNotifClick(event, '{{ $targetUrl }}', {{ $notif->id }}, {{ !$notif->is_read ? 'true' : 'false' }})">
                <div class="unread-dot"></div>
                @php
                    $senderAvatar = $notif->sender?->avatar ? asset('storage/' . $notif->sender->avatar) : null;
                @endphp
                @if($senderAvatar)
                    <img src="{{ $senderAvatar }}" alt="صورة الطالب" style="width:50px; height:50px; border-radius:50%; object-fit:cover; border:2px solid #ffcc00; flex-shrink:0; box-shadow:0 2px 8px rgba(0,0,0,0.15);">
                @else
                    <div class="notif-icon {{ $iconClass }}"><i class="fa-solid {{ $iconName }}"></i></div>
                @endif
                <div class="notif-content">
                    <div class="notif-header">
                        <h3 class="notif-title">{{ $notif->title }}</h3>
                        <span class="notif-time">{{ $notif->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="notif-body">{{ $notif->message }}</p>
                    <div class="notif-actions" onclick="event.stopPropagation()">
                        @if($targetUrl)
                            <a href="{{ $targetUrl }}" class="action-btn action-primary" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem;">
                                الانتقال للطلب <i class="fa-solid fa-arrow-left"></i>
                            </a>
                        @endif
                        @if(!$notif->is_read)
                            <form method="POST" action="{{ route('affairs.notifications.read', $notif->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="action-btn action-secondary">تحديد كمقروء</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                <i class="fa-regular fa-bell-slash" style="font-size: 3rem; opacity: 0.4; margin-bottom: 1rem;"></i>
                <p style="font-size: 1.1rem;">لا توجد إشعارات حتى الآن.</p>
            </div>
        @endforelse

    </div>

</div>
@endsection

@push('scripts')
<script>
    const filterBtns = document.querySelectorAll('.filter-btn');
    const notifCards = document.querySelectorAll('.notif-card');
    const unreadCountBadge = document.getElementById('unreadCount');

    // Filter Logic
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Active states
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.getAttribute('data-filter');

            notifCards.forEach(card => {
                const status = card.getAttribute('data-status');
                if (filter === 'all' || filter === status) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Mark single as read
    function markAsRead(btnElement) {
        const card = btnElement.closest('.notif-card');
        card.classList.remove('unread');
        card.setAttribute('data-status', 'read');
        // Hide the mark as read button
        btnElement.style.display = 'none';
        updateUnreadCount();
        
        // If current filter is 'unread', hide the card after a short delay
        const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-filter');
        if(activeFilter === 'unread') {
            setTimeout(() => {
                card.style.display = 'none';
            }, 300);
        }
    }

    // Mark all as read
    function markAllAsRead() {
        notifCards.forEach(card => {
            if (card.classList.contains('unread')) {
                card.classList.remove('unread');
                card.setAttribute('data-status', 'read');
                const btn = card.querySelector('.action-secondary');
                if(btn) btn.style.display = 'none';
            }
        });
        updateUnreadCount();

        const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-filter');
        if(activeFilter === 'unread') {
            notifCards.forEach(c => c.style.display = 'none');
        }
    }

    // Handle click on notification card
    function handleNotifClick(event, targetUrl, notifId, isUnread) {
        const card = event.currentTarget;

        if (isUnread) {
            // Optimistically update UI
            card.classList.remove('unread');
            card.setAttribute('data-status', 'read');
            const markBtn = card.querySelector('form button.action-secondary');
            if (markBtn) markBtn.style.display = 'none';

            updateUnreadCount();

            // Send async POST request to mark read
            fetch(`/affairs/notifications/${notifId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            }).finally(() => {
                if (targetUrl && targetUrl !== '#' && targetUrl !== 'null' && targetUrl !== window.location.href) {
                    window.location.href = targetUrl;
                }
            });
        } else {
            if (targetUrl && targetUrl !== '#' && targetUrl !== 'null' && targetUrl !== window.location.href) {
                window.location.href = targetUrl;
            }
        }
    }

    // Update Counter
    function updateUnreadCount() {
        const unreadCount = document.querySelectorAll('.notif-card.unread').length;
        if (unreadCountBadge) {
            unreadCountBadge.innerText = unreadCount;
            if (unreadCount === 0) {
                unreadCountBadge.style.display = 'none';
            }
        }
    }
</script>
@endpush
