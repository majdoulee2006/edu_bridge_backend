@extends('layouts.admin')

@section('title', 'الرئيسية')


@section('content')

{{-- ===== Welcome Banner ===== --}}
<div class="relative rounded-2xl overflow-hidden bg-primary text-primary-content p-5 shadow-glow">
    <div class="absolute left-0 top-0 bottom-0 w-28 opacity-10 pointer-events-none overflow-hidden">
        <span class="material-symbols-outlined text-[110px] text-black absolute -left-3 -top-3">admin_panel_settings</span>
    </div>
    <p class="text-[10px] font-extrabold opacity-75 mb-0.5 uppercase tracking-widest">لوحة التحكم</p>
    <h2 class="text-xl font-extrabold leading-tight">مرحباً، {{ Auth::user()->full_name ?? 'المدير العام' }}</h2>
    <p class="text-xs opacity-90 mt-1">
        @php $pending = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count(); @endphp
        @if($pending > 0)
            لديك <strong>{{ $pending }}</strong> {{ $pending == 1 ? 'إشعار' : 'إشعارات' }} تنتظر المراجعة
        @else
            لا توجد إشعارات جديدة، كل شيء على ما يرام ✓
        @endif
    </p>
</div>

{{-- ===== Quick Stats ===== --}}
@php
    $totalUsers   = \App\Models\User::count();
    $totalCourses = \App\Models\Course::count();
@endphp
<div class="grid grid-cols-2 gap-4">
    <a href="{{ route('admin.accounts') }}" class="flex items-center gap-4 p-4 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 hover:border-primary/50 hover:scale-[1.02] active:scale-98 transition-all cursor-pointer group">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-blue-500 text-[24px]">group</span>
        </div>
        <div>
            <span class="text-2xl font-black text-slate-900 dark:text-white block group-hover:text-primary transition-colors">{{ $totalUsers }}</span>
            <span class="text-xs font-semibold text-slate-400">إجمالي الحسابات</span>
        </div>
    </a>
    <a href="{{ route('admin.courses') }}" class="flex items-center gap-4 p-4 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 hover:border-primary/50 hover:scale-[1.02] active:scale-98 transition-all cursor-pointer group">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-emerald-500 text-[24px]">school</span>
        </div>
        <div>
            <span class="text-2xl font-black text-slate-900 dark:text-white block group-hover:text-primary transition-colors">{{ $totalCourses }}</span>
            <span class="text-xs font-semibold text-slate-400">الدورات الدراسية</span>
        </div>
    </a>
</div>

{{-- ===== Announcements Header ===== --}}
<div class="flex items-center justify-between -mb-1">
    <div class="flex items-center gap-2">
        <span class="w-1 h-5 bg-primary rounded-full"></span>
        <h3 class="text-sm font-bold text-slate-800 dark:text-white">آخر الأخبار والإعلانات</h3>
    </div>
    <a href="{{ route('admin.announcements.create') }}"
       class="flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold bg-primary text-primary-content shadow-glow hover:scale-105 active:scale-95 transition-all">
        <span class="material-symbols-outlined text-[16px]">add</span>
        إضافة إعلان
    </a>
</div>

{{-- ===== News Cards ===== --}}
@forelse($announcements ?? [] as $post)
    @php
        $imgsArr = [];
        if (!empty($post->images)) {
            $imgsArr = is_string($post->images) ? json_decode($post->images, true) : $post->images;
        }
        if (empty($imgsArr) && !empty($post->image)) {
            $imgsArr = [$post->image];
        }

        $formattedImgs = [];
        if (is_array($imgsArr)) {
            foreach ($imgsArr as $img) {
                if ($img) {
                    $formattedImgs[] = str_starts_with($img, 'http') ? $img : asset('storage/' . ltrim($img, '/'));
                }
            }
        }

        $imgUrl = !empty($formattedImgs) ? $formattedImgs[0] : null;
        $imgCount = count($formattedImgs);
        $isOwner  = isset($post->user_id) && $post->user_id == Auth::id();
        $postId   = $post->announcement_id ?? $post->id;
    @endphp
    {{-- كارت الإعلان --}}
    <div class="rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 p-6 flex flex-col gap-4 group hover:shadow-md transition-shadow">
        {{-- الهيدر: شارات وأزرار التحكّم --}}
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-bold flex-shrink-0">
                    <span class="material-symbols-outlined text-xl">campaign</span>
                </div>
                <div>
                    <h4 class="text-base font-bold text-slate-900 dark:text-white leading-snug">{{ $post->title }}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-primary/20 text-primary text-[10px] font-extrabold">
                            {{ $loop->first ? 'إعلان هام' : 'إداري' }}
                        </span>
                        <span class="text-xs text-slate-400 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]">schedule</span>
                            {{ \Carbon\Carbon::parse($post->created_at)->diffForHumans() }}
                        </span>
                    </div>
                </div>
            </div>

            @if($isOwner)
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <a href="{{ route('admin.announcements.edit', $postId) }}"
                   class="flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100 transition-colors">
                    <span class="material-symbols-outlined text-[15px]">edit</span> تعديل
                </a>
                <form action="{{ route('admin.announcements.delete', $postId) }}" method="POST"
                      onsubmit="return confirm('هل تريد حذف هذا الإعلان؟')">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 transition-colors">
                        <span class="material-symbols-outlined text-[15px]">delete</span> حذف
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- المحتوى النصي --}}
        @if(!empty($post->content))
            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-normal">
                {{ $post->content }}
            </p>
        @endif

        {{-- شبكة الصور بعرض كامل وبدون أي اقتطاع قسري --}}
        @if(!empty($formattedImgs))
            <div class="w-full rounded-2xl overflow-hidden shadow-xs border border-slate-200/60 dark:border-slate-700/60" style="height: 320px;">
                @include('partials.announcement_image_grid', ['images' => $formattedImgs, 'id' => $postId])
            </div>
        @endif

        {{-- رابط خارجي إذا وجد --}}
        @if(!empty($post->link_url))
            <div class="pt-1">
                <a href="{{ $post->link_url }}" target="_blank"
                   class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:underline">
                    <span class="material-symbols-outlined text-sm">open_in_new</span> فتح الرابط المرفق
                </a>
            </div>
        @endif
    </div>
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
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">موعد الامتحانات النهائية للفصل الأول</h4>
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

