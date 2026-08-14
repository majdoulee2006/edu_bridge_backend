@extends('layouts.admin')

@section('title', 'مركز الإشعارات')

@push('styles')
<style>
    .notif-filter-btn {
        background: transparent;
        color: #a1a1aa;
        border: none;
        padding: 0.6rem 1.2rem;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        position: relative;
        transition: color 0.2s;
    }
    .notif-filter-btn:hover { color: #ffffff; }
    .notif-filter-btn.active { color: #ffffff; }
    .notif-filter-btn.active::after {
        content: '';
        position: absolute;
        bottom: -0.6rem;
        left: 0;
        width: 100%;
        height: 3px;
        background: #f2f20d;
        border-radius: 3px 3px 0 0;
        box-shadow: 0 0 10px rgba(242, 242, 13, 0.5);
    }
    
    .notif-card {
        background-color: #121212;
        border: 1px solid #262626;
        border-radius: 1.25rem;
        padding: 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 1.25rem;
        transition: transform 0.2s, border-color 0.2s;
        position: relative;
    }
    .notif-card:hover {
        transform: translateX(-4px);
        border-color: #3f3f46;
    }
    .notif-card.unread {
        background: linear-gradient(to left, #121212, rgba(242, 242, 13, 0.04));
        border-right: 4px solid #f2f20d;
    }
</style>
@endpush

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold text-white flex items-center gap-2">
                <i class="fa-solid fa-bell text-[#f2f20d]"></i>
                مركز الإشعارات والتنبيهات
            </h2>
            <p class="text-sm text-zinc-400 mt-1">عرض ومتابعة كافة الطلبات والإشعارات والتنبيهات الإدارية الواردة</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            {{-- زر إرسال إشعار --}}
            <button onclick="document.getElementById('sendNotifModal').classList.remove('hidden')"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#f2f20d] hover:bg-[#d9d90b] text-black shadow-glow hover:scale-105 active:scale-95 transition-all font-extrabold text-xs">
                <i class="fa-solid fa-paper-plane text-sm"></i>
                <span>إرسال إشعار جديد</span>
            </button>

            {{-- زر تحديد الكل كمقروء --}}
            @if($notifications->filter(fn($n) => !$n->is_read)->count() > 0)
                <form action="{{ route('admin.notifications.read_all') }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="flex items-center gap-2 px-4 py-2.5 rounded-full bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-zinc-700 font-bold text-xs transition-all">
                        <i class="fa-solid fa-check-double text-[#f2f20d]"></i>
                        <span>تحديد الكل كمقروء</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ===== Filters Bar ===== --}}
    @php
        $unreadCount = $notifications->filter(fn($n) => !$n->is_read)->count();
    @endphp
    <div class="flex items-center gap-4 mb-6 border-b border-zinc-800 pb-3">
        <button class="notif-filter-btn active" data-filter="all" onclick="filterNotifs('all', this)">
            كل الإشعارات
            <span class="mr-1.5 px-2 py-0.5 rounded-full text-xs bg-zinc-800 text-zinc-300 font-bold">{{ $notifications->count() }}</span>
        </button>
        <button class="notif-filter-btn" data-filter="unread" onclick="filterNotifs('unread', this)">
            غير مقروءة
            @if($unreadCount > 0)
                <span class="mr-1.5 px-2 py-0.5 rounded-full text-xs bg-[#f2f20d] text-black font-black" id="unreadBadge">{{ $unreadCount }}</span>
            @endif
        </button>
    </div>

    {{-- ===== Notifications List ===== --}}
    <div class="flex flex-col gap-4" id="notificationsContainer">
        @forelse($notifications as $notif)
            @php
                $titleLower = mb_strtolower(($notif->title ?? '') . ' ' . ($notif->message ?? ''));
                $type = strtolower($notif->type ?? '');

                // Smart navigation links
                $targetUrl = null;
                if (str_contains($titleLower, 'موعد') || str_contains($titleLower, 'مقابلة') || str_contains($titleLower, 'لقاء')) {
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
                    str_contains($titleLower, 'موعد') || str_contains($titleLower, 'مقابلة') => ['icon' => 'fa-calendar-check', 'bg' => 'bg-amber-500/15', 'color' => 'text-amber-400'],
                    str_contains($titleLower, 'رسالة') || $type === 'message' => ['icon' => 'fa-comments', 'bg' => 'bg-blue-500/15', 'color' => 'text-blue-400'],
                    str_contains($titleLower, 'حساب') || $type === 'account' => ['icon' => 'fa-user-plus', 'bg' => 'bg-emerald-500/15', 'color' => 'text-emerald-400'],
                    str_contains($titleLower, 'إجازة') || $type === 'leave' => ['icon' => 'fa-user-clock', 'bg' => 'bg-rose-500/15', 'color' => 'text-rose-400'],
                    default => ['icon' => 'fa-bullhorn', 'bg' => 'bg-yellow-500/15', 'color' => 'text-[#f2f20d]'],
                };
            @endphp

            <div class="notif-card {{ !$notif->is_read ? 'unread' : '' }}" 
                 data-unread="{{ !$notif->is_read ? 'true' : 'false' }}"
                 onclick="markAsRead({{ $notif->id }}, this)">
                
                {{-- Icon Badge --}}
                <div class="w-12 h-12 rounded-2xl {{ $iconData['bg'] }} {{ $iconData['color'] }} flex items-center justify-center shrink-0 text-xl font-bold shadow-sm">
                    <i class="fa-solid {{ $iconData['icon'] }}"></i>
                </div>

                {{-- Content Body --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3 mb-1">
                        <h3 class="text-base font-bold text-white leading-snug truncate">
                            {{ $notif->title }}
                        </h3>
                        <span class="text-xs font-semibold text-zinc-500 shrink-0">
                            {{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}
                        </span>
                    </div>

                    <p class="text-xs text-zinc-400 leading-relaxed mb-3">
                        {{ $notif->message }}
                    </p>

                    @if($targetUrl)
                        <div class="flex items-center gap-2">
                            <a href="{{ $targetUrl }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-[#f2f20d] hover:text-black text-zinc-300 font-bold text-xs transition-all">
                                <span>عرض التفاصيل</span>
                                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Unread glowing indicator --}}
                @if(!$notif->is_read)
                    <div class="unread-dot w-3 h-3 rounded-full bg-[#f2f20d] shadow-glow shrink-0 mt-1"></div>
                @endif
            </div>
        @empty
            <div class="text-center py-16 px-4 bg-[#121212] border border-zinc-800 rounded-3xl">
                <div class="w-16 h-16 rounded-full bg-zinc-800/80 text-[#f2f20d] flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fa-solid fa-bell-slash"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-1">لا توجد إشعارات حالياً</h3>
                <p class="text-xs text-zinc-400 max-w-sm mx-auto">علبة التنبيهات والإشعارات فارغة تماماً. سريان العمل منتظم ولا توجد أي طلبات معلقة.</p>
            </div>
        @endforelse
    </div>

    {{-- Modal إرسال إشعار جديد --}}
    <div id="sendNotifModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg bg-[#121212] border border-zinc-800 rounded-3xl shadow-2xl p-6 text-right" dir="rtl">
            <div class="flex items-center justify-between mb-5 border-b border-zinc-800 pb-4">
                <h3 class="text-lg font-extrabold text-white flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane text-[#f2f20d]"></i>
                    إرسال إشعار إداري جديد
                </h3>
                <button onclick="document.getElementById('sendNotifModal').classList.add('hidden')"
                        class="w-8 h-8 rounded-full bg-zinc-800 text-zinc-400 hover:text-white flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('admin.notifications.send') }}" method="POST" class="flex flex-col gap-4">
                @csrf

                {{-- الجمهور / الفئة --}}
                <div>
                    <label class="text-xs font-bold text-zinc-300 block mb-2">جهة الإرسال (الفئة)</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="cursor-pointer">
                            <input checked class="peer sr-only" name="recipient_type" value="all" type="radio"
                                   onchange="document.getElementById('deptSelectorModal').classList.add('hidden')"/>
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-1.5 p-2.5 rounded-xl border border-zinc-800 bg-zinc-900 peer-checked:border-[#f2f20d] peer-checked:bg-[#f2f20d]/10 transition-all text-center">
                                <i class="fa-solid fa-users text-zinc-400 peer-checked:text-[#f2f20d] text-sm"></i>
                                <p class="text-[11px] font-bold text-white leading-tight">كافة المستخدمين</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input class="peer sr-only" name="recipient_type" value="departments" type="radio"
                                   onchange="document.getElementById('deptSelectorModal').classList.remove('hidden')"/>
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-1.5 p-2.5 rounded-xl border border-zinc-800 bg-zinc-900 peer-checked:border-[#f2f20d] peer-checked:bg-[#f2f20d]/10 transition-all text-center">
                                <i class="fa-solid fa-building-columns text-zinc-400 peer-checked:text-[#f2f20d] text-sm"></i>
                                <p class="text-[11px] font-bold text-white leading-tight">قسم معين</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input class="peer sr-only" name="recipient_type" value="heads" type="radio"
                                   onchange="document.getElementById('deptSelectorModal').classList.add('hidden')"/>
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-1.5 p-2.5 rounded-xl border border-zinc-800 bg-zinc-900 peer-checked:border-[#f2f20d] peer-checked:bg-[#f2f20d]/10 transition-all text-center">
                                <i class="fa-solid fa-user-shield text-zinc-400 peer-checked:text-[#f2f20d] text-sm"></i>
                                <p class="text-[11px] font-bold text-white leading-tight">رؤساء الأقسام بس</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- اختيار القسم --}}
                <div id="deptSelectorModal" class="hidden">
                    <label class="text-xs font-bold text-zinc-300 block mb-2">الأقسام المحددة</label>
                    <div class="flex flex-col gap-2 max-h-36 overflow-y-auto pr-1">
                        @foreach(\App\Models\Department::orderBy('name')->get() as $d)
                        <label class="cursor-pointer flex items-center gap-3 p-2.5 rounded-xl border border-zinc-800 bg-zinc-900 hover:border-[#f2f20d]/40 transition-all">
                            <input type="checkbox" name="target_departments[]" value="{{ $d->department_id }}"
                                   class="w-4 h-4 accent-[#f2f20d] cursor-pointer flex-shrink-0">
                            <span class="text-xs font-bold text-white">{{ $d->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- الموضوع --}}
                <div>
                    <label class="text-xs font-bold text-zinc-300 block mb-1">عنوان الإشعار</label>
                    <input name="subject" type="text" required placeholder="أدخل عنوان الإشعار التنبيهي"
                           class="w-full rounded-xl border border-zinc-800 bg-zinc-900 py-2.5 px-4 text-xs text-white focus:border-[#f2f20d] outline-none"/>
                </div>

                {{-- الرسالة --}}
                <div>
                    <label class="text-xs font-bold text-zinc-300 block mb-1">محتوى الإشعار</label>
                    <textarea name="message" rows="3" required placeholder="اكتب نص الإشعار هنا..."
                              class="w-full rounded-xl border border-zinc-800 bg-zinc-900 py-2.5 px-4 text-xs text-white focus:border-[#f2f20d] outline-none resize-none"></textarea>
                </div>

                <button type="submit"
                        class="w-full py-3 rounded-xl bg-[#f2f20d] text-black font-extrabold text-xs hover:bg-[#d9d90b] transition-all active:scale-[0.98] shadow-glow">
                    <i class="fa-solid fa-paper-plane ml-1"></i>
                    تأكيد وإرسال الإشعار
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
        if (!dot) return; // already read

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
</script>
@endpush
