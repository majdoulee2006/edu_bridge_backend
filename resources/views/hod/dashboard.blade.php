@extends('layouts.hod')

@section('title', 'الرئيسية')

@push('styles')
<style>
    .welcome-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    .welcome-text h2 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
    }
    .welcome-text p {
        color: var(--text-secondary);
        font-size: 1.1rem;
    }
    
    .alert-box {
        background-color: #fefce8; /* very light yellow */
        border: 1px solid #fef08a;
        border-radius: 1rem;
        padding: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        margin-bottom: 2rem;
    }
    [data-theme="dark"] .alert-box {
        background-color: #3f3f1e;
        border-color: #716616;
    }
    
    .alert-icon {
        background-color: var(--accent-color);
        color: #1a1a1a;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
    }
    
    .section-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .section-title h3 {
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .section-title a {
        color: var(--accent-color);
        text-decoration: none;
        font-weight: 600;
    }
    
    .news-card {
        border-radius: 1rem;
        overflow: hidden;
        background-color: var(--bg-secondary);
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
    }
    
    .news-image {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
        transition: transform 0.3s ease;
    }
    .news-card:hover .news-image { transform: scale(1.02); }
    .news-img-wrap {
        overflow: hidden;
        position: relative;
        max-height: 220px;
    }
    .news-placeholder {
        width: 100%;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #fef9c3, #fde68a);
        color: #ca8a04;
    }
    .news-content { padding: 1.25rem 1.5rem 1.5rem; }
    .news-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--text-secondary);
        font-size: 0.8rem;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
        gap: 0.4rem;
    }
    .audience-badge {
        background: #fef3c7;
        color: #92400e;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .news-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }
    .news-excerpt {
        color: var(--text-secondary);
        line-height: 1.7;
        font-size: 0.9rem;
        margin-bottom: 0.75rem;
    }
    .news-link-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: var(--accent-color);
        color: #1a1a1a;
        padding: 0.45rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: opacity 0.2s;
        margin-top: 0.5rem;
    }
    .news-link-btn:hover { opacity: 0.85; }
</style>
@endpush

@section('content')

    <div class="welcome-header">
        <div class="welcome-text">
            <h2>Edu-Bridge</h2>
            <p>مرحباً، {{ auth()->user()->full_name ?? 'رئيس القسم' }}</p>
        </div>
    </div>

    <!-- News Section -->
    <div class="section-title">
        <h3>آخر الأخبار والإعلانات</h3>
        <a href="{{ route('hod.announcements.create') }}" class="btn btn-primary" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600;">إنشاء إعلان</a>
    </div>

    @forelse($announcements as $announcement)
    <div class="news-card">
        {{-- الصورة --}}
        @if($announcement->image)
        <div class="news-img-wrap">
            <a href="{{ asset('storage/' . $announcement->image) }}" target="_blank">
                <img src="{{ asset('storage/' . $announcement->image) }}"
                     class="news-image" alt="{{ $announcement->title }}">
            </a>
        </div>
        @else
        <div class="news-placeholder">
            <i class="fa-solid fa-bullhorn" style="font-size:2.5rem; opacity:0.6;"></i>
        </div>
        @endif

        <div class="news-content">
            {{-- الميتا --}}
            <div class="news-meta">
                <span><i class="fa-regular fa-clock"></i>
                    {{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}
                </span>
                <span class="audience-badge">
                    @php
                        $aud = $announcement->target_audience ?? 'all';
                        echo match($aud) { 'students'=>'الطلاب', 'teachers'=>'المعلمون', default=>'الجميع' };
                    @endphp
                </span>
            </div>

            {{-- العنوان والمحتوى --}}
            <h4 class="news-title">{{ $announcement->title }}</h4>
            <p class="news-excerpt">{{ Str::limit($announcement->content, 180) }}</p>

            {{-- الرابط --}}
            @if($announcement->link_url)
            <a href="{{ $announcement->link_url }}" target="_blank" class="news-link-btn">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                فتح الرابط
            </a>
            @endif
        </div>
    </div>
    @empty
    <div class="card" style="text-align:center; padding:2rem; color:var(--text-secondary);">
        <i class="fa-solid fa-bullhorn" style="font-size:2rem; opacity:0.3; margin-bottom:0.5rem; display:block;"></i>
        لا توجد إعلانات حالياً.
    </div>
    @endforelse

@endsection

