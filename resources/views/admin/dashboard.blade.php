@extends('layouts.admin')

@section('title', 'الرئيسية')
@section('header-title', 'Edu-Bridge')
@section('header-subtitle', 'أهلاً، المدير ' . (Auth::user()->full_name ?? 'العام'))

@section('content')

    <!-- Announcements & Events Feed -->
    @forelse($announcements as $announcement)
        <!-- Premium News Card (Matching HOD layout style - Larger Scale) -->
        <article class="overflow-hidden bg-surface-light dark:bg-surface-dark rounded-[2rem] shadow-soft border border-slate-100/50 dark:border-slate-800/50 transition-all duration-300 hover:shadow-lg">
            <div class="relative w-full h-[240px] md:h-[280px]">
                @if($announcement->image_path)
                    <div class="w-full h-full bg-cover bg-center" style="background-image: url('{{ asset('storage/' . $announcement->image_path) }}');"></div>
                @else
                    <div class="w-full h-full flex items-center justify-center bg-primary/10 dark:bg-primary/5 text-primary">
                        <span class="material-symbols-outlined text-6xl">campaign</span>
                    </div>
                @endif
                <span class="absolute top-6 right-6 inline-block px-4 py-1.5 bg-primary text-primary-content text-xs font-bold rounded-full shadow-sm">
                    {{ $announcement->category ?? 'إعلان هام' }}
                </span>
            </div>
            
            <div class="p-8">
                <div class="flex justify-between items-center text-xs md:text-sm text-slate-400 dark:text-slate-500 mb-4 font-bold">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm md:text-base">schedule</span>
                        {{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm md:text-base">groups</span>
                        موجه إلى: {{ $announcement->target_audience == 'all' ? 'الجميع' : ($announcement->target_audience ?? 'الجميع') }}
                    </span>
                </div>
                
                <h4 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white mb-3 leading-tight">{{ $announcement->title }}</h4>
                <p class="text-sm md:text-base text-slate-550 dark:text-slate-400 leading-relaxed">{{ $announcement->content }}</p>
            </div>
        </article>
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
