@extends('layouts.hod')
@section('title', 'الإشعارات')

@push('styles')
<style>
    .add-circle-btn {
        width: 38px; height: 38px; border-radius: 50%;
        background: var(--accent-color); color: #1a1a1a;
        border: none; font-size: 1.3rem;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        transition: transform 0.2s; flex-shrink: 0;
    }
    .add-circle-btn:hover { transform: scale(1.1); }

    .modal-overlay {
        position: fixed; top:0; left:0; width:100%; height:100%;
        background:rgba(0,0,0,0.5); display:flex; align-items:center;
        justify-content:center; z-index:1000; opacity:0; pointer-events:none;
        transition:opacity 0.3s;
    }
    .modal-overlay.active { opacity:1; pointer-events:auto; }
    .modal-card {
        background:var(--bg-secondary); border-radius:1.5rem; padding:2rem;
        width:92%; max-width:500px; box-shadow:var(--shadow);
        transform:translateY(20px); transition:transform 0.3s;
    }
    .modal-overlay.active .modal-card { transform:translateY(0); }
    .form-label { display:block; margin-bottom:0.4rem; font-weight:700; font-size:0.88rem; color:var(--text-secondary); }
    .form-input {
        width:100%; padding:0.7rem 0.9rem; border-radius:0.75rem;
        border:1px solid var(--border-color); background:var(--bg-primary);
        color:var(--text-primary); font-family:inherit; font-size:0.95rem; box-sizing:border-box;
    }
    .form-input:focus { outline:none; border-color:var(--accent-color); }
    .form-group { margin-bottom:0.9rem; }

    .target-options { display:flex; flex-direction:column; gap:0.5rem; }
    .target-opt {
        display:flex; align-items:center; gap:0.75rem;
        padding:0.75rem 1rem; border-radius:0.75rem;
        border:1px solid var(--border-color); cursor:pointer;
        transition:all 0.2s; font-weight:600; font-size:0.92rem;
    }
    .target-opt input[type=radio] { accent-color: var(--accent-color); width:16px; height:16px; }
    .target-opt.selected { border-color:var(--accent-color); background: rgba(202,138,4,0.08); }

    .btn-send   { background:var(--accent-color); color:#1a1a1a; flex:1; padding:0.75rem; border-radius:0.75rem; border:none; font-weight:700; cursor:pointer; font-size:1rem; font-family:inherit; }
    .btn-cancel { background:transparent; border:1px solid var(--border-color); color:var(--text-primary); flex:1; padding:0.75rem; border-radius:0.75rem; font-weight:700; cursor:pointer; font-size:1rem; font-family:inherit; }

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
    }
    .notif-card.unread { border-right-color: var(--accent-color); }
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

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <p style="color:var(--text-secondary); margin: 0;">
                لديك <span style="font-weight: 800; color: var(--text-primary);">{{ isset($notifications) ? $notifications->count() : 0 }}</span> إشعار
                @if(isset($unreadCount) && $unreadCount > 0)
                    (<span style="color: var(--accent-color); font-weight: 800;">{{ $unreadCount }} غير مقروء</span>)
                @endif
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            @if(isset($notifications) && $notifications->filter(fn($n) => !$n->is_read)->count() > 0)
                <form method="POST" action="{{ route('hod.notifications.read_all') }}">
                    @csrf
                    <button type="submit" class="mark-read-btn">
                        <i class="fa-solid fa-check-double"></i> تمييز الكل كمقروءة
                    </button>
                </form>
            @endif
            <button class="add-circle-btn" onclick="openModal('send-notif-modal')" title="إرسال إشعار جديد">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>
    </div>

    @forelse($notifications ?? [] as $n)
        @php
            $isRead = $n->is_read ?? false;
            $type   = $n->type ?? 'general';
            $iconMap = [
                'assignment'     => ['icon' => 'fa-book-open',      'color' => '#ca8a04', 'bg' => '#fefce8'],
                'announcement'   => ['icon' => 'fa-bullhorn',        'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
                'leave'          => ['icon' => 'fa-calendar-xmark',  'color' => '#ef4444', 'bg' => '#fef2f2'],
                'leave_request'  => ['icon' => 'fa-calendar-check',  'color' => '#ef4444', 'bg' => '#fef2f2'],
                'attendance'     => ['icon' => 'fa-clipboard-user',  'color' => '#f59e0b', 'bg' => '#fffbeb'],
                'grade'          => ['icon' => 'fa-star',            'color' => '#10b981', 'bg' => '#ecfdf5'],
                'message'        => ['icon' => 'fa-envelope',        'color' => '#3b82f6', 'bg' => '#eff6ff'],
                'administrative' => ['icon' => 'fa-bell',            'color' => '#6366f1', 'bg' => '#eef2ff'],
                'general'        => ['icon' => 'fa-bell',            'color' => '#f59e0b', 'bg' => '#fffbeb'],
            ];
            $style = $iconMap[$type] ?? $iconMap['general'];

            $linkMap = [
                'assignment'     => '/hod/dashboard',
                'announcement'   => '/hod/dashboard',
                'leave'          => '/hod/leaves',
                'leave_request'  => '/hod/leaves',
                'attendance'     => '/hod/dashboard',
                'grade'          => '/hod/dashboard',
                'message'        => '/hod/messages',
                'administrative' => '/hod/dashboard',
                'general'        => '/hod/notifications',
            ];
            $link = $linkMap[$type] ?? '/hod/notifications';
        @endphp

        <div class="notif-card {{ !$isRead ? 'unread' : '' }}" onclick="handleNotifClick(event, '{{ $link }}', {{ $n->id }}, {{ !$isRead ? 'true' : 'false' }})">
            <div class="notif-icon" style="background: {{ $style['bg'] }}; color: {{ $style['color'] }};">
                <i class="fa-solid {{ $style['icon'] }}"></i>
            </div>
            <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                    <span style="font-weight: {{ $isRead ? '600' : '800' }}; font-size: 0.97rem; color: var(--text-primary);">
                        {{ $n->title }}
                    </span>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.78rem; color: var(--text-secondary); white-space: nowrap;">
                            {{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}
                        </span>
                        @if(!$isRead)
                            <form method="POST" action="{{ route('hod.notifications.read', $n->id) }}" style="display: inline;" onclick="event.stopPropagation();">
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
            <i class="fa-regular fa-bell-slash" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--accent-color); opacity: 0.5;"></i>
            <p style="font-size: 1.1rem; font-weight: 600;">لا توجد إشعارات حتى الآن</p>
        </div>
    @endforelse


    {{-- Modal: إرسال إشعار جديد --}}
    <div id="send-notif-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size:1.3rem;font-weight:800;margin-bottom:1.25rem;text-align:center;">
                <i class="fa-solid fa-bell" style="color:var(--accent-color);margin-left:0.4rem;"></i>
                إرسال إشعار جديد
            </h4>
            <form id="sendHodNotifForm" action="{{ route('hod.notifications.send') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label">عنوان الإشعار <span style="color:#ef4444">*</span></label>
                    <input type="text" name="title" required class="form-input" placeholder="مثال: تنبيه هام للطلاب">
                </div>

                <div class="form-group">
                    <label class="form-label">محتوى الإشعار <span style="color:#ef4444">*</span></label>
                    <textarea name="message" required rows="3" class="form-input" placeholder="اكتب نص الإشعار..."></textarea>
                </div>

                <input type="hidden" name="category" value="academic">

                <div class="form-group">
                    <label class="form-label">إرسال إلى</label>
                    <div class="target-options" id="target-opts">
                        <label class="target-opt selected" onclick="selectTarget(this)">
                            <input type="radio" name="target" value="students" checked> الطلاب فقط
                        </label>
                        <label class="target-opt" onclick="selectTarget(this)">
                            <input type="radio" name="target" value="students_teachers"> الطلاب والمعلمين
                        </label>
                        <label class="target-opt" onclick="selectTarget(this)">
                            <input type="radio" name="target" value="all"> الكل
                        </label>
                    </div>
                </div>

                <div style="display:flex;gap:0.75rem;margin-top:1rem;">
                    <button type="submit" id="sendHodNotifBtn" class="btn-send" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;">
                        <i class="fa-solid fa-paper-plane"></i> <span>إرسال الإشعار</span>
                    </button>
                    <button type="button" onclick="closeModal('send-notif-modal')" class="btn-cancel">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function openModal(id)  { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    function selectTarget(el) {
        document.querySelectorAll('.target-opt').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');
    }

    // Anti-double click protection for send notification form
    (function() {
        const form = document.getElementById('sendHodNotifForm');
        const btn = document.getElementById('sendHodNotifBtn');
        let isSubmitted = false;

        if (form && btn) {
            form.addEventListener('submit', function(e) {
                if (isSubmitted) {
                    e.preventDefault();
                    return false;
                }
                isSubmitted = true;
                btn.disabled = true;
                btn.style.opacity = '0.6';
                btn.style.pointerEvents = 'none';
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>جاري الإرسال...</span>';
            });
        }

        window.addEventListener('pageshow', function() {
            isSubmitted = false;
            if (btn) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> <span>إرسال الإشعار</span>';
            }
        });
    })();

    function handleNotifClick(event, link, notifId, isUnread) {
        const card = event.currentTarget;
        if (isUnread) {
            card.classList.remove('unread');
            const singleBtn = card.querySelector('.single-read-btn');
            if (singleBtn) singleBtn.style.display = 'none';
            const dot = card.querySelector('div[style*="border-radius: 50%"]');
            if (dot) dot.remove();

            fetch(`/hod/notifications/${notifId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            }).finally(() => {
                if (link && link !== '/hod/notifications' && link !== '#') {
                    window.location.href = link;
                }
            });
        } else {
            if (link && link !== '/hod/notifications' && link !== '#') {
                window.location.href = link;
            }
        }
    }
</script>
@endpush
