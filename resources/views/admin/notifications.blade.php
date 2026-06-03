@extends('layouts.admin')

@section('title', 'الإشعارات')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center justify-between mb-4">
        {{-- يمين: العنوان --}}
        <div class="flex flex-col">
            <h2 class="text-xl font-bold text-slate-800 dark:text-white leading-tight">الإشعارات</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500 mt-1">أحدث التنبيهات والطلبات الواردة</span>
        </div>

        {{-- يسار: زر إرسال إشعار --}}
        <button onclick="document.getElementById('sendNotifModal').classList.remove('hidden')"
                class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#f2f20d] hover:bg-[#e0e00b] text-[#101924] shadow-glow hover:scale-105 active:scale-95 transition-all font-bold text-xs">
            <span class="material-symbols-outlined text-[18px]">add</span>
            إرسال إشعار
        </button>
    </div>

    <div class="flex justify-between items-center px-1 mb-2">
        <h2 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">التنبيهات الإدارية</h2>
        @if($notifications->where('is_read', false)->count() > 0)
            <form action="{{ route('admin.notifications.read_all') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs font-bold text-primary hover:text-primary-dark transition-colors">تحديد الكل كمقروء</button>
            </form>
        @endif
    </div>

    @forelse($notifications as $notif)
        <div onclick="markAsRead({{ $notif->id }}, this)" 
             class="relative flex items-start gap-4 p-4 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer border border-slate-100/50 dark:border-slate-800/50">
            
            @php
                $icon = match($notif->type) {
                    'message' => 'chat_bubble',
                    'leave' => 'sick',
                    'account' => 'person_add',
                    default => 'campaign',
                };
                $iconColorClass = match($notif->type) {
                    'message' => 'text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/20',
                    'leave' => 'text-orange-600 dark:text-orange-400 bg-orange-100 dark:bg-orange-900/20',
                    'account' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/20',
                    default => 'text-yellow-700 dark:text-yellow-500 bg-yellow-100 dark:bg-yellow-900/20',
                };
            @endphp

            <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 {{ $iconColorClass }}">
                <span class="material-symbols-outlined text-2xl">{{ $icon }}</span>
            </div>
            
            <div class="flex-grow flex flex-col gap-1">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                        {{ $notif->title }}
                    </h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    {{ $notif->message }}
                </p>
                <span class="text-[10px] text-slate-400 font-medium mt-1">
                    {{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}
                </span>
            </div>

            @if(!$notif->is_read)
                <div class="unread-dot absolute top-4 left-4 w-2.5 h-2.5 rounded-full bg-primary shadow-glow"></div>
            @endif
        </div>
    @empty
        <!-- Fallback if no notifications -->
        <article class="flex flex-col items-center justify-center p-8 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft text-center border border-slate-100/50 dark:border-slate-800/50">
            <span class="material-symbols-outlined text-5xl text-primary mb-3">notifications_off</span>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">لا توجد إشعارات حالياً</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs">علبة الوارد الخاصة بك فارغة تماماً. سنقوم بإبلاغك فور وصول أي تنبيهات إدارية جديدة.</p>
        </article>
    @endforelse

    {{-- MODAL FOR ADDING EVENTS & HOLIDAYS --}}
    <div id="event-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-sm" onclick="closeEventModal()"></div>
        
        {{-- Modal Content --}}
        <div class="relative bg-surface-light dark:bg-surface-dark rounded-[2rem] p-6 shadow-glow w-[90%] max-w-md border border-slate-100 dark:border-slate-700/50 transform transition-all scale-95 duration-200 z-50">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/50 pb-4 mb-4">
                <h3 id="modal-title" class="text-base font-bold text-slate-900 dark:text-white">إضافة حدث</h3>
                <button type="button" onclick="closeEventModal()" class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            
            {{-- Form --}}
            <form action="{{ route('admin.calendar.store') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                
                {{-- Event Type (hidden/dynamic description) --}}
                <input type="hidden" name="type" id="modal-event-type" value="activity">
                
                {{-- Date --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-1">التاريخ</label>
                    <input type="date" name="event_date" required
                           class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-none focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white transition-all outline-none">
                </div>
                
                {{-- Title --}}
                <div class="flex flex-col gap-1.5">
                    <label id="title-label" class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-1">اسم الفعالية</label>
                    <input type="text" name="title" id="event-title-input" required placeholder="أدخل الاسم"
                           class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-none focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white transition-all outline-none">
                </div>
                
                {{-- Time (Only for Activity) --}}
                <div id="time-group" class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-1">الوقت</label>
                    <input type="time" name="event_time"
                           class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-none focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white transition-all outline-none">
                </div>
                
                {{-- Location (Only for Activity) --}}
                <div id="location-group" class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-1">المكان / القاعة</label>
                    <input type="text" name="location" placeholder="مثال: القاعة الكبرى، مختبر الحاسوب"
                           class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-none focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white transition-all outline-none">
                </div>
                
                {{-- Submit Button --}}
                <button type="submit" class="w-full py-4 rounded-2xl bg-[#f2f20d] hover:bg-[#e0e00b] text-[#101924] font-extrabold text-sm shadow-soft hover:shadow-glow transition-all mt-2">
                    تأكيد الحفظ
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Header actions dropdown logic
    const addEventBtn = document.getElementById('add-event-dropdown-btn');
    const addEventMenu = document.getElementById('add-event-dropdown-menu');
    
    if (addEventBtn && addEventMenu) {
        addEventBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            addEventMenu.classList.toggle('hidden');
        });
        
        document.addEventListener('click', function(event) {
            if (!addEventBtn.contains(event.target) && !addEventMenu.contains(event.target)) {
                addEventMenu.classList.add('hidden');
            }
        });
    }

    // Modal Control Logic
    function openAddEventModal(type) {
        if (addEventMenu) {
            addEventMenu.classList.add('hidden');
        }
        
        const modal = document.getElementById('event-modal');
        const modalTitle = document.getElementById('modal-title');
        const titleLabel = document.getElementById('title-label');
        const eventTitleInput = document.getElementById('event-title-input');
        const timeGroup = document.getElementById('time-group');
        const locationGroup = document.getElementById('location-group');
        const eventTypeInput = document.getElementById('modal-event-type');
        
        eventTypeInput.value = type;
        
        if (type === 'holiday') {
            modalTitle.innerText = 'إضافة عطلة رسمية جديدة';
            titleLabel.innerText = 'اسم العطلة / المناسبة';
            eventTitleInput.placeholder = 'مثال: عطلة عيد الفطر السعيد';
            timeGroup.classList.add('hidden');
            locationGroup.classList.add('hidden');
            // Remove required state for time if it has any, and clear
            const timeInput = timeGroup.querySelector('input');
            if (timeInput) {
                timeInput.required = false;
                timeInput.value = '';
            }
            const locInput = locationGroup.querySelector('input');
            if (locInput) locInput.value = '';
        } else {
            modalTitle.innerText = 'إضافة فعالية جديدة';
            titleLabel.innerText = 'اسم الفعالية';
            eventTitleInput.placeholder = 'مثال: ندوة الذكاء الاصطناعي الأكاديمية';
            timeGroup.classList.remove('hidden');
            locationGroup.classList.remove('hidden');
        }
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.querySelector('.relative').classList.remove('scale-95');
        }, 10);
    }
    
    function closeEventModal() {
        const modal = document.getElementById('event-modal');
        modal.querySelector('.relative').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 150);
    }

    // Mark as read AJAX logic
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
            }
        })
        .catch(err => console.error(err));
    }
</script>
// إغلاق الـ modal بالضغط خارجه
document.getElementById('sendNotifModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
@endpush

{{-- Modal إرسال إشعار --}}
<div id="sendNotifModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl p-6" dir="rtl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white">إرسال إشعار جديد</h3>
            <button onclick="document.getElementById('sendNotifModal').classList.add('hidden')"
                    class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form action="{{ route('admin.messages.send') }}" method="POST" class="flex flex-col gap-4">
            @csrf

            {{-- الجمهور --}}
            <div>
                <label class="text-sm font-bold text-slate-700 dark:text-slate-200 block mb-2">إرسال إلى</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input checked class="peer sr-only" name="recipient_type" value="all" type="radio"
                               onchange="document.getElementById('deptSelectorModal').classList.add('hidden')"/>
                        <div class="flex items-center justify-center gap-2 p-3.5 rounded-2xl border-2 border-transparent bg-slate-50 dark:bg-slate-800 peer-checked:border-[#f2f20d] peer-checked:bg-yellow-50/10 transition-all">
                            <span class="material-symbols-outlined text-xl text-slate-400 peer-checked:text-[#f2f20d]">groups</span>
                            <p class="text-sm font-bold text-slate-800 dark:text-white">الجميع</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input class="peer sr-only" name="recipient_type" value="departments" type="radio"
                               onchange="document.getElementById('deptSelectorModal').classList.remove('hidden')"/>
                        <div class="flex items-center justify-center gap-2 p-3.5 rounded-2xl border-2 border-transparent bg-slate-50 dark:bg-slate-800 peer-checked:border-[#f2f20d] peer-checked:bg-yellow-50/10 transition-all">
                            <span class="material-symbols-outlined text-xl text-slate-400">domain</span>
                            <p class="text-sm font-bold text-slate-800 dark:text-white">قسم محدد</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- اختيار القسم - تظهر فقط عند اختيار "قسم محدد" --}}
            <div id="deptSelectorModal" class="hidden">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-200 block mb-2">الأقسام</label>
                <div class="flex flex-col gap-2">
                    @foreach(\App\Models\Department::orderBy('name')->get() as $d)
                    <label class="cursor-pointer flex items-center gap-3 p-3.5 rounded-2xl border-2 border-transparent bg-slate-50 dark:bg-slate-800 hover:border-[#f2f20d]/40 transition-all has-[:checked]:border-[#f2f20d] has-[:checked]:bg-yellow-50/5">
                        <input type="checkbox" name="target_departments[]" value="{{ $d->department_id }}"
                               class="w-4 h-4 accent-[#f2f20d] cursor-pointer flex-shrink-0">
                        <span class="text-sm font-bold text-slate-800 dark:text-white">{{ $d->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- الموضوع --}}
            <div>
                <label class="text-sm font-bold text-slate-700 dark:text-slate-200 block mb-1">الموضوع</label>
                <input name="subject" type="text" required placeholder="موضوع الإشعار"
                       class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 py-3 px-4 text-sm text-slate-800 dark:text-white focus:border-[#f2f20d] outline-none"/>
            </div>

            {{-- الرسالة --}}
            <div>
                <label class="text-sm font-bold text-slate-700 dark:text-slate-200 block mb-1">نص الإشعار</label>
                <textarea name="message" rows="3" required placeholder="اكتب نص الإشعار هنا..."
                          class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 py-3 px-4 text-sm text-slate-800 dark:text-white focus:border-[#f2f20d] outline-none resize-none"></textarea>
            </div>

            <button type="submit"
                    class="w-full py-3.5 rounded-2xl bg-[#f2f20d] text-[#101924] font-extrabold text-sm hover:bg-[#e0e00b] transition-all active:scale-[0.98]">
                <span class="material-symbols-outlined text-lg align-middle ml-1">send</span>
                إرسال الإشعار
            </button>
        </form>
    </div>
</div>
