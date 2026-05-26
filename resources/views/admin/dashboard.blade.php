@extends('layouts.admin')

@section('title', 'الرئيسية')
@section('header-title', 'Edu-Bridge')
@section('header-subtitle', 'أهلاً، المدير ' . (Auth::user()->full_name ?? 'العام'))

@push('styles')
<style>
    .news-card {
        border-radius: 1rem;
        overflow: hidden;
        background-color: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        margin-bottom: 1.5rem;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    .dark class .news-card, 
    .dark .news-card {
        background-color: #1e1e1e;
        border-color: #3f3f46;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
    }
    
    .news-image {
        width: 100%;
        height: 200px;
        background-color: #e5e7eb;
        object-fit: cover;
    }
    .dark .news-image {
        background-color: #121212;
    }
    
    .news-content {
        padding: 1.5rem;
    }
    
    .news-meta {
        display: flex;
        justify-content: space-between;
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }
    .dark .news-meta {
        color: #a1a1aa;
    }
    
    .news-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #1a1a1a;
        line-height: 1.4;
    }
    .dark .news-title {
        color: #ffffff;
    }
    
    .news-excerpt {
        color: #6b7280;
        line-height: 1.6;
        font-size: 0.95rem;
    }
    .dark .news-excerpt {
        color: #a1a1aa;
    }

    .badge-yellow {
        background-color: #fce300 !important;
        color: #1a1a1a !important;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
</style>
@endpush

@section('content')

    <!-- Announcements Section -->
    <div class="flex items-center justify-between px-1 mb-2">
        <h3 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
            <span class="w-1.5 h-6 bg-primary rounded-full"></span>
            آخر الأخبار والإعلانات
        </h3>
    </div>

    @forelse($announcements as $announcement)
        <!-- News Card (100% Identical to HOD layout styles) -->
        <div class="news-card">
            <div style="position: relative;">
                @if($announcement->image_path)
                    <img src="{{ asset('storage/' . $announcement->image_path) }}" class="news-image" alt="News Image">
                @else
                    <div class="news-image" style="display: flex; align-items: center; justify-content: center; background-color: rgba(252, 227, 0, 0.12); color: #ca8a04;">
                        <i class="fa-solid fa-bullhorn" style="font-size: 3rem;"></i>
                    </div>
                @endif
                <span class="badge-yellow" style="position: absolute; top: 1rem; right: 1rem;">{{ $announcement->category ?? 'عام' }}</span>
            </div>
            <div class="news-content">
                <div class="news-meta">
                    <span><i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}</span>
                    <span>موجه إلى: {{ $announcement->target_audience == 'all' ? 'الجميع' : ($announcement->target_audience ?? 'الجميع') }}</span>
                </div>
                <h4 class="news-title">{{ $announcement->title }}</h4>
                <p class="news-excerpt">{{ Str::limit($announcement->content, 180) }}</p>
            </div>
        </div>
    @empty
        <div class="news-card" style="text-align: center; padding: 2rem; color: #64748b;">
            لا توجد إعلانات حالياً.
        </div>
    @endforelse

    <!-- Upcoming Events Section -->
    <div class="flex items-center justify-between px-1 mt-4 mb-2">
        <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
            <span class="w-1.5 h-6 bg-yellow-500 rounded-full"></span>
            الفعاليات والأحداث القادمة
        </h3>
    </div>

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
        <div class="news-card" style="text-align: center; padding: 2rem; color: #64748b;">
            لا توجد فعاليات قادمة.
        </div>
    @endforelse

    <!-- Empty spacing for bottom navigation bar -->
    <div class="h-12"></div>

    <!-- Floating action button to create announcement -->
    <a href="{{ route('admin.messages') }}" class="floating-add-btn fixed bottom-28 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-content shadow-glow transition-transform active:scale-95 hover:bg-yellow-300 focus:outline-none focus:ring-4 focus:ring-yellow-200 dark:focus:ring-yellow-900" title="إرسال تعميم جديد">
        <span class="material-symbols-outlined text-3xl">add</span>
    </a>

@endsection
