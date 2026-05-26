@extends('layouts.admin')

@section('title', 'الإشعارات')
@section('header-title', 'الإشعارات')
@section('header-subtitle', 'أحدث التنبيهات والطلبات الواردة')

@push('styles')
<style>
    /* CSS للزر الجانبي مع خيارين */
    .corner-menu-overlay {
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .corner-menu {
        transform: scale(0);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        transform-origin: bottom right;
    }
    .corner-menu.active {
        transform: scale(1);
        opacity: 1;
    }
    .corner-fab-icon.active {
        transform: rotate(45deg);
    }
</style>
@endpush

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

    <div class="h-16"></div>

    <!-- ================= CORNER FAB FOR ADDING EVENTS & HOLIDAYS ================= -->
    <div class="fixed bottom-28 right-6 z-50">
        <!-- Main Button -->
        <button id="corner-fab" class="corner-fab relative flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-content shadow-glow transition-all active:scale-95 hover:bg-primary-dark focus:outline-none focus:ring-4 focus:ring-yellow-200 dark:focus:ring-yellow-900">
            <span class="material-symbols-outlined corner-fab-icon text-3xl transition-transform duration-300">add</span>
        </button>

        <!-- Dropdown Menu -->
        <div id="corner-menu" class="corner-menu absolute bottom-20 right-0 z-40 w-48 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 overflow-hidden">
            <div class="flex flex-col p-2">
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors text-right">
                    <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/20 flex items-center justify-center shrink-0 text-orange-600 dark:text-orange-400">
                        <span class="material-symbols-outlined">celebration</span>
                    </div>
                    <div class="flex flex-col items-start">
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">عطل رسمية</span>
                        <span class="text-xs text-slate-400">إضافة عطلة جديدة</span>
                    </div>
                </a>
                
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors text-right">
                    <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center shrink-0 text-purple-600 dark:text-purple-400">
                        <span class="material-symbols-outlined">event</span>
                    </div>
                    <div class="flex flex-col items-start">
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">فعاليات</span>
                        <span class="text-xs text-slate-400">إضافة فعالية جديدة</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Semi-transparent overlay to close dropdown on click outside -->
        <div id="corner-menu-overlay" class="corner-menu-overlay fixed inset-0 z-30 bg-black/20 dark:bg-black/40 backdrop-blur-sm"></div>
    </div>

@endsection

@push('scripts')
<script>
    // Corner menu FAB logic
    const cornerFab = document.getElementById('corner-fab');
    const cornerMenu = document.getElementById('corner-menu');
    const cornerMenuOverlay = document.getElementById('corner-menu-overlay');
    const cornerFabIcon = document.querySelector('.corner-fab-icon');
    
    let isMenuOpen = false;
    
    cornerFab.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleCornerMenu();
    });
    
    cornerMenuOverlay.addEventListener('click', function() {
        closeCornerMenu();
    });
    
    document.addEventListener('click', function(event) {
        if (!cornerFab.contains(event.target) && !cornerMenu.contains(event.target)) {
            closeCornerMenu();
        }
    });
    
    function toggleCornerMenu() {
        isMenuOpen = !isMenuOpen;
        if (isMenuOpen) {
            cornerMenu.classList.add('active');
            cornerMenuOverlay.style.opacity = '1';
            cornerMenuOverlay.style.pointerEvents = 'auto';
            cornerFabIcon.classList.add('active');
        } else {
            closeCornerMenu();
        }
    }
    
    function closeCornerMenu() {
        isMenuOpen = false;
        cornerMenu.classList.remove('active');
        cornerMenuOverlay.style.opacity = '0';
        cornerMenuOverlay.style.pointerEvents = 'none';
        cornerFabIcon.classList.remove('active');
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
