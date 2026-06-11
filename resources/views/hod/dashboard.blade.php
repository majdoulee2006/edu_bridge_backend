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
        <div style="display:flex; flex-direction:row-reverse; border-radius:1.25rem; overflow:hidden; background:var(--bg-secondary); box-shadow:var(--shadow); margin-bottom:1.25rem; min-height:200px;">
            <div style="width:38%; flex-shrink:0; background:#1e293b; position:relative; overflow:hidden;">
                @if($imgUrl)
                    <a href="{{ $imgUrl }}" target="_blank" download style="display:block; position:absolute; inset:0;">
                        <img src="{{ $imgUrl }}" style="width:100%; height:100%; object-fit:cover;">
                    </a>
                @else
                    <i class="fa-solid fa-bullhorn" style="position:absolute; inset:0; margin:auto; font-size:4rem; color:rgba(255,255,255,0.08); width:fit-content; height:fit-content;"></i>
                @endif
            </div>
            <div style="flex:1; padding:1.5rem; display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem; margin-bottom:0.75rem;">
                        <span style="background:var(--accent-color); color:#1a1a1a; padding:0.2rem 0.75rem; border-radius:2rem; font-size:0.78rem; font-weight:700;">إعلان هام</span>
                        @if($isOwner)
                        <div style="display:flex; gap:0.5rem;">
                            <a href="{{ route('hod.announcements.edit', $annId) }}"
                               style="display:flex; align-items:center; gap:0.25rem; padding:0.3rem 0.6rem; border-radius:0.5rem; background:#eff6ff; color:#1d4ed8; font-size:0.75rem; font-weight:700; text-decoration:none;">
                                <i class="fa-solid fa-pen" style="font-size:0.7rem;"></i> تعديل
                            </a>
                            <form action="{{ route('hod.announcements.delete', $annId) }}" method="POST" onsubmit="return confirm('حذف الإعلان؟')" style="margin:0;">
                                @csrf
                                <button type="submit" style="display:flex; align-items:center; gap:0.25rem; padding:0.3rem 0.6rem; border-radius:0.5rem; background:#fef2f2; color:#dc2626; font-size:0.75rem; font-weight:700; border:none; cursor:pointer; font-family:inherit;">
                                    <i class="fa-solid fa-trash" style="font-size:0.7rem;"></i> حذف
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                    <h4 style="font-size:1.05rem; font-weight:800; margin-bottom:0.5rem; color:var(--text-primary);">{{ $ann->title }}</h4>
                    <p style="color:var(--text-secondary); font-size:0.85rem; line-height:1.6;">{{ Str::limit($ann->content, 200) }}</p>
                    @if(isset($ann->link_url) && $ann->link_url)
                        <a href="{{ $ann->link_url }}" target="_blank" style="display:inline-flex; align-items:center; gap:0.3rem; margin-top:0.5rem; color:var(--accent-color); font-size:0.82rem; font-weight:700; text-decoration:none;">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> فتح الرابط
                        </a>
                    @endif
                </div>
                <div style="margin-top:0.75rem; font-size:0.78rem; color:var(--text-secondary);">
                    <i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}
                </div>
            </div>
        </div>
        @else
        <div style="display:flex; flex-direction:row-reverse; border-radius:1.25rem; overflow:hidden; background:var(--bg-secondary); box-shadow:var(--shadow); margin-bottom:0.75rem; min-height:110px;">
            <div style="width:150px; flex-shrink:0; background:#1e293b; position:relative; overflow:hidden;">
                @if($imgUrl)
                    <a href="{{ $imgUrl }}" target="_blank" download style="display:block; position:absolute; inset:0;">
                        <img src="{{ $imgUrl }}" style="width:100%; height:100%; object-fit:cover;">
                    </a>
                @else
                    <i class="fa-solid fa-bullhorn" style="position:absolute; inset:0; margin:auto; font-size:2rem; color:rgba(255,255,255,0.1); width:fit-content; height:fit-content;"></i>
                @endif
            </div>
            <div style="flex:1; padding:1rem 1.25rem; display:flex; flex-direction:column; justify-content:center;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.4rem;">
                    <span style="font-size:0.72rem; font-weight:700; color:var(--text-secondary);">إعلان</span>
                    @if($isOwner)
                    <div style="display:flex; gap:0.4rem;">
                        <a href="{{ route('hod.announcements.edit', $annId) }}"
                           style="padding:0.25rem 0.5rem; border-radius:0.4rem; background:#eff6ff; color:#1d4ed8; font-size:0.7rem; text-decoration:none;">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <form action="{{ route('hod.announcements.delete', $annId) }}" method="POST" onsubmit="return confirm('حذف؟')" style="margin:0;">
                            @csrf
                            <button type="submit" style="padding:0.25rem 0.5rem; border-radius:0.4rem; background:#fef2f2; color:#dc2626; font-size:0.7rem; border:none; cursor:pointer;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                <h4 style="font-size:0.9rem; font-weight:700; color:var(--text-primary); margin-bottom:0.3rem;">{{ $ann->title }}</h4>
                <p style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.3rem; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">{{ $ann->content }}</p>
                <span style="font-size:0.75rem; color:var(--text-secondary);">{{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}</span>
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

