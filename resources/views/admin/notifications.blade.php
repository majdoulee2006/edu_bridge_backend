@extends('layouts.admin')

@section('title', __('messages.notifications_center'))

@push('styles')
<style>
    .notif-filter-btn {
        background: transparent;
        color: #64748b;
        border: none;
        padding: 0.6rem 1.2rem;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
    }
    .notif-filter-btn:hover { color: #0f172a; }
    .dark .notif-filter-btn { color: #a1a1aa; }
    .dark .notif-filter-btn:hover { color: #ffffff; }
    .notif-filter-btn.active {
        color: #0f172a;
        font-weight: 800;
    }
    .dark .notif-filter-btn.active {
        color: #ffffff;
    }
    .notif-filter-btn.active::after {
        content: '';
        position: absolute;
        bottom: -0.85rem;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--primary, #f2f20d);
        border-radius: 3px 3px 0 0;
        box-shadow: 0 0 10px rgba(242, 242, 13, 0.5);
    }
    
    .notif-card {
        border-radius: 1.25rem;
        padding: 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 1.25rem;
        transition: all 0.2s ease;
        position: relative;
    }
    .notif-card:hover {
        transform: translateY(-2px);
    }
    .notif-card.unread {
        border-inline-start-width: 4px;
        border-inline-start-color: var(--primary, #f2f20d);
    }
</style>
@endpush

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-bell text-amber-500 dark:text-[#f2f20d]"></i>
                {{ __('messages.notifications_center') }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">{{ __('messages.notifications_center_desc') }}</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            {{-- زر إرسال إشعار --}}
            <button onclick="document.getElementById('sendNotifModal').classList.remove('hidden')"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#f2f20d] hover:bg-[#d9d90b] text-black shadow-glow hover:scale-105 active:scale-95 transition-all font-extrabold text-xs">
                <i class="fa-solid fa-paper-plane text-sm"></i>
                <span>{{ __('messages.send_new_notification') }}</span>
            </button>

            {{-- زر تحديد الكل كمقروء --}}
            @if($notifications->filter(fn($n) => !$n->is_read)->count() > 0)
                <form action="{{ route('admin.notifications.read_all') }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="flex items-center gap-2 px-4 py-2.5 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 border border-slate-200 dark:border-zinc-700 font-bold text-xs transition-all shadow-sm">
                        <i class="fa-solid fa-check-double text-amber-500 dark:text-[#f2f20d]"></i>
                        <span>{{ __('messages.mark_all_read') }}</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ===== Filters Bar ===== --}}
    @php
        $unreadCount = $notifications->filter(fn($n) => !$n->is_read)->count();
    @endphp
    <div class="flex items-center gap-4 mb-6 border-b border-slate-200 dark:border-zinc-800 pb-3">
        <a href="{{ route('admin.notifications') }}" class="notif-filter-btn {{ !request('filter') ? 'active' : '' }}">
            {{ __('messages.all_notifications') }}
            <span class="mx-1.5 px-2 py-0.5 rounded-full text-xs bg-slate-200/80 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold">{{ $notifications->total() }}</span>
        </a>
        <a href="{{ route('admin.notifications', ['filter' => 'unread']) }}" class="notif-filter-btn {{ request('filter') == 'unread' ? 'active' : '' }}">
            {{ __('messages.unread') }}
            @if($unreadCount > 0)
                <span class="mx-1.5 px-2 py-0.5 rounded-full text-xs bg-amber-400 dark:bg-[#f2f20d] text-black font-black" id="unreadBadge">{{ $unreadCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.notifications', ['filter' => 'read']) }}" class="notif-filter-btn {{ request('filter') == 'read' ? 'active' : '' }}">
            {{ __('messages.read') }}
        </a>
    </div>

    {{-- ===== Notifications List ===== --}}
    <div class="flex flex-col gap-4" id="notificationsContainer">
        @forelse($notifications as $notif)
            @php
                $titleLower = mb_strtolower(($notif->title ?? '') . ' ' . ($notif->message ?? ''));
                $type = strtolower($notif->type ?? '');

                // Smart navigation links
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

                if ($type === 'student_service' || str_contains($titleLower, 'خدمة') || str_contains($titleLower, 'استرحام') || str_contains($titleLower, 'وثيقة') || str_contains($titleLower, 'كشف') || str_contains($titleLower, 'إكمال')) {
                    $targetUrl = route('admin.student_services') . '?tab=' . $serviceTab;
                } elseif (str_contains($titleLower, 'موعد') || str_contains($titleLower, 'مقابلة') || str_contains($titleLower, 'لقاء')) {
                    $targetUrl = route('admin.appointments');
                } elseif (str_contains($titleLower, 'رسالة') || $type === 'message' || $type === 'chat') {
                    $targetUrl = route('admin.messages');
                } elseif (str_contains($titleLower, 'حساب') || str_contains($titleLower, 'تسجيل') || $type === 'account') {
                    $targetUrl = route('admin.accounts');
                } elseif (str_contains($titleLower, 'تقرير') || str_contains($titleLower, 'تقارير')) {
                    $targetUrl = route('admin.reports');
                }

                // Dynamic icon styling
                $iconData = match(true) {
                    str_contains($titleLower, 'موعد') || str_contains($titleLower, 'مقابلة') => ['icon' => 'fa-calendar-check', 'bg' => 'bg-amber-500/15', 'color' => 'text-amber-500 dark:text-amber-400'],
                    str_contains($titleLower, 'رسالة') || $type === 'message' => ['icon' => 'fa-comments', 'bg' => 'bg-blue-500/15', 'color' => 'text-blue-600 dark:text-blue-400'],
                    str_contains($titleLower, 'حساب') || $type === 'account' => ['icon' => 'fa-user-plus', 'bg' => 'bg-emerald-500/15', 'color' => 'text-emerald-600 dark:text-emerald-400'],
                    str_contains($titleLower, 'إجازة') || $type === 'leave' => ['icon' => 'fa-user-clock', 'bg' => 'bg-rose-500/15', 'color' => 'text-rose-600 dark:text-rose-400'],
                    default => ['icon' => 'fa-bullhorn', 'bg' => 'bg-amber-400/20 dark:bg-yellow-500/15', 'color' => 'text-amber-600 dark:text-[#f2f20d]'],
                };
            @endphp

            <div class="notif-card bg-white dark:bg-[#121212] border border-slate-200 dark:border-[#262626] shadow-sm hover:shadow-md hover:border-slate-300 dark:hover:border-zinc-700 {{ !$notif->is_read ? 'unread bg-amber-50/40 dark:bg-[#151515]' : '' }}" 
                 data-unread="{{ !$notif->is_read ? 'true' : 'false' }}"
                 onclick="markAsRead({{ $notif->id }}, this)">
                
                {{-- Icon Badge --}}
                <div class="w-12 h-12 rounded-2xl {{ $iconData['bg'] }} {{ $iconData['color'] }} flex items-center justify-center shrink-0 text-xl font-bold shadow-sm">
                    <i class="fa-solid {{ $iconData['icon'] }}"></i>
                </div>

                {{-- Content Body --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3 mb-1">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white leading-snug truncate">
                            {{ $notif->title }}
                        </h3>
                        <span class="text-xs font-semibold text-slate-400 dark:text-zinc-500 shrink-0">
                            {{ \Carbon\Carbon::parse($notif->created_at)->translatedFormat('d F Y - h:i A') }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-zinc-400 leading-relaxed mb-3">
                        {{ $notif->message }}
                    </p>

                    @if($targetUrl)
                        <div class="flex items-center gap-2">
                            <a href="{{ $targetUrl }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-[#f2f20d] text-slate-700 hover:text-black dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-[#f2f20d] dark:hover:text-black font-bold text-xs transition-all shadow-sm">
                                <span>{{ __('messages.view_details') }}</span>
                                <i class="fa-solid {{ app()->getLocale() === 'en' ? 'fa-arrow-right' : 'fa-arrow-left' }} text-[10px]"></i>
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Unread glowing indicator --}}
                @if(!$notif->is_read)
                    <div class="unread-dot w-3 h-3 rounded-full bg-amber-400 dark:bg-[#f2f20d] shadow-glow shrink-0 mt-1"></div>
                @endif
            </div>
        @empty
            <div class="text-center py-16 px-4 bg-white dark:bg-[#121212] border border-slate-200 dark:border-zinc-800 rounded-3xl shadow-sm">
                <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-zinc-800/80 text-amber-500 dark:text-[#f2f20d] flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fa-solid fa-bell-slash"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">{{ __('messages.no_notifications_currently') }}</h3>
                <p class="text-xs text-slate-500 dark:text-zinc-400 max-w-sm mx-auto">{{ __('messages.no_notifications_desc') }}</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-8 flex justify-center w-full" dir="ltr">
            {{ $notifications->appends(request()->query())->links('pagination::tailwind') }}
        </div>
    @endif

    {{-- Modal إرسال إشعار جديد --}}
    <div id="sendNotifModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg bg-white dark:bg-[#141417] border border-slate-200 dark:border-zinc-800 rounded-3xl shadow-2xl p-6 text-right">
            <div class="flex items-center justify-between mb-5 border-b border-slate-200 dark:border-zinc-800 pb-4">
                <h3 class="text-lg font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane text-amber-500 dark:text-[#f2f20d]"></i>
                    {{ __('messages.send_admin_notification') }}
                </h3>
                <button onclick="document.getElementById('sendNotifModal').classList.add('hidden')"
                        class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="sendAdminNotifForm" action="{{ route('admin.notifications.send') }}" method="POST" class="flex flex-col gap-4">
                @csrf

                {{-- الجمهور / الفئة --}}
                <div>
                    <label class="text-xs font-bold text-slate-700 dark:text-zinc-300 block mb-2">{{ __('messages.target_audience') }}</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="cursor-pointer">
                            <input checked class="peer sr-only" name="recipient_type" value="all" type="radio"
                                   onchange="document.getElementById('deptSelectorModal').classList.add('hidden')"/>
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-1.5 p-2.5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 peer-checked:border-[#f2f20d] peer-checked:bg-amber-400/10 dark:peer-checked:bg-[#f2f20d]/10 transition-all text-center">
                                <i class="fa-solid fa-users text-slate-400 dark:text-zinc-400 peer-checked:text-amber-500 dark:peer-checked:text-[#f2f20d] text-sm"></i>
                                <p class="text-[11px] font-bold text-slate-800 dark:text-white leading-tight">{{ __('messages.all_users') }}</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input class="peer sr-only" name="recipient_type" value="departments" type="radio"
                                   onchange="document.getElementById('deptSelectorModal').classList.remove('hidden')"/>
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-1.5 p-2.5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 peer-checked:border-[#f2f20d] peer-checked:bg-amber-400/10 dark:peer-checked:bg-[#f2f20d]/10 transition-all text-center">
                                <i class="fa-solid fa-building-columns text-slate-400 dark:text-zinc-400 peer-checked:text-amber-500 dark:peer-checked:text-[#f2f20d] text-sm"></i>
                                <p class="text-[11px] font-bold text-slate-800 dark:text-white leading-tight">{{ __('messages.specific_department') }}</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input class="peer sr-only" name="recipient_type" value="heads" type="radio"
                                   onchange="document.getElementById('deptSelectorModal').classList.add('hidden')"/>
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-1.5 p-2.5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 peer-checked:border-[#f2f20d] peer-checked:bg-amber-400/10 dark:peer-checked:bg-[#f2f20d]/10 transition-all text-center">
                                <i class="fa-solid fa-user-shield text-slate-400 dark:text-zinc-400 peer-checked:text-amber-500 dark:peer-checked:text-[#f2f20d] text-sm"></i>
                                <p class="text-[11px] font-bold text-slate-800 dark:text-white leading-tight">{{ __('messages.department_heads_only') }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- اختيار القسم --}}
                <div id="deptSelectorModal" class="hidden">
                    <label class="text-xs font-bold text-slate-700 dark:text-zinc-300 block mb-2">{{ __('messages.selected_departments') }}</label>
                    <div class="flex flex-col gap-2 max-h-36 overflow-y-auto pr-1">
                        @foreach(\App\Models\Department::orderBy('name')->get() as $d)
                        <label class="cursor-pointer flex items-center gap-3 p-2.5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 hover:border-amber-400/50 transition-all">
                            <input type="checkbox" name="target_departments[]" value="{{ $d->department_id }}"
                                   class="w-4 h-4 accent-amber-400 cursor-pointer flex-shrink-0">
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ $d->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- الموضوع --}}
                <div>
                    <label class="text-xs font-bold text-slate-700 dark:text-zinc-300 block mb-1">{{ __('messages.notification_title') }}</label>
                    <input name="subject" type="text" required placeholder="{{ __('messages.enter_notif_title') }}"
                           class="w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 py-2.5 px-4 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:border-[#f2f20d] focus:ring-1 focus:ring-[#f2f20d] outline-none transition-all"/>
                </div>

                {{-- الرسالة --}}
                <div>
                    <label class="text-xs font-bold text-slate-700 dark:text-zinc-300 block mb-1">{{ __('messages.notification_content') }}</label>
                    <textarea name="message" rows="3" required placeholder="{{ __('messages.enter_notif_content') }}"
                              class="w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 py-2.5 px-4 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:border-[#f2f20d] focus:ring-1 focus:ring-[#f2f20d] outline-none resize-none transition-all"></textarea>
                </div>

                <button type="submit" id="sendAdminNotifBtn"
                        class="w-full py-3 rounded-xl bg-[#f2f20d] text-black font-extrabold text-xs hover:bg-[#d9d90b] transition-all active:scale-[0.98] shadow-glow flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane {{ app()->getLocale() === 'en' ? 'mr-1' : 'ml-1' }}"></i>
                    <span>{{ __('messages.confirm_and_send_notif') }}</span>
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Tab Filter Logic (All vs Unread)
    function filterNotifs(filter, btn) {
        document.querySelectorAll('.notif-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const cards = document.querySelectorAll('.notif-card');
        cards.forEach(card => {
            if (filter === 'unread') {
                if (card.getAttribute('data-unread') === 'true') {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            } else {
                card.style.display = 'flex';
            }
        });
    }

    // AJAX Mark as Read
    function markAsRead(id, element) {
        const dot = element.querySelector('.unread-dot');
        if (!dot) return;

        fetch(`/admin/notifications/${id}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                dot.remove();
                element.classList.remove('unread');
                element.setAttribute('data-unread', 'false');
                
                const badge = document.getElementById('unreadBadge');
                if (badge) {
                    let count = parseInt(badge.textContent) - 1;
                    if (count <= 0) badge.remove();
                    else badge.textContent = count;
                }
            }
        })
        .catch(err => console.error(err));
    }

    // Close modal on click outside
    document.getElementById('sendNotifModal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });

    // Anti-double click protection for send notification form
    (function() {
        const form = document.getElementById('sendAdminNotifForm');
        const btn = document.getElementById('sendAdminNotifBtn');
        let isSubmitted = false;

        if (form && btn) {
            form.addEventListener('submit', function(e) {
                if (isSubmitted) {
                    e.preventDefault();
                    return false;
                }
                isSubmitted = true;
                btn.disabled = true;
                btn.classList.add('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-sm"></i> <span>${@json(__('messages.sending'))}</span>`;
            });
        }

        window.addEventListener('pageshow', function() {
            isSubmitted = false;
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                btn.innerHTML = `<i class="fa-solid fa-paper-plane ml-1"></i> <span>${@json(__('messages.confirm_and_send_notif'))}</span>`;
            }
        });
    })();
</script>
@endpush
