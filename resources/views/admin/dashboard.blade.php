@extends('layouts.admin')

@section('title', 'الرئيسية')


@section('content')

{{-- ===== Welcome Banner ===== --}}
<div class="relative rounded-2xl overflow-hidden bg-gradient-to-l from-yellow-400 to-yellow-300 dark:from-yellow-500 dark:to-yellow-400 p-5 shadow-glow">
    <div class="absolute left-0 top-0 bottom-0 w-28 opacity-10 pointer-events-none overflow-hidden">
        <span class="material-symbols-outlined text-[110px] text-black absolute -left-3 -top-3">admin_panel_settings</span>
    </div>
    <p class="text-[10px] font-extrabold text-yellow-900/60 mb-0.5 uppercase tracking-widest">لوحة التحكم</p>
    <h2 class="text-xl font-extrabold text-slate-900 leading-tight">مرحباً، {{ Auth::user()->full_name ?? 'المدير العام' }} ðŸ‘‹</h2>
    <p class="text-xs text-slate-800/70 mt-1">
        @php $pending = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count(); @endphp
        @if($pending > 0)
            لديك <strong>{{ $pending }}</strong> {{ $pending == 1 ? 'إشعار' : 'إشعارات' }} تنتظر المراجعة
        @else
            لا توجد إشعارات جديدة، كل شيء على ما يرام ✓
        @endif
    </p>
</div>

{{-- ===== Quick Stats ===== --}}
<div class="grid grid-cols-3 gap-3">
    @php
        $totalUsers = \App\Models\User::count();
        $totalCourses = \App\Models\Course::count();
        $pendingRequests = \App\Models\Notification::where('is_read', false)->count();
    @endphp
    <div class="flex flex-col items-center gap-1.5 p-3 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 hover:border-primary/30 transition-colors">
        <div class="w-9 h-9 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-500 text-[18px]">group</span>
        </div>
        <span class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $totalUsers }}</span>
        <span class="text-[10px] font-medium text-slate-400 text-center">الحسابات</span>
    </div>
    <div class="flex flex-col items-center gap-1.5 p-3 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 hover:border-primary/30 transition-colors">
        <div class="w-9 h-9 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
            <span class="material-symbols-outlined text-emerald-500 text-[18px]">school</span>
        </div>
        <span class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $totalCourses }}</span>
        <span class="text-[10px] font-medium text-slate-400 text-center">الدورات</span>
    </div>
    <div class="flex flex-col items-center gap-1.5 p-3 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 hover:border-primary/30 transition-colors">
        <div class="w-9 h-9 rounded-full bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center">
            <span class="material-symbols-outlined text-orange-500 text-[18px]">pending_actions</span>
        </div>
        <span class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $pendingRequests }}</span>
        <span class="text-[10px] font-medium text-slate-400 text-center">طلبات</span>
    </div>
</div>

{{-- ===== Announcements Header ===== --}}
<div class="flex items-center justify-between -mb-1">
    <div class="flex items-center gap-2">
        <span class="w-1 h-5 bg-primary rounded-full"></span>
        <h3 class="text-sm font-bold text-slate-800 dark:text-white">آخر الأخبار والإعلانات</h3>
    </div>
    <button class="text-xs font-bold text-yellow-600 dark:text-yellow-400 hover:underline">عرض الكل</button>
</div>

{{-- ===== News Cards ===== --}}
@forelse($announcements ?? [] as $post)
    @if($loop->first)
    {{-- Large card for first item --}}
    <div class="relative rounded-2xl overflow-hidden bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 group hover:shadow-md transition-shadow">
        <div class="h-44 bg-gradient-to-br from-slate-700 to-slate-900 relative overflow-hidden">
            @if(isset($post->image) && $post->image)
                <img src="{{ Storage::url($post->image) }}" alt="{{ $post->title }}" class="absolute inset-0 w-full h-full object-cover opacity-70 group-hover:scale-105 transition-transform duration-700"/>
            @else
                <div class="absolute inset-0 bg-gradient-to-br from-slate-600 to-slate-800 opacity-80"></div>
                <span class="material-symbols-outlined absolute inset-0 m-auto text-[80px] text-white/10">campaign</span>
            @endif
            <div class="absolute bottom-3 right-3 z-10">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-primary text-primary-content text-[10px] font-extrabold shadow-sm">إعلان هام</span>
            </div>
        </div>
        <div class="p-4">
            <div class="flex items-center gap-1.5 mb-2">
                <span class="material-symbols-outlined text-slate-400 text-[13px]">schedule</span>
                <span class="text-[10px] text-slate-400 font-medium">{{ \Carbon\Carbon::parse($post->created_at)->diffForHumans() }}</span>
            </div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1 leading-snug">{{ $post->title }}</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2">{{ $post->content }}</p>
        </div>
    </div>
    @else
    {{-- Compact card for other items --}}
    <div class="flex items-start gap-3 p-4 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer">
        <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-700/50 flex-shrink-0 overflow-hidden flex items-center justify-center">
            @if(isset($post->image) && $post->image)
                <img src="{{ Storage::url($post->image) }}" alt="" class="w-full h-full object-cover"/>
            @else
                <span class="material-symbols-outlined text-slate-400 text-[28px]">article</span>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <span class="inline-block px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 text-[9px] font-bold rounded-md mb-1">إداري</span>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-snug mb-1 truncate">{{ $post->title }}</h4>
            <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($post->created_at)->diffForHumans() }}</span>
        </div>
    </div>
    @endif
@empty
    {{-- Fallback static cards when no data --}}
    <div class="relative rounded-2xl overflow-hidden bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 group hover:shadow-md transition-shadow">
        <div class="h-44 relative overflow-hidden" style="background: linear-gradient(135deg, #1e3a5f 0%, #152d45 100%);">
            <span class="material-symbols-outlined absolute inset-0 m-auto text-[80px] text-white/5">campaign</span>
            <div class="absolute bottom-3 right-3 z-10">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-primary text-primary-content text-[10px] font-extrabold shadow-sm">إعلان هام</span>
            </div>
        </div>
        <div class="p-4">
            <div class="flex items-center gap-1.5 mb-2">
                <span class="material-symbols-outlined text-slate-400 text-[13px]">schedule</span>
                <span class="text-[10px] text-slate-400 font-medium">منذ ساعتين</span>
            </div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">موعد الامتحانات النهائية لل�صل الأول</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2">تم اعتماد جدول الامتحانات النهائية وسيتم نشره على جميع المنصات الرسمية.</p>
        </div>
    </div>

    <div class="flex items-start gap-3 p-4 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer">
        <div class="w-16 h-16 rounded-xl bg-blue-50 dark:bg-slate-700/50 flex-shrink-0 flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-400 text-[28px]">policy</span>
        </div>
        <div class="flex-1">
            <span class="inline-block px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 text-[9px] font-bold rounded-md mb-1">إداري</span>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-snug mb-1">تحديث سياسة الحضور والغياب</h4>
            <span class="text-[10px] text-slate-400">أمس · 04:30 م</span>
        </div>
    </div>

    <div class="flex items-start gap-3 p-4 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border-r-4 border-primary border-t border-b border-l border-slate-100 dark:border-slate-700/50">
        <div class="w-10 h-10 rounded-xl bg-yellow-50 dark:bg-yellow-900/20 flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-yellow-500 text-[20px]">event</span>
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-0.5">اجتماع مجلس الإدارة</h4>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">مناقشة الميزانية السنوية للمؤسسة</p>
            <span class="text-[10px] text-slate-400 mt-0.5 block">الأحد القادم · 10:00 صباحاً</span>
        </div>
    </div>
@endforelse

{{-- Spacer --}}
<div class="h-4"></div>

@endsection

@push('scripts')
<script>
    // Auto-dismiss flash messages after 4s
    setTimeout(() => {
        document.querySelectorAll('.alert-toast').forEach(el => {
            el.style.transition = 'opacity 0.4s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        });
    }, 4000);
</script>
@endpush

