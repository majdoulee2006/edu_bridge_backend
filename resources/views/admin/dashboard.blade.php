@extends('layouts.admin')

@section('title', 'الرئيسية')
@section('header-title', 'Edu-Bridge')
@section('header-subtitle', 'أهلاً، المدير ' . (Auth::user()->full_name ?? 'العام'))

@section('content')

    <!-- Announcements & Events Feed -->
    @forelse($announcements as $announcement)
        @if($loop->first && $announcement->image_path)
            <!-- Main Hero Announcement (Featured) -->
            <article class="relative flex flex-col rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft overflow-hidden group border border-slate-100/50 dark:border-slate-800/50 transition-colors">
                <div class="relative w-full h-48 overflow-hidden">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105" 
                         style="background-image: url('{{ asset('storage/' . $announcement->image_path) }}');">
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-4 right-4 text-white">
                        <span class="inline-block px-3 py-1 bg-primary rounded-full text-xs font-bold text-primary-content backdrop-blur-sm mb-2 shadow-sm">هام جداً</span>
                    </div>
                </div>
                <div class="p-5 flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">
                        {{ $announcement->title }}
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed line-clamp-2">
                        {{ $announcement->content }}
                    </p>

                    <div class="flex items-center justify-between mt-2 pt-3 border-t border-slate-100 dark:border-slate-800/50">
                        <span class="text-xs text-slate-400 font-medium">{{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}</span>
                        <a href="#" class="text-yellow-600 dark:text-yellow-400 text-sm font-bold flex items-center gap-1 hover:gap-2 transition-all">
                            تفاصيل الإعلان
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                        </a>
                    </div>
                </div>
            </article>
        @else
            <!-- Standard Announcement Card -->
            <article class="flex items-stretch gap-4 p-4 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100/50 dark:border-slate-800/50 transition-colors">
                <div class="flex-grow flex flex-col justify-center gap-2 min-w-0">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight truncate">
                        {{ $announcement->title }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2">
                        {{ $announcement->content }}
                    </p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 px-2 py-1 rounded-md font-medium">
                            {{ $announcement->category ?? 'إعلانات عامة' }}
                        </span>
                        <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}</span>
                    </div>
                </div>
                @if($announcement->image_path)
                    <div class="w-24 h-24 shrink-0 rounded-xl bg-cover bg-center" 
                         style="background-image: url('{{ asset('storage/' . $announcement->image_path) }}');">
                    </div>
                @else
                    <div class="w-24 h-24 shrink-0 rounded-xl bg-primary/10 dark:bg-primary/5 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-4xl">campaign</span>
                    </div>
                @endif
            </article>
        @endif
    @empty
        <!-- Fallback if no announcements -->
        <article class="flex flex-col items-center justify-center p-8 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft text-center border border-slate-100/50 dark:border-slate-800/50">
            <span class="material-symbols-outlined text-5xl text-primary mb-3">campaign</span>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">لا توجد إعلانات نشطة</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs">يمكنك البدء بإنشاء إعلان جديد بالضغط على الزر العائم بالأسفل ليظهر لجميع المعلمين والطلاب.</p>
        </article>
    @endforelse

    <!-- Upcoming Events (Meetings / Calendars) -->
    @forelse($events as $event)
        <article class="flex items-stretch gap-4 p-4 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border-r-4 border-primary border-y border-l border-slate-100/50 dark:border-slate-800/50 transition-colors">
            <div class="flex-grow flex flex-col justify-center gap-2">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-yellow-500 text-lg">event</span>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                        {{ $event->title }}
                    </h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    الموقع / القاعة: {{ $event->location ?? 'غير محدد' }}
                </p>
                <span class="text-[10px] text-slate-400 mt-1 font-semibold">
                    {{ \Carbon\Carbon::parse($event->event_date)->format('Y-m-d') }} - {{ $event->event_time ?? '00:00' }}
                </span>
            </div>
        </article>
    @empty
        <!-- Fallback if no events -->
        <article class="flex flex-col items-center justify-center p-8 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft text-center border border-slate-100/50 dark:border-slate-800/50">
            <span class="material-symbols-outlined text-5xl text-primary mb-3">event</span>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">لا توجد فعاليات قادمة</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs">يمكنك إضافة عطل أو فعاليات قادمة في التقويم الأكاديمي.</p>
        </article>
    @endforelse

    <!-- Empty spacing for bottom navigation bar -->
    <div class="h-12"></div>

    <!-- Floating action button to create announcement -->
    <a href="#" class="floating-add-btn fixed bottom-28 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-content shadow-glow transition-transform active:scale-95 hover:bg-yellow-300 focus:outline-none focus:ring-4 focus:ring-yellow-200 dark:focus:ring-yellow-900" title="إنشاء إعلان جديد">
        <span class="material-symbols-outlined text-3xl">add</span>
    </a>

@endsection
