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

@section('subtitle', 'مرحباً، ' . (auth()->user()->full_name ?? 'رئيس القسم'))

    {{-- ===== Stats (3 cards) ===== --}}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 2rem;">

        {{-- Card 1: المعلمون --}}
        <div class="stat-card" onclick="window.location.href='{{ url('/hod/accounts?role=teacher') }}'" title="اضغط لعرض المعلمين">
            <div class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div>
                <div class="stat-value">{{ $teachersCount ?? 0 }}</div>
                <div class="stat-label">عدد المعلمين</div>
                <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
            </div>
        </div>

        {{-- Card 2: الطلاب --}}
        <div class="stat-card" onclick="window.location.href='{{ url('/hod/accounts?role=student') }}'" title="اضغط لعرض الطلاب">
            <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
            <div>
                <div class="stat-value">{{ $studentsCount ?? 0 }}</div>
                <div class="stat-label">عدد الطلاب</div>
                <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
            </div>
        </div>

        {{-- Card 3: المواد الدراسية --}}
        <div class="stat-card stat-card-plain">
            <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
            <div>
                <div class="stat-value">{{ $coursesCount ?? 0 }}</div>
                <div class="stat-label">إجمالي المواد</div>
            </div>
        </div>

    </div>

    <!-- News Section -->
    <div class="section-title">
        <h3>آخر الأخبار والإعلانات</h3>
        <a href="{{ route('hod.announcements.create') }}" class="btn btn-primary" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600;">إنشاء إعلان</a>
    </div>

    @forelse($announcements as $index => $announcement)
        @if($index === 0)
            <!-- Large Card -->
            <div class="announce-card-large">
                <div class="announce-large-header">
                    @if($announcement->image_path)
                        <img src="{{ asset('storage/' . $announcement->image_path) }}" class="announce-large-img" alt="صورة الإعلان">
                    @else
                        <div class="announce-large-icon"><i class="fa-solid fa-bullhorn"></i></div>
                    @endif
                    <span class="announce-badge">{{ $announcement->category ?? 'إعلان هام' }}</span>
                </div>
                <div class="announce-large-body">
                    <div class="announce-meta">
                        <i class="fa-regular fa-clock"></i>
                        <span>{{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}</span>
                        <span style="margin: 0 0.25rem;">·</span>
                        <span>موجه إلى: {{ $announcement->target_audience == 'all' ? 'الجميع' : $announcement->target_audience }}</span>
                    </div>
                    <h4 class="announce-title">{{ $announcement->title }}</h4>
                    <p class="announce-text">{{ Str::limit($announcement->content, 150) }}</p>
                </div>
            </div>
        @else
            <!-- Compact Card -->
            <div class="announce-card-compact">
                <div class="announce-compact-icon">
                    @if($announcement->image_path)
                        <img src="{{ asset('storage/' . $announcement->image_path) }}" alt="صورة">
                    @else
                        <i class="fa-solid fa-file-lines"></i>
                    @endif
                </div>
                <div class="announce-compact-body">
                    <span class="announce-tag">{{ $announcement->category ?? 'إعلان' }}</span>
                    <h4 class="announce-compact-title">{{ $announcement->title }}</h4>
                    <div class="announce-meta" style="margin-bottom: 0;">
                        <span>{{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        @endif
    @empty
    <div class="card" style="text-align: center; color: var(--text-secondary);">
        لا توجد إعلانات حالياً.
    </div>
    @endforelse

@endsection
