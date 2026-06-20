@extends('layouts.student')
@section('title', 'طلبات الإذن')
@section('subtitle', 'طلبات الغياب المسبق')

@push('styles')
<style>
    .form-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.75rem;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }
    .form-group { margin-bottom: 1.25rem; }
    .form-label { display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.5rem; }
    .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid var(--border-color);
        border-radius: 0.75rem;
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: inherit;
        font-size: 0.95rem;
        transition: border-color 0.2s;
        outline: none;
    }
    .form-control:focus { border-color: var(--accent-color); }
    .btn-submit {
        background: var(--accent-color); color: #1a1a1a;
        border: none; padding: 0.875rem 2rem;
        border-radius: 0.75rem; font-size: 0.95rem; font-weight: 700;
        cursor: pointer; font-family: inherit; transition: transform 0.2s;
    }
    .btn-submit:hover { transform: translateY(-2px); }

    .request-card {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 0.75rem;
        display: flex; align-items: center; gap: 1rem;
        box-shadow: var(--shadow);
        border-right: 4px solid var(--accent-color);
    }
    .badge { padding: 0.2rem 0.65rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; }
    .badge-pending  { background: hsl(30,70%,90%);  color: hsl(30,50%,30%);  }
    .badge-approved { background: hsl(120,70%,90%); color: hsl(120,50%,30%); }
    .badge-rejected { background: hsl(0,70%,90%);   color: hsl(0,50%,30%);   }
</style>
@endpush

@section('content')

{{-- New Request Form --}}
<div class="form-card">
    <p style="font-size: 1.05rem; font-weight: 800; margin-bottom: 1.25rem;">
        <i class="fa-solid fa-envelope-open-text" style="color: var(--accent-color);"></i>
        تقديم طلب إذن جديد
    </p>
    <form action="{{ route('student.leave_requests.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label class="form-label">تاريخ الغياب</label>
            <input type="date" name="date" class="form-control" required min="{{ date('Y-m-d') }}">
        </div>
        <div class="form-group">
            <label class="form-label">سبب الغياب</label>
            <textarea name="reason" class="form-control" rows="3" placeholder="اكتب سبب الغياب..." required></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">مستند مرفق (اختياري)</label>
            <input type="file" name="document" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
        </div>
        <button type="submit" class="btn-submit">
            <i class="fa-solid fa-paper-plane"></i> تقديم الطلب
        </button>
    </form>
</div>

{{-- Previous Requests --}}
<p style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem;">
    <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-color);"></i>
    الطلبات السابقة
</p>

@forelse($requests as $r)
<div class="request-card">
    <div style="flex: 1;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.3rem;">
            @if($r->status === 'approved')
                <span class="badge badge-approved">موافق عليه</span>
            @elseif($r->status === 'rejected')
                <span class="badge badge-rejected">مرفوض</span>
            @else
                <span class="badge badge-pending">قيد المراجعة</span>
            @endif
            <span style="font-weight: 700; font-size: 0.9rem;">
                {{ \Carbon\Carbon::parse($r->date)->format('Y-m-d') }}
            </span>
        </div>
        <div style="color: var(--text-secondary); font-size: 0.82rem;">{{ Str::limit($r->reason, 100) }}</div>
        <div style="color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.3rem;">
            <i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($r->created_at)->diffForHumans() }}
        </div>
    </div>
</div>
@empty
<div style="text-align: center; padding: 2.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
    <i class="fa-solid fa-envelope-open-text" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
    لا توجد طلبات سابقة
</div>
@endforelse

@endsection
