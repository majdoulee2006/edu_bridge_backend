@extends('layouts.hod')

@section('title', 'Ø§Ù„Ø±Ø¦ÙŠØ³ÙŠØ©')

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
        height: 200px;
        background-color: #e5e7eb;
        object-fit: cover;
    }
    
    .news-content {
        padding: 1.5rem;
    }
    
    .news-meta {
        display: flex;
        justify-content: space-between;
        color: var(--text-secondary);
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }
    
    .news-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .news-excerpt {
        color: var(--text-secondary);
        line-height: 1.6;
    }
</style>
@endpush

@section('content')

    <div class="welcome-header">
        <div class="welcome-text">
            <h2>Edu-Bridge</h2>
            <p>Ù…Ø±Ø­Ø¨Ø§Ù‹ØŒ {{ auth()->user()->full_name ?? 'Ø±Ø¦ÙŠØ³ Ø§Ù„Ù‚Ø³Ù…' }}</p>
        </div>
    </div>

    <!-- News Section -->
    <div class="section-title">
        <h3>Ø¢Ø®Ø± Ø§Ù„Ø£Ø®Ø¨Ø§Ø± ÙˆØ§Ù„Ø¥Ø¹Ù„Ø§Ù†Ø§Øª</h3>
        <a href="{{ route('hod.announcements.create') }}" class="btn btn-primary" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600;">Ø¥Ù†Ø´Ø§Ø¡ Ø¥Ø¹Ù„Ø§Ù†</a>
    </div>

    @forelse($announcements as $announcement)
    <!-- News Card -->
    <div class="news-card">
        <div style="position: relative;">
            @if($announcement->image)
                <a href="{{ asset('storage/' . $announcement->image) }}" target="_blank" download
                   style="display:block; position:relative;">
                    <img src="{{ asset('storage/' . $announcement->image) }}" class="news-image" alt="News Image" style="cursor:pointer;">
                    <span style="position:absolute; bottom:0.5rem; left:0.5rem; background:rgba(0,0,0,0.6); color:white; padding:0.3rem 0.7rem; border-radius:0.5rem; font-size:0.8rem;">
                        <i class="fa-solid fa-download"></i> تنزيل
                    </span>
                </a>
            @else
                <div class="news-image" style="display: flex; align-items: center; justify-content: center; background-color: #fce30020; color: #ca8a04;">
                    <i class="fa-solid fa-bullhorn" style="font-size: 3rem;"></i>
                </div>
            @endif
            <span class="badge badge-yellow" style="position: absolute; top: 1rem; right: 1rem;">{{ $announcement->category ?? 'Ø¥Ø¹Ù„Ø§Ù† Ù‡Ø§Ù…' }}</span>
        </div>
        <div class="news-content">
            <div class="news-meta">
                <span><i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}</span>
                <span>Ù…ÙˆØ¬Ù‡ Ø¥Ù„Ù‰: {{ $announcement->target_audience == 'all' ? 'Ø§Ù„Ø¬Ù…ÙŠØ¹' : $announcement->target_audience }}</span>
            </div>
            <h4 class="news-title">{{ $announcement->title }}</h4>
            <p class="news-excerpt">{{ Str::limit($announcement->content, 150) }}</p>
        </div>
    </div>
    @empty
    <div class="card" style="text-align: center; color: var(--text-secondary);">
        Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¥Ø¹Ù„Ø§Ù†Ø§Øª Ø­Ø§Ù„ÙŠØ§Ù‹.
    </div>
    @endforelse

@endsection

