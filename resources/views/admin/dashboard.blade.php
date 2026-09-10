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

<<<<<<< Updated upstream
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
=======
{{-- ===== Announcements Section ===== --}}
@php
    $announcementsCol = collect($announcements ?? []);
    $todayAnnouncements = $announcementsCol->filter(fn($item) => \Carbon\Carbon::parse($item->created_at)->isToday());
    $previousAnnouncements = $announcementsCol->reject(fn($item) => \Carbon\Carbon::parse($item->created_at)->isToday());
@endphp
>>>>>>> Stashed changes

{{-- ===== أخبار اليوم ===== --}}
<div class="space-y-4">
    <div class="flex items-center justify-between -mb-1">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></span>
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">أخبار اليوم</h3>
        </div>
        <a href="{{ route('admin.announcements.create') }}"
           class="flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold shadow-glow hover:scale-105 active:scale-95 transition-all"
           style="background:#f2f20d;color:#101924;">
            <span class="material-symbols-outlined text-[16px]">add</span>
            إضافة إعلان
        </a>
    </div>

    @forelse($todayAnnouncements as $post)
        @php
            $imgUrl   = (isset($post->image) && $post->image) ? Storage::url($post->image) : null;
            $isOwner  = isset($post->user_id) && $post->user_id == Auth::id();
            $postId   = $post->announcement_id ?? $post->id;
        @endphp
        @if($loop->first)
        {{-- الكارت الأول: نص يمين + صورة يسار كبيرة --}}
        <div class="flex flex-row-reverse rounded-2xl overflow-hidden bg-surface-light dark:bg-surface-dark shadow-soft border-2 border-emerald-500/30 group hover:shadow-md transition-shadow" style="min-height:240px;">
            {{-- صورة يسار --}}
            <div class="flex-shrink-0 relative bg-gradient-to-br from-slate-700 to-slate-900 overflow-hidden" style="width:42%;">
                @if($imgUrl)
                    <a href="{{ $imgUrl }}" target="_blank" download title="تنزيل الصورة" class="block w-full h-full absolute inset-0">
                        <img src="{{ $imgUrl }}" alt="{{ $post->title }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"/>
                    </a>
                @else
                    <span class="material-symbols-outlined absolute inset-0 m-auto text-[80px] text-white/10">campaign</span>
                @endif
            </div>
            {{-- نص يمين --}}
            <div class="flex-1 p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-500 text-white text-[11px] font-extrabold shadow-sm">اليوم</span>
                        @if($isOwner)
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <a href="{{ route('admin.announcements.edit', $postId) }}"
                               class="flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[11px] font-bold bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100 transition-colors">
                                <span class="material-symbols-outlined text-[14px]">edit</span> تعديل
                            </a>
                            <form action="{{ route('admin.announcements.delete', $postId) }}" method="POST"
                                  onsubmit="return confirm('هل تريد حذف هذا الإعلان؟')">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[11px] font-bold bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">delete</span> حذف
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                    <h4 class="text-base font-bold text-slate-900 dark:text-white leading-snug mb-2">{{ $post->title }}</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-4">{{ $post->content }}</p>
                </div>
                <div class="flex items-center gap-1.5 mt-3">
                    <span class="material-symbols-outlined text-slate-400 text-[15px]">schedule</span>
                    <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($post->created_at)->diffForHumans() }}</span>
                </div>
            </div>
        </div>
        @else
        {{-- الكروت التالية: نص يمين + صورة يسار --}}
        <div class="flex flex-row-reverse rounded-2xl overflow-hidden bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors" style="min-height:120px;">
            <div class="flex-shrink-0 relative bg-slate-100 dark:bg-slate-700/50 overflow-hidden" style="width:150px;">
                @if($imgUrl)
                    <a href="{{ $imgUrl }}" target="_blank" download class="block absolute inset-0">
                        <img src="{{ $imgUrl }}" alt="" class="w-full h-full object-cover"/>
                    </a>
                @else
                    <span class="material-symbols-outlined absolute inset-0 m-auto text-[35px] text-slate-400">article</span>
                @endif
            </div>
            <div class="flex-1 p-4 flex flex-col justify-center min-w-0">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <span class="inline-block px-2 py-0.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold rounded-md w-fit">اليوم</span>
                    @if($isOwner)
                    <div class="flex gap-1.5 flex-shrink-0">
                        <a href="{{ route('admin.announcements.edit', $postId) }}" class="p-1 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">edit</span>
                        </a>
                        <form action="{{ route('admin.announcements.delete', $postId) }}" method="POST" onsubmit="return confirm('حذف؟')">
                            @csrf
                            <button type="submit" class="p-1 rounded-lg text-red-500 hover:bg-red-50 transition-colors">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-snug mb-1">{{ $post->title }}</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-1">{{ Str::limit($post->content, 80) }}</p>
                <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($post->created_at)->diffForHumans() }}</span>
            </div>
        </div>
        @endif
    @empty
        <div class="p-4 text-center rounded-2xl bg-surface-light dark:bg-surface-dark border border-dashed border-slate-200 dark:border-slate-700 text-xs text-slate-400">
            لا توجد أخبار جديدة نشرت اليوم.
        </div>
    @endforelse
</div>

{{-- ===== الأخبار السابقة ===== --}}
<div class="space-y-4 mt-6">
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-slate-400 text-[18px]">history</span>
        <h3 class="text-sm font-bold text-slate-800 dark:text-white">الأخبار السابقة</h3>
    </div>

    @forelse($previousAnnouncements as $post)
        @php
            $imgUrl   = (isset($post->image) && $post->image) ? Storage::url($post->image) : null;
            $isOwner  = isset($post->user_id) && $post->user_id == Auth::id();
            $postId   = $post->announcement_id ?? $post->id;
        @endphp
        <div class="flex flex-row-reverse rounded-2xl overflow-hidden bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors opacity-90" style="min-height:110px;">
            <div class="flex-shrink-0 relative bg-slate-100 dark:bg-slate-700/50 overflow-hidden" style="width:140px;">
                @if($imgUrl)
                    <a href="{{ $imgUrl }}" target="_blank" download class="block absolute inset-0">
                        <img src="{{ $imgUrl }}" alt="" class="w-full h-full object-cover"/>
                    </a>
                @else
                    <span class="material-symbols-outlined absolute inset-0 m-auto text-[35px] text-slate-400">article</span>
                @endif
            </div>
            <div class="flex-1 p-4 flex flex-col justify-center min-w-0">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <span class="inline-block px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-500 text-[10px] font-bold rounded-md w-fit">سابق</span>
                    @if($isOwner)
                    <div class="flex gap-1.5 flex-shrink-0">
                        <a href="{{ route('admin.announcements.edit', $postId) }}" class="p-1 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">edit</span>
                        </a>
                        <form action="{{ route('admin.announcements.delete', $postId) }}" method="POST" onsubmit="return confirm('حذف؟')">
                            @csrf
                            <button type="submit" class="p-1 rounded-lg text-red-500 hover:bg-red-50 transition-colors">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-snug mb-1">{{ $post->title }}</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-1">{{ Str::limit($post->content, 80) }}</p>
                <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($post->created_at)->format('Y-m-d') }}</span>
            </div>
        </div>
    @empty
        <div class="p-4 text-center rounded-2xl bg-surface-light dark:bg-surface-dark border border-dashed border-slate-200 dark:border-slate-700 text-xs text-slate-400">
            لا توجد أخبار سابقة.
        </div>
    @endforelse
</div>

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

