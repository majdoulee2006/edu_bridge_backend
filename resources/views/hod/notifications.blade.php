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
</style>
@endpush

@section('content')

    @if(session('success'))
        <div style="background:#f0fdf4;color:#16a34a;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <p style="color:var(--text-secondary);">{{ isset($notifications) ? $notifications->count() : 0 }} إشعار</p>
        <button class="add-circle-btn" onclick="openModal('send-notif-modal')" title="إرسال إشعار جديد">
            <i class="fa-solid fa-plus"></i>
        </button>
    </div>

    @forelse($notifications ?? [] as $n)
        @php
            $isRead = $n->is_read ?? false;
            $type   = $n->type ?? 'general';
            $iconMap = [
                'assignment'     => ['icon' => 'fa-book-open',      'color' => '#ca8a04', 'bg' => '#fefce8'],
                'announcement'   => ['icon' => 'fa-bullhorn',        'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
                'leave'          => ['icon' => 'fa-calendar-xmark',  'color' => '#ef4444', 'bg' => '#fef2f2'],
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
                'attendance'     => '/hod/dashboard',
                'grade'          => '/hod/dashboard',
                'message'        => '/hod/messages',
                'administrative' => '/hod/dashboard',
                'general'        => '/hod/notifications',
            ];
            $link = $linkMap[$type] ?? '/hod/notifications';
        @endphp

        <a href="{{ $link }}" class="notif-card {{ !$isRead ? 'unread' : '' }}">
            <div class="notif-icon" style="background: {{ $style['bg'] }}; color: {{ $style['color'] }};">
                <i class="fa-solid {{ $style['icon'] }}"></i>
            </div>
            <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                    <span style="font-weight: {{ $isRead ? '600' : '800' }}; font-size: 0.97rem; color: var(--text-primary);">
                        {{ $n->title }}
                    </span>
                    <span style="font-size: 0.78rem; color: var(--text-secondary); white-space: nowrap;">
                        {{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}
                    </span>
                </div>
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.4rem; line-height: 1.5;">
                    {{ $n->body ?? $n->message ?? '' }}
                </div>
            </div>
            @if(!$isRead)
                <div style="width: 9px; height: 9px; border-radius: 50%; background: var(--accent-color); flex-shrink: 0; margin-top: 4px;"></div>
            @endif
        </a>

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
            <form action="{{ route('hod.notifications.send') }}" method="POST">
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
                    <button type="submit" class="btn-send">
                        <i class="fa-solid fa-paper-plane"></i> إرسال الإشعار
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
</script>
@endpush
