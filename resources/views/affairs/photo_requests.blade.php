@extends('layouts.affairs')
@section('title', 'طلبات تغيير الصورة')

@push('styles')
<style>
.photo-req-container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 0 1rem;
}
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
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.badge-count {
    background: #ffcc00;
    color: #1a1a1a;
    padding: 0.2rem 0.8rem;
    border-radius: 2rem;
    font-size: 0.85rem;
    font-weight: 800;
}

.requests-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 1.5rem;
}

.photo-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 1.25rem;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.photo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    border-color: #ffcc00;
}

.card-header-user {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.user-info-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.user-icon-badge {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(255, 204, 0, 0.15);
    color: #ffcc00;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.user-name {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text-primary);
}
.user-sub {
    font-size: 0.8rem;
    color: var(--text-secondary);
}
.req-date {
    font-size: 0.78rem;
    color: var(--text-secondary);
    font-weight: 700;
    background: var(--bg-primary);
    padding: 0.25rem 0.65rem;
    border-radius: 0.5rem;
}

.comparison-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    background: var(--bg-primary);
    padding: 1rem;
    border-radius: 1rem;
    border: 1px solid var(--border-color);
}

.photo-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}
.photo-label {
    font-size: 0.75rem;
    font-weight: 800;
    color: var(--text-secondary);
}
.photo-frame {
    width: 100%;
    height: 120px;
    border-radius: 0.85rem;
    overflow: hidden;
    background: var(--bg-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--border-color);
}
.photo-frame.new-photo {
    border-color: #ffcc00;
    box-shadow: 0 0 10px rgba(255, 204, 0, 0.2);
}
.photo-frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.photo-placeholder {
    font-size: 2.2rem;
    color: var(--text-secondary);
    opacity: 0.4;
}

.arrow-icon {
    font-size: 1.3rem;
    color: #ffcc00;
    flex-shrink: 0;
}

.card-actions {
    display: flex;
    gap: 0.75rem;
}
.btn-approve {
    flex: 1;
    padding: 0.75rem;
    background: #ffcc00;
    color: #1a1a1a;
    border: none;
    border-radius: 0.75rem;
    font-weight: 800;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    transition: opacity 0.2s;
}
.btn-approve:hover {
    opacity: 0.9;
}
.btn-reject {
    flex: 1;
    padding: 0.75rem;
    background: transparent;
    color: #ef4444;
    border: 2px solid #ef4444;
    border-radius: 0.75rem;
    font-weight: 800;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    transition: all 0.2s;
}
.btn-reject:hover {
    background: #ef4444;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}
.empty-state i {
    font-size: 3.5rem;
    opacity: 0.3;
    margin-bottom: 1rem;
    display: block;
}
</style>
@endpush

@section('content')
<div class="photo-req-container">
    <div class="page-header">
        <h2>
            <i class="fa-solid fa-camera" style="color:#ffcc00;"></i>
            طلبات تغيير الصورة الشخصية
            @if($requests->count() > 0)
                <span class="badge-count">{{ $requests->count() }}</span>
            @endif
        </h2>
    </div>

    @if($requests->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-camera-retro"></i>
            <p style="font-size:1.2rem; font-weight:800; color:var(--text-primary);">لا توجد طلبات تغيير صورة معلقة</p>
            <p style="font-size:0.9rem; margin-top:0.5rem;">جميع طلبات الطلاب لتحديث بصمة الوجه والصورة الشخصية تمت مراجعتها.</p>
        </div>
    @else
        <div class="requests-grid">
            @foreach($requests as $req)
            @php
                $oldUrl = $req->old_photo ? asset('storage/' . $req->old_photo) : null;
                $newUrl = $req->new_photo ? asset('storage/' . $req->new_photo) : null;
            @endphp
            <div class="photo-card">
                <div class="card-header-user">
                    <div class="user-info-group">
                        <div class="user-icon-badge">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <div class="user-name">{{ $req->full_name }}</div>
                            <div class="user-sub">{{ $req->department ?? 'طالب' }}</div>
                        </div>
                    </div>
                    <span class="req-date">
                        {{ \Carbon\Carbon::parse($req->created_at)->format('Y-m-d') }}
                    </span>
                </div>

                <div class="comparison-box">
                    <div class="photo-item">
                        <span class="photo-label">الصورة الحالية</span>
                        <div class="photo-frame">
                            @if($oldUrl)
                                <img src="{{ $oldUrl }}" alt="الصورة الحالية">
                            @else
                                <i class="fa-solid fa-user photo-placeholder"></i>
                            @endif
                        </div>
                    </div>

                    <div class="arrow-icon">
                        <i class="fa-solid fa-arrow-left-long"></i>
                    </div>

                    <div class="photo-item">
                        <span class="photo-label" style="color:#ffcc00;">الصورة الجديدة</span>
                        <div class="photo-frame new-photo">
                            @if($newUrl)
                                <img src="{{ $newUrl }}" alt="الصورة الجديدة">
                            @else
                                <i class="fa-solid fa-user photo-placeholder"></i>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-actions">
                    <form method="POST" action="{{ route('affairs.photo_requests.approve', $req->id) }}" style="flex:1;">
                        @csrf
                        <button type="submit" class="btn-approve">
                            <i class="fa-solid fa-check"></i> موافقة
                        </button>
                    </form>

                    <form method="POST" action="{{ route('affairs.photo_requests.reject', $req->id) }}" style="flex:1;" onsubmit="return confirm('هل أنت متأكد من رفض هذا الطلب؟')">
                        @csrf
                        <button type="submit" class="btn-reject">
                            <i class="fa-solid fa-xmark"></i> رفض
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
