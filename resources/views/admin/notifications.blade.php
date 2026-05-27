@extends('layouts.admin')

@section('title', 'الإشعارات')
@section('header-title', 'الإشعارات')
@section('header-subtitle', 'أحدث التنبيهات والطلبات الواردة')

@section('header-actions')
    <div class="flex items-center gap-3">
        {{-- Add Event Dropdown --}}
        <div class="relative">
            <button id="add-event-dropdown-btn" class="flex items-center justify-center w-10 h-10 rounded-2xl bg-[#f2f20d] hover:bg-[#e0e00b] text-[#101924] shadow-soft hover:shadow-glow transition-all active:scale-95 duration-200">
                <span class="material-symbols-outlined text-[24px]">add</span>
            </button>
            
            {{-- Dropdown Menu --}}
            <div id="add-event-dropdown-menu" class="hidden absolute left-0 mt-2 w-48 rounded-2xl bg-white dark:bg-slate-800 shadow-lg border border-slate-100 dark:border-slate-700 z-50 overflow-hidden">
                <div class="flex flex-col p-1.5">
                    <button type="button" onclick="openAddEventModal('activity')" class="w-full flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors text-right">
                        <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center shrink-0 text-purple-600 dark:text-purple-400">
                            <span class="material-symbols-outlined text-lg">event</span>
                        </div>
                        <div class="flex flex-col items-start">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">فعالية جديدة</span>
                        </div>
                    </button>
                    
                    <button type="button" onclick="openAddEventModal('holiday')" class="w-full flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors text-right">
                        <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/20 flex items-center justify-center shrink-0 text-orange-600 dark:text-orange-400">
                            <span class="material-symbols-outlined text-lg">celebration</span>
                        </div>
                        <div class="flex flex-col items-start">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">عطلة جديدة</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        @if(isset($notifications) && $notifications->where('is_read', false)->count() > 0)
            <form action="{{ route('admin.notifications.read_all') }}" method="POST" class="flex items-center">
                @csrf
                <button type="submit" class="text-[11px] font-bold text-yellow-700 dark:text-yellow-400 px-3 py-1.5 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 transition-colors">قراءة الكل</button>
            </form>
        @endif
    </div>
@endsection

@section('content')

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
@endpush
