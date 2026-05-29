@extends('layouts.affairs')
@section('title', 'طلبات الحسابات')

@push('styles')
<style>
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; }
.page-header h2 { font-size:1.8rem; font-weight:800; color:var(--text-primary); }
.badge-count { background:var(--accent-color); color:var(--primary-dark); padding:0.2rem 0.8rem; border-radius:2rem; font-size:0.85rem; font-weight:800; margin-right:0.5rem; }
.card-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1.5rem; }
.req-card { background:var(--surface); border-radius:1.25rem; padding:1.5rem; border:1.5px solid var(--border); display:flex; flex-direction:column; gap:1rem; }
.req-card-header { display:flex; align-items:center; gap:1rem; }
.req-avatar { width:48px; height:48px; border-radius:50%; background:var(--accent-color); display:flex; align-items:center; justify-content:center; font-size:1.3rem; color:var(--primary-dark); font-weight:800; flex-shrink:0; }
.req-name { font-weight:800; font-size:1rem; color:var(--text-primary); }
.req-role { font-size:0.8rem; color:var(--text-secondary); margin-top:0.2rem; }
.req-info { display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; }
.req-info-item { background:var(--surface-2); border-radius:0.75rem; padding:0.6rem 0.8rem; }
.req-info-label { font-size:0.72rem; color:var(--text-secondary); font-weight:700; display:block; }
.req-info-value { font-size:0.85rem; color:var(--text-primary); font-weight:600; word-break:break-all; }
.req-actions { display:flex; gap:0.75rem; }
.btn-approve { flex:1; padding:0.7rem; background:#10b981; color:white; border:none; border-radius:0.75rem; font-weight:800; cursor:pointer; font-size:0.9rem; }
.btn-approve:hover { background:#059669; }
.btn-reject { flex:1; padding:0.7rem; background:#fee2e2; color:#dc2626; border:none; border-radius:0.75rem; font-weight:800; cursor:pointer; font-size:0.9rem; }
.btn-reject:hover { background:#fecaca; }
.empty-state { text-align:center; padding:4rem 2rem; color:var(--text-secondary); }
.empty-state i { font-size:3rem; opacity:0.3; margin-bottom:1rem; display:block; }
</style>
@endpush

@section('content')
<div style="max-width:1100px; margin:2rem auto; padding:0 1rem;">
    <div class="page-header">
        <h2>
            <i class="fa-solid fa-clock" style="color:var(--accent-color); margin-left:0.5rem;"></i>
            طلبات الحسابات المعلّقة
            @if($pending->count() > 0)
                <span class="badge-count">{{ $pending->count() }}</span>
            @endif
        </h2>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5; color:#065f46; padding:1rem 1.5rem; border-radius:1rem; margin-bottom:1.5rem; font-weight:700;">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif

    @if($pending->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p style="font-size:1.1rem; font-weight:700;">لا توجد طلبات معلّقة</p>
            <p style="font-size:0.9rem; margin-top:0.5rem;">جميع الطلبات تمت مراجعتها</p>
        </div>
    @else
    <div class="card-grid">
        @foreach($pending as $user)
        <div class="req-card">
            <div class="req-card-header">
                <div class="req-avatar">{{ mb_substr($user->full_name, 0, 1) }}</div>
                <div>
                    <div class="req-name">{{ $user->full_name }}</div>
                    <div class="req-role">
                        {{ $user->role_id == 3 ? '🎓 طالب' : '👨‍👩‍👧 ولي أمر' }}
                    </div>
                </div>
            </div>

            <div class="req-info">
                <div class="req-info-item" style="grid-column:1/-1;">
                    <span class="req-info-label">البريد الإلكتروني</span>
                    <span class="req-info-value">{{ $user->email }}</span>
                </div>
                @if($user->university_id)
                <div class="req-info-item">
                    <span class="req-info-label">الرقم الجامعي</span>
                    <span class="req-info-value">{{ $user->university_id }}</span>
                </div>
                @endif
                <div class="req-info-item">
                    <span class="req-info-label">تاريخ الطلب</span>
                    <span class="req-info-value">{{ $user->created_at->format('Y-m-d') }}</span>
                </div>
            </div>

            <div class="req-actions">
                <form method="POST" action="{{ route('affairs.pending_accounts.approve', $user->user_id) }}">
                    @csrf
                    <button type="submit" class="btn-approve">
                        <i class="fa-solid fa-check"></i> موافقة
                    </button>
                </form>
                <form method="POST" action="{{ route('affairs.pending_accounts.reject', $user->user_id) }}"
                      onsubmit="return confirm('رفض وحذف هذا الطلب؟')">
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
