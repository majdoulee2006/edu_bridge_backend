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
    
    /* ── Announcement Cards ── */
    .ann-hero { border-radius: 1.5rem; overflow: hidden; background: var(--bg-secondary); box-shadow: var(--shadow); margin-bottom: 1rem; display: flex; flex-direction: column; }
    .ann-hero-img { position: relative; width: 100%; height: 240px; overflow: hidden; background: #121212; flex-shrink: 0; }
    .ann-hero-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .5s ease; }
    .ann-hero:hover .ann-hero-img img { transform: scale(1.04); }
    .ann-hero-img-grad { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.55) 0%, transparent 55%); pointer-events: none; }
    .ann-hero-img-badge { position: absolute; top: 0.85rem; right: 0.85rem; background: var(--accent-color); color: #1a1a1a; font-size: 0.72rem; font-weight: 800; padding: 0.25rem 0.75rem; border-radius: 2rem; }
    .ann-hero-body { padding: 1.25rem 1.5rem 1.5rem; }
    .ann-hero-title { font-size: 1.05rem; font-weight: 800; margin-bottom: 0.4rem; line-height: 1.45; color: var(--text-primary); }
    .ann-hero-excerpt { font-size: 0.85rem; color: var(--text-secondary); line-height: 1.65; margin-bottom: 0.75rem; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }
    .ann-hero-footer { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; }

    .ann-row { display: flex; align-items: stretch; border-radius: 1.25rem; overflow: hidden; background: var(--bg-secondary); box-shadow: var(--shadow); margin-bottom: 0.65rem; transition: box-shadow .2s; }
    .ann-row:hover { box-shadow: 0 4px 20px rgba(0,0,0,.12); }
    .ann-row-thumb { flex-shrink: 0; width: 120px; position: relative; overflow: hidden; background: #121212; }
    .ann-row-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; position: absolute; inset: 0; transition: transform .4s ease; }
    .ann-row:hover .ann-row-thumb img { transform: scale(1.07); }
    .ann-row-body { flex: 1; padding: 0.85rem 1.1rem; display: flex; flex-direction: column; justify-content: center; min-width: 0; }
    .ann-row-title { font-size: 0.88rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.3rem; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-height: 1.4; }
    .ann-row-meta { font-size: 0.74rem; color: var(--text-secondary); display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }

    .ann-no-img { background: linear-gradient(135deg, #121212 0%, #000000 100%); display: flex; align-items: center; justify-content: center; }
    .ann-actions { display: flex; gap: 0.4rem; flex-shrink: 0; }
    .btn-edit-sm  { padding: 0.3rem 0.65rem; border-radius: 0.5rem; background: #eff6ff; color: #1d4ed8; font-size: 0.72rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; }
    .btn-del-sm   { padding: 0.3rem 0.65rem; border-radius: 0.5rem; background: #fef2f2; color: #dc2626; font-size: 0.72rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 0.25rem; }
</style>
@endpush

@section('content')

    <div class="welcome-header">
        <div class="welcome-text">
            <h2>Edu-Bridge</h2>
            <p>مرحباً، رئيس القسم{{ auth()->user()->department ? ' ' . auth()->user()->department : '' }}</p>
        </div>
    </div>

    {{-- ===== Announcements Header ===== --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <span style="width:4px; height:24px; background:var(--accent-color); border-radius:2px; display:inline-block;"></span>
            <h3 style="font-size:1.1rem; font-weight:800;">آخر الأخبار والإعلانات</h3>
        </div>
        <a href="{{ route('hod.announcements.create') }}"
           style="display:flex; align-items:center; gap:0.4rem; background:var(--accent-color); color:#1a1a1a; border-radius:2rem; padding:0.45rem 1rem; font-weight:700; font-size:0.82rem; text-decoration:none;">
            <i class="fa-solid fa-plus"></i> إضافة إعلان
        </a>
    </div>

    @forelse($announcements as $ann)
        @php
            $colors = ['#111827','#1d4ed8','#065f46','#7c3aed','#be123c','#b45309'];
            $color  = $colors[$loop->index % count($colors)];
            $initials = mb_substr($ann->user->full_name ?? 'إ', 0, 1);

            $imgsArr = [];
            if (!empty($ann->images)) {
                $imgsArr = is_string($ann->images) ? json_decode($ann->images, true) : $ann->images;
            }
            if (empty($imgsArr) && !empty($ann->image)) {
                $imgsArr = [$ann->image];
            }

            $formattedImgs = [];
            if (is_array($imgsArr)) {
                foreach ($imgsArr as $img) {
                    if ($img) {
                        $formattedImgs[] = str_starts_with($img, 'http') ? $img : asset('storage/' . ltrim($img, '/'));
                    }
                }
            }

            $isOwner = isset($ann->user_id) && $ann->user_id == auth()->id();
            $annId   = $ann->announcement_id ?? $ann->id;
        @endphp

        <div style="background: var(--bg-secondary); border: 1px solid rgba(128,128,128,0.2); border-radius: 1rem; margin-bottom: 1.5rem; padding: 1.25rem; display: flex; gap: 0.75rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <!-- Avatar column -->
            <div style="flex-shrink: 0;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background-color: {{ $color }}; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; font-size: 1.1rem;">
                    {{ $initials }}
                </div>
            </div>
            
            <!-- Content column -->
            <div style="flex: 1; min-width: 0;">
                <!-- Header -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                        <span style="font-weight: 700; color: var(--text-dark); font-size: 0.95rem;">{{ $ann->user->full_name ?? 'رئيس القسم' }}</span>
                        @if(isset($ann->user) && in_array($ann->user->role, ['admin', 'hod', 'affairs']))
                            <i class="fa-solid fa-circle-check" style="color: #1d9bf0; font-size: 0.85rem;"></i>
                        @endif
                        <span style="color: var(--text-muted); font-size: 0.9rem;" dir="ltr">@admin · {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans(null, true) }}</span>
                    </div>

                    @if($isOwner)
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <a href="{{ route('hod.announcements.edit', $annId) }}" style="color: var(--text-muted);"><i class="fa-solid fa-pen"></i></a>
                        <form action="{{ route('hod.announcements.delete', $annId) }}" method="POST" onsubmit="return confirm('حذف؟')" style="margin:0; display: flex; align-items: center;">
                            @csrf
                            <button type="submit" style="background:none; border:none; color: #ef4444; cursor:pointer; padding: 0;"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                    @endif
                </div>
            
                <!-- Text Content -->
                <div style="color: var(--text-dark); font-size: 0.95rem; line-height: 1.6; margin-bottom: 0.75rem; white-space: pre-line;">
                    @if($ann->title)
                    <strong style="display: block; margin-bottom: 0.25rem; font-size: 1.05rem;">{{ $ann->title }}</strong>
                    @endif
                    {{ $ann->content }}
                </div>

                <!-- Image Attachment -->
                @include('partials.image_slider', ['images' => $formattedImgs])
                
                @if(isset($ann->link_url) && $ann->link_url)
                    <div style="margin-top: 0.75rem;">
                        <a href="{{ $ann->link_url }}" target="_blank" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #1d9bf0; text-decoration: none; font-size: 0.9rem; font-weight: 700;">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> فتح الرابط
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @empty
    <div style="text-align:center; padding:2rem; background:var(--bg-secondary); border-radius:1.25rem; color:var(--text-secondary);">
        <i class="fa-solid fa-bullhorn" style="font-size:2rem; opacity:0.3; margin-bottom:0.5rem; display:block;"></i>
        لا توجد إعلانات حالياً
    </div>
    @endforelse

@endsection

