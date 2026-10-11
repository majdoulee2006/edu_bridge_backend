@extends('layouts.affairs')
@section('title', __('messages.activities_and_events'))

@push('styles')
<style>
    .activities-container {
        max-width: 1200px;
        margin: 1.5rem auto;
        padding: 0 1rem;
    }

    /* Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .page-header h2 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0;
    }
    .page-header .header-note {
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--bg-secondary);
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        border: 1px solid var(--border-color);
    }
    .page-header .header-note i {
        color: var(--accent-color);
    }

    /* Search & Controls */
    .controls-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .search-box {
        position: relative;
        flex: 1;
        min-width: 260px;
        max-width: 400px;
    }
    .search-box input {
        width: 100%;
        padding: {{ app()->getLocale() === 'en' ? '0.65rem 1rem 0.65rem 2.5rem' : '0.65rem 2.5rem 0.65rem 1rem' }};
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 2rem;
        color: var(--text-primary);
        font-family: inherit;
        font-size: 0.9rem;
        font-weight: 600;
        outline: none;
        transition: all 0.2s;
    }
    .search-box input:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 3px rgba(252, 227, 0, 0.15);
    }
    .search-box i {
        position: absolute;
        {{ app()->getLocale() === 'en' ? 'left: 1rem;' : 'right: 1rem;' }}
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
    }

    /* Filters */
    .filters {
        display: flex;
        gap: 0.75rem;
        overflow-x: auto;
        padding-bottom: 0.25rem;
        scrollbar-width: none;
    }
    .filter-chip {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        border: 1.5px solid var(--border-color);
        padding: 0.55rem 1.2rem;
        border-radius: 2rem;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .filter-chip .count-badge {
        background: rgba(0,0,0,0.1);
        padding: 0.1rem 0.55rem;
        border-radius: 1rem;
        font-size: 0.75rem;
    }
    .filter-chip.active, .filter-chip:hover {
        background: var(--accent-color);
        border-color: var(--accent-color);
        color: #1a1a1a;
    }
    .filter-chip.active .count-badge {
        background: rgba(0,0,0,0.2);
        color: #1a1a1a;
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
        transition: transform 0.25s, opacity 0.25s;
        display: flex;
        flex-direction: column;
        border: 1px solid var(--border-color);
    }
    .activity-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .card-cover {
        height: 130px;
        background: linear-gradient(135deg, rgba(252, 227, 0, 0.2), rgba(252, 227, 0, 0.05));
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card-cover i {
        font-size: 3rem;
        color: var(--accent-color);
        opacity: 0.6;
    }
    .status-badge {
        position: absolute;
        top: 0.85rem;
        {{ app()->getLocale() === 'en' ? 'left: 0.85rem;' : 'right: 0.85rem;' }}
        padding: 0.35rem 0.85rem;
        border-radius: 1rem;
        font-size: 0.78rem;
        font-weight: 800;
    }
    .badge-upcoming  { background: var(--accent-color); color: #1a1a1a; }
    .badge-ongoing   { background: #10b981; color: white; }
    .badge-completed { background: #6b7280; color: white; }

    .card-body {
        padding: 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        text-align: {{ app()->getLocale() === 'en' ? 'left' : 'right' }};
    }
    .activity-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.85rem;
        line-height: 1.4;
    }

    .info-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 0.5rem;
        color: var(--text-secondary);
        font-size: 0.88rem;
        font-weight: 600;
    }
    .info-row i {
        color: var(--accent-color);
        width: 18px;
        text-align: center;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-secondary);
        grid-column: 1 / -1;
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        border: 1px dashed var(--border-color);
    }
    .empty-state i {
        font-size: 3.5rem;
        color: var(--accent-color);
        opacity: 0.5;
        margin-bottom: 1rem;
    }
    .empty-state h3 {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.4rem;
    }
    .empty-state p { font-size: 0.9rem; margin: 0; }
    .empty-state a {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1.25rem;
        padding: 0.7rem 1.5rem;
        background: var(--accent-color);
        color: #1a1a1a;
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
        <h2>{{ __('messages.activities_and_events') }}</h2>
        <div class="header-note">
            <i class="fa-solid fa-circle-info"></i>
            {{ __('messages.activities_note') }}
        </div>
    </div>

    <!-- Advanced Filter Bar -->
    <div style="background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.5rem; border: 1px solid var(--border-color); margin-bottom: 2rem; box-shadow: var(--shadow);">
        <form method="GET" action="{{ route('affairs.activities') }}" id="filterForm">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; align-items: end;">
                
                {{-- اختيار القسم --}}
                <div>
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                        <i class="fa-solid fa-building-user" style="color: var(--accent-color);"></i> {{ __('messages.filter_by_dept') }}
                    </label>
                    <select name="department_id" style="width: 100%; padding: 0.7rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                        <option value="">{{ __('messages.all_departments') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}" {{ $deptId == $dept->department_id ? 'selected' : '' }}>
                                {{ $dept->display_name ?? $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- نوع التصفية حسب التاريخ --}}
                <div>
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                        <i class="fa-solid fa-calendar-days" style="color: var(--accent-color);"></i> {{ __('messages.time_filter_type') }}
                    </label>
                    <select name="date_mode" id="date_mode_select" onchange="toggleDateInputs(this.value)" style="width: 100%; padding: 0.7rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                        <option value="all" {{ $dateMode === 'all' ? 'selected' : '' }}>{{ __('messages.all') }}</option>
                        <option value="single_date" {{ $dateMode === 'single_date' ? 'selected' : '' }}>{{ __('messages.single_day') }}</option>
                        <option value="date_range" {{ $dateMode === 'date_range' ? 'selected' : '' }}>{{ __('messages.date_range') }}</option>
                        <option value="week" {{ $dateMode === 'week' ? 'selected' : '' }}>{{ __('messages.specific_week') }}</option>
                    </select>
                </div>

                {{-- مدخل يوم معين --}}
                <div id="single_date_container" style="display: {{ $dateMode === 'single_date' ? 'block' : 'none' }};">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">{{ __('messages.selected_date') }}</label>
                    <input type="date" name="single_date" value="{{ $singleDate }}" style="width: 100%; padding: 0.7rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;" />
                </div>

                {{-- مدخل بين تاريخين --}}
                <div id="date_range_start_container" style="display: {{ $dateMode === 'date_range' ? 'block' : 'none' }};">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">{{ __('messages.from_date') }}</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" style="width: 100%; padding: 0.7rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;" />
                </div>
                <div id="date_range_end_container" style="display: {{ $dateMode === 'date_range' ? 'block' : 'none' }};">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">{{ __('messages.to_date') }}</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" style="width: 100%; padding: 0.7rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;" />
                </div>

                {{-- مدخل أسبوع معين --}}
                <div id="week_container" style="display: {{ $dateMode === 'week' ? 'block' : 'none' }};">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">{{ __('messages.select_week_day') }}</label>
                    <input type="date" name="week_date" value="{{ $weekDate }}" style="width: 100%; padding: 0.7rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;" />
                </div>

                {{-- أزرار التحكم --}}
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" style="flex: 1; padding: 0.7rem 1.25rem; border: none; border-radius: 0.75rem; background: var(--accent-color); color: #101924; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                        <i class="fa-solid fa-filter"></i> {{ __('messages.apply_filter') }}
                    </button>
                    @if($deptId || $dateMode !== 'all')
                        <a href="{{ route('affairs.activities') }}" style="padding: 0.7rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-secondary); font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center;" title="{{ __('messages.reset_filters') }}">
                            <i class="fa-solid fa-rotate-right"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Thin Divider Line -->
    <div style="height: 1px; background: var(--border-color); opacity: 0.6; margin: 2rem 0 2.25rem 0;"></div>

    <!-- Controls: Search & Filters -->
    <div class="controls-row">
        <div class="filters">
            <button class="filter-chip active" data-filter="all">
                {{ __('messages.all') }} <span class="count-badge" id="count-all">0</span>
            </button>
            <button class="filter-chip" data-filter="ongoing">
                {{ __('messages.ongoing_activities') }} <span class="count-badge" id="count-ongoing">0</span>
            </button>
            <button class="filter-chip" data-filter="upcoming">
                {{ __('messages.upcoming_activities') }} <span class="count-badge" id="count-upcoming">0</span>
            </button>
            <button class="filter-chip" data-filter="completed">
                {{ __('messages.completed_activities') }} <span class="count-badge" id="count-completed">0</span>
            </button>
        </div>

        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="{{ __('messages.search_activity_placeholder') }}" oninput="applyFilters()">
        </div>
    </div>

    <!-- Grid -->
    <div class="activities-grid" id="activitiesGrid">

        @forelse($events ?? [] as $event)
            @php
                $today     = \Carbon\Carbon::today();
                $eventDate = \Carbon\Carbon::parse($event->event_date)->startOfDay();

                if ($eventDate->equalTo($today)) {
                    $status      = 'ongoing';
                    $badgeClass  = 'badge-ongoing';
                    $badgeLabel  = __('messages.status_ongoing');
                    $icon        = 'fa-solid fa-champagne-glasses';
                } elseif ($eventDate->greaterThan($today)) {
                    $status      = 'upcoming';
                    $badgeClass  = 'badge-upcoming';
                    $badgeLabel  = __('messages.status_upcoming');
                    $icon        = 'fa-solid fa-calendar-check';
                } else {
                    $status      = 'completed';
                    $badgeClass  = 'badge-completed';
                    $badgeLabel  = __('messages.status_completed_event');
                    $icon        = 'fa-solid fa-flag-checkered';
                }

                $time = $event->event_time
                    ? \Carbon\Carbon::createFromTimeString($event->event_time)->format('h:i A')
                    : null;
            @endphp

            <div class="activity-card" data-status="{{ $status }}" data-search="{{ mb_strtolower($event->title . ' ' . $event->location) }}">
                <div class="card-cover">
                    <i class="{{ $icon }}"></i>
                    <span class="status-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                </div>
                <div class="card-body">
                    <h3 class="activity-title">{{ $event->title }}</h3>

                    <div class="info-row">
                        <i class="fa-solid fa-building-user"></i>
                        <span>{{ __('messages.academic_department') }}: {{ $event->department ? ($event->department->display_name ?? $event->department->name) : __('messages.general_all_depts') }}</span>
                    </div>

                    <div class="info-row">
                        <i class="fa-solid fa-calendar-day"></i>
                        <span>{{ $eventDate->locale(app()->getLocale())->translatedFormat('d F Y') }}</span>
                    </div>

                    @if($time)
                    <div class="info-row">
                        <i class="fa-solid fa-clock"></i>
                        <span>{{ $time }}</span>
                    </div>
                    @endif

                    @if(!empty($event->location))
                    <div class="info-row">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>{{ $event->location }}</span>
                    </div>
                    @endif
                </div>
            </div>

        @empty
            <div class="empty-state">
                <i class="fa-regular fa-calendar-xmark"></i>
                <h3>{{ __('messages.no_activities_yet') }}</h3>
                <p>{{ __('messages.add_calendar_events_hint') }}</p>
                <a href="{{ route('affairs.calendar') }}">
                    <i class="fa-solid fa-calendar-plus"></i>
                    {{ __('messages.go_to_calendar') }}
                </a>
            </div>
        @endforelse

        <div id="noResultsState" class="empty-state" style="display: none;">
            <i class="fa-solid fa-filter-circle-xmark"></i>
            <h3>{{ __('messages.no_matching_activities_filter') }}</h3>
            <p>{{ __('messages.try_other_filter') }}</p>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleDateInputs(mode) {
        document.getElementById('single_date_container').style.display = (mode === 'single_date') ? 'block' : 'none';
        document.getElementById('date_range_start_container').style.display = (mode === 'date_range') ? 'block' : 'none';
        document.getElementById('date_range_end_container').style.display = (mode === 'date_range') ? 'block' : 'none';
        document.getElementById('week_container').style.display = (mode === 'week') ? 'block' : 'none';
    }

    let currentFilter = 'all';

    document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            currentFilter = chip.getAttribute('data-filter');
            applyFilters();
        });
    });

    function applyFilters() {
        const query = document.getElementById('searchInput').value.trim().toLowerCase();
        const cards = document.querySelectorAll('.activity-card');
        const noResults = document.getElementById('noResultsState');
        let visibleCount = 0;

        cards.forEach(card => {
            const matchesStatus = (currentFilter === 'all' || card.getAttribute('data-status') === currentFilter);
            const matchesSearch = !query || card.getAttribute('data-search').includes(query);

            if (matchesStatus && matchesSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResults) {
            noResults.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
        }
    }

    function updateCounts() {
        const cards = document.querySelectorAll('.activity-card');
        let counts = { all: cards.length, ongoing: 0, upcoming: 0, completed: 0 };

        cards.forEach(card => {
            const status = card.getAttribute('data-status');
            if (counts[status] !== undefined) counts[status]++;
        });

        document.getElementById('count-all').textContent = counts.all;
        document.getElementById('count-ongoing').textContent = counts.ongoing;
        document.getElementById('count-upcoming').textContent = counts.upcoming;
        document.getElementById('count-completed').textContent = counts.completed;
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateCounts();
        applyFilters();
    });
</script>
@endpush
