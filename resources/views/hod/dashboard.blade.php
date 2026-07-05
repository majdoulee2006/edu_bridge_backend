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
    .ann-hero-img { position: relative; width: 100%; height: 240px; overflow: hidden; background: #1e293b; flex-shrink: 0; }
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
    .ann-row-thumb { flex-shrink: 0; width: 120px; position: relative; overflow: hidden; background: #1e293b; }
    .ann-row-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; position: absolute; inset: 0; transition: transform .4s ease; }
    .ann-row:hover .ann-row-thumb img { transform: scale(1.07); }
    .ann-row-body { flex: 1; padding: 0.85rem 1.1rem; display: flex; flex-direction: column; justify-content: center; min-width: 0; }
    .ann-row-title { font-size: 0.88rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.3rem; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-height: 1.4; }
    .ann-row-meta { font-size: 0.74rem; color: var(--text-secondary); display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }

    .ann-no-img { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); display: flex; align-items: center; justify-content: center; }
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
            $imgUrl  = ($ann->image ?? false) ? asset('storage/' . $ann->image) : null;
            $isOwner = isset($ann->user_id) && $ann->user_id == auth()->id();
            $annId   = $ann->announcement_id ?? $ann->id;
        @endphp

        @if($loop->first)
        {{-- Hero card --}}
        <div class="ann-hero">
            <div class="ann-hero-img">
                @if($imgUrl)
                    <img src="{{ $imgUrl }}" alt="{{ $ann->title }}">
                @else
                    <div class="ann-no-img" style="width:100%; height:100%;">
                        <i class="fa-solid fa-bullhorn" style="font-size:3.5rem; color:rgba(255,255,255,0.07);"></i>
                    </div>
                @endif
                <div class="ann-hero-img-grad"></div>
                <span class="ann-hero-img-badge">إعلان هام</span>
            </div>
            <div class="ann-hero-body">
                <div class="ann-hero-footer" style="margin-bottom:0.6rem;">
                    <span style="font-size:0.75rem; color:var(--text-secondary);"><i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}</span>
                    @if($isOwner)
                    <div class="ann-actions">
                        <a href="{{ route('hod.announcements.edit', $annId) }}" class="btn-edit-sm"><i class="fa-solid fa-pen"></i> تعديل</a>
                        <form action="{{ route('hod.announcements.delete', $annId) }}" method="POST" onsubmit="return confirm('حذف الإعلان؟')" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn-del-sm"><i class="fa-solid fa-trash"></i> حذف</button>
                        </form>
                    </div>
                    @endif
                </div>
                <h4 class="ann-hero-title">{{ $ann->title }}</h4>
                <p class="ann-hero-excerpt">{{ $ann->content }}</p>
                @if(isset($ann->link_url) && $ann->link_url)
                    <a href="{{ $ann->link_url }}" target="_blank" style="display:inline-flex; align-items:center; gap:0.3rem; color:var(--accent-color); font-size:0.82rem; font-weight:700; text-decoration:none;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> فتح الرابط
                    </a>
                @endif
            </div>
        </div>
        @else
        {{-- Row card --}}
        <div class="ann-row">
            <div class="ann-row-thumb">
                @if($imgUrl)
                    <img src="{{ $imgUrl }}" alt="{{ $ann->title }}">
                @else
                    <div class="ann-no-img" style="width:100%; height:100%; position:absolute; inset:0;">
                        <i class="fa-solid fa-bullhorn" style="font-size:1.75rem; color:rgba(255,255,255,0.12);"></i>
                    </div>
                @endif
            </div>
            <div class="ann-row-body">
                <h4 class="ann-row-title">{{ $ann->title }}</h4>
                <div class="ann-row-meta">
                    <span><i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}</span>
                    @if($isOwner)
                    <div class="ann-actions" style="margin-right:auto;">
                        <a href="{{ route('hod.announcements.edit', $annId) }}" class="btn-edit-sm"><i class="fa-solid fa-pen"></i></a>
                        <form action="{{ route('hod.announcements.delete', $annId) }}" method="POST" onsubmit="return confirm('حذف؟')" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn-del-sm"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    @empty
    <div style="text-align:center; padding:2rem; background:var(--bg-secondary); border-radius:1.25rem; color:var(--text-secondary);">
        <i class="fa-solid fa-bullhorn" style="font-size:2rem; opacity:0.3; margin-bottom:0.5rem; display:block;"></i>
        لا توجد إعلانات حالياً
    </div>
    @endforelse

@endsection

