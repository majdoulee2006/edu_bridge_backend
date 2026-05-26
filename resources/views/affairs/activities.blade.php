@extends('layouts.affairs')
@section('title', 'الأنشطة')

@push('styles')
<style>
    .activities-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    /* Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    .page-header h2 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    .page-header .header-note {
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .page-header .header-note i {
        color: var(--accent-color);
    }

    /* Filters */
    .filters {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }
    .filter-chip {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        border: 2px solid transparent;
        padding: 0.6rem 1.2rem;
        border-radius: 2rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .filter-chip.active, .filter-chip:hover {
        background: var(--bg-primary);
        border-color: var(--accent-color);
        color: var(--text-primary);
    }

    /* Activities Grid */
    .activities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
    }

    /* Activity Card */
    .activity-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: transform 0.3s;
        display: flex;
        flex-direction: column;
    }
    .activity-card:hover {
        transform: translateY(-5px);
    }

    .card-cover {
        height: 140px;
        background: linear-gradient(135deg, rgba(252, 227, 0, 0.2), rgba(252, 227, 0, 0.05));
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card-cover i {
        font-size: 3rem;
        color: var(--accent-color);
        opacity: 0.5;
    }
    .status-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.3rem 0.8rem;
        border-radius: 1rem;
        font-size: 0.8rem;
        font-weight: 800;
    }
    .badge-upcoming  { background: var(--accent-color); color: var(--primary-dark); }
    .badge-ongoing   { background: #10b981; color: white; }
    .badge-completed { background: #6b7280; color: white; }

    .card-body {
        padding: 1.5rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .activity-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 1rem;
    }

    .info-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.6rem;
        color: var(--text-secondary);
        font-size: 0.95rem;
        font-weight: 600;
    }
    .info-row i {
        color: var(--accent-color);
        width: 20px;
        text-align: center;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-secondary);
        grid-column: 1 / -1;
    }
    .empty-state i {
        font-size: 4rem;
        color: var(--accent-color);
        opacity: 0.4;
        margin-bottom: 1rem;
    }
    .empty-state h3 {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }
    .empty-state p { font-size: 0.95rem; margin: 0; }
    .empty-state a {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
        padding: 0.8rem 1.8rem;
        background: var(--accent-color);
        color: var(--primary-dark);
        border-radius: 0.8rem;
        font-weight: 800;
        text-decoration: none;
        transition: transform 0.2s;
    }
    .empty-state a:hover { transform: translateY(-2px); }
</style>
@endpush

@section('content')
<div class="activities-container">

    <!-- Header -->
    <div class="page-header">
        <h2>الأنشطة والفعاليات</h2>
        <div class="header-note">
            <i class="fa-solid fa-circle-info"></i>
            تُعرض الأنشطة من أحداث التقويم
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <button class="filter-chip active" data-filter="all">الكل</button>
        <button class="filter-chip" data-filter="upcoming">الأنشطة القادمة</button>
        <button class="filter-chip" data-filter="ongoing">قيد التنفيذ</button>
        <button class="filter-chip" data-filter="completed">الأنشطة المنتهية</button>
    </div>

    <!-- Grid -->
    <div class="activities-grid" id="activitiesGrid">

        @forelse($events as $event)
            @php
                $today     = \Carbon\Carbon::today();
                $eventDate = \Carbon\Carbon::parse($event->event_date);

                if ($eventDate->isToday()) {
                    $status      = 'ongoing';
                    $badgeClass  = 'badge-ongoing';
                    $badgeLabel  = 'قيد التنفيذ';
                    $icon        = 'fa-solid fa-champagne-glasses';
                } elseif ($eventDate->isFuture()) {
                    $status      = 'upcoming';
                    $badgeClass  = 'badge-upcoming';
                    $badgeLabel  = 'قادم';
                    $icon        = 'fa-solid fa-calendar-check';
                } else {
                    $status      = 'completed';
                    $badgeClass  = 'badge-completed';
                    $badgeLabel  = 'منتهي';
                    $icon        = 'fa-solid fa-flag-checkered';
                }

                // تنسيق الوقت
                $time = $event->event_time
                    ? \Carbon\Carbon::createFromTimeString($event->event_time)->format('h:i A')
                    : null;
            @endphp

            <div class="activity-card" data-status="{{ $status }}">
                <div class="card-cover">
                    <i class="{{ $icon }}"></i>
                    <span class="status-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                </div>
                <div class="card-body">
                    <h3 class="activity-title">{{ $event->title }}</h3>

                    <div class="info-row">
                        <i class="fa-solid fa-calendar-day"></i>
                        <span>{{ $eventDate->translatedFormat('d F Y') }}</span>
                    </div>

                    @if($time)
                    <div class="info-row">
                        <i class="fa-solid fa-clock"></i>
                        <span>{{ $time }}</span>
                    </div>
                    @endif

                    <div class="info-row">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>{{ $event->location }}</span>
                    </div>
                </div>
            </div>

        @empty
            <div class="empty-state">
                <i class="fa-regular fa-calendar-xmark"></i>
                <h3>لا توجد أنشطة حتى الآن</h3>
                <p>أضف حدثاً من صفحة التقويم ليظهر هنا</p>
                <a href="{{ route('affairs.calendar') }}">
                    <i class="fa-solid fa-calendar-plus"></i>
                    الذهاب إلى التقويم
                </a>
            </div>
        @endforelse

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Filtering Logic
    const filterChips = document.querySelectorAll('.filter-chip');
    const cards       = document.querySelectorAll('.activity-card');

    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            filterChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');

            const filter = chip.getAttribute('data-filter');
            cards.forEach(card => {
                card.style.display =
                    (filter === 'all' || card.getAttribute('data-status') === filter)
                        ? 'flex' : 'none';
            });
        });
    });
</script>
@endpush
