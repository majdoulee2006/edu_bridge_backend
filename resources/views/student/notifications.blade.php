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
    .active-filter {
        background: var(--accent-color) !important;
        color: #1a1a1a !important;
        border-color: transparent !important;
    }
    /* Pagination Styles Fix for Dark Mode */
    .pagination-wrapper nav { width: 100%; }
    .pagination-wrapper .pagination { justify-content: center; margin-bottom: 0; }
    .pagination-wrapper .page-item .page-link { background: var(--bg-secondary); border-color: var(--border-color); color: var(--text-primary); }
    .pagination-wrapper .page-item.active .page-link { background: var(--accent-color); color: #000; border-color: var(--accent-color); }
    .pagination-wrapper .page-item.disabled .page-link { color: var(--text-secondary); background: var(--bg-primary); }
</style>
@endpush

@section('content')

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 0.6rem; margin: 0;">
                <i class="fa-solid fa-bell" style="color: var(--accent-color);"></i> الإشعارات
            </h2>
            <p style="color: var(--text-secondary); margin-top: 0.25rem; font-size: 0.9rem;">
                لديك <span style="font-weight: 800; color: var(--text-primary);">{{ $notifications->total() }}</span> إشعار 
                @if(isset($unreadCount) && $unreadCount > 0)
                    (<span style="color: var(--accent-color); font-weight: 800;">{{ $unreadCount }} غير مقروء</span>)
                @endif
            </p>
            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <a href="{{ route('student.notifications') }}" class="single-read-btn {{ !request('filter') ? 'active-filter' : '' }}" style="text-decoration: none;">الكل</a>
                <a href="{{ route('student.notifications', ['filter' => 'unread']) }}" class="single-read-btn {{ request('filter') == 'unread' ? 'active-filter' : '' }}" style="text-decoration: none;">غير المقروءة</a>
                <a href="{{ route('student.notifications', ['filter' => 'read']) }}" class="single-read-btn {{ request('filter') == 'read' ? 'active-filter' : '' }}" style="text-decoration: none;">المقروءة</a>
            </div>
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

            $titleText = mb_strtolower(($n->title ?? '') . ' ' . ($n->body ?? '') . ' ' . ($n->message ?? ''));
            $isExamRelated = str_contains($titleText, 'فحص') || str_contains($titleText, 'امتحان') || str_contains($titleText, 'اختبار') || $type === 'exam';
            $isServiceRelated = str_contains($titleText, 'خدمة') || str_contains($titleText, 'استرحام') || str_contains($titleText, 'وثيقة') || str_contains($titleText, 'إكمال') || str_contains($titleText, 'قفل') || $type === 'student_service';

            $iconMap = [
                'student_service'=> ['icon' => 'fa-hand-holding-hand', 'color' => '#ca8a04', 'bg' => '#fef9c3'],
                'assignment'    => ['icon' => 'fa-book-open',          'color' => '#ffe600', 'bg' => '#fffbe6'],
                'message'       => ['icon' => 'fa-envelope',           'color' => '#3b82f6', 'bg' => '#eff6ff'],
                'admin'         => ['icon' => 'fa-calendar',           'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
                'grade'         => $isExamRelated ? ['icon' => 'fa-pencil', 'color' => '#ef4444', 'bg' => '#fee2e2'] : ['icon' => 'fa-check', 'color' => '#10b981', 'bg' => '#ecfdf5'],
                'exam'          => ['icon' => 'fa-pencil',             'color' => '#ef4444', 'bg' => '#fee2e2'],
                'attendance'    => ['icon' => 'fa-clipboard-user',     'color' => '#f59e0b', 'bg' => '#fffbeb'],
                'leave_request' => ['icon' => 'fa-calendar-xmark',     'color' => '#ef4444', 'bg' => '#fef2f2'],
                'leave'         => ['icon' => 'fa-calendar-xmark',     'color' => '#ef4444', 'bg' => '#fef2f2'],
                'general'       => ['icon' => 'fa-bell',               'color' => '#f59e0b', 'bg' => '#fffbeb'],
            ];
            $style = $iconMap[$type] ?? ($isExamRelated ? ['icon' => 'fa-pencil', 'color' => '#ef4444', 'bg' => '#fee2e2'] : $iconMap['general']);

            $serviceTab = 'all';
            if (str_contains($titleText, 'وثيقة') || str_contains($titleText, 'كشف') || str_contains($titleText, 'علامات')) {
                $serviceTab = 'document';
            } elseif (str_contains($titleText, 'إكمال') || str_contains($titleText, 'امتحان')) {
                $serviceTab = 'makeup';
            } elseif (str_contains($titleText, 'استرحام')) {
                $serviceTab = 'mercy';
            } elseif (str_contains($titleText, 'قفل') || str_contains($titleText, 'جهاز')) {
                $serviceTab = 'device_reset';
            } elseif (str_contains($titleText, 'بصمة') || str_contains($titleText, 'وجه')) {
                $serviceTab = 'face_photo';
            }

            $studentServiceLink = '/student/student-services?tab=' . $serviceTab;

            $linkMap = [
                'student_service'=> $studentServiceLink,
                'assignment'    => '/student/assignments',
                'grade'         => $isExamRelated ? '/student/schedule#exams-section' : '/student/grades',
                'exam'          => '/student/schedule#exams-section',
                'attendance'    => '/student/attendance',
                'leave_request' => $isServiceRelated ? $studentServiceLink : '/student/leave-requests',
                'leave'         => $isServiceRelated ? $studentServiceLink : '/student/leave-requests',
                'message'       => '/student/messages',
                'admin'         => $isServiceRelated ? $studentServiceLink : '/student/dashboard',
                'general'       => $isServiceRelated ? $studentServiceLink : ($isExamRelated ? '/student/schedule#exams-section' : '/student/notifications'),
            ];
            $link = $linkMap[$type] ?? ($isServiceRelated ? $studentServiceLink : ($isExamRelated ? '/student/schedule#exams-section' : '/student/notifications'));
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
                        <span style="font-size: 0.78rem; color: var(--text-secondary); white-space: nowrap;" dir="rtl">
                            {{ \Carbon\Carbon::parse($n->created_at)->translatedFormat('d F Y - h:i A') }}
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
            <p style="font-size: 1.1rem; font-weight: 600;">لا توجد إشعارات لعرضها</p>
        </div>
    @endforelse

    @if($notifications->hasPages())
        <div class="pagination-wrapper" style="margin-top: 2rem; display: flex; justify-content: center;" dir="ltr">
            {{ $notifications->appends(request()->query())->links('pagination::bootstrap-4') }}
        </div>
    @endif
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
