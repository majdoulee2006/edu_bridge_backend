@extends('layouts.affairs')
@section('title', 'الإعدادات')

@push('styles')
<style>
    .settings-page { max-width: 800px; margin: 0 auto; padding: 0 1rem 3rem; }
    .settings-card {
        background: var(--card);
        border-radius: 1.5rem;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
    }
    .settings-card h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--border);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .settings-card h3 i { color: var(--primary); }
    .form-group { margin-bottom: 1.25rem; }
    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
    }
    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1.5px solid var(--border);
        border-radius: 0.75rem;
        background: var(--bg);
        color: var(--text);
        font-size: 0.9rem;
        transition: border-color 0.2s;
        outline: none;
    }
    .form-control:focus { border-color: var(--primary); }
    .btn-save {
        background: var(--primary);
        color: #000;
        border: none;
        border-radius: 0.75rem;
        padding: 0.75rem 2rem;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .btn-save:hover { opacity: 0.85; }
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border);
        font-size: 0.9rem;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: var(--text-muted); }
    .info-value { font-weight: 600; color: var(--text); }
    .badge-role {
        background: rgba(252,227,0,0.15);
        color: #b8a000;
        border-radius: 0.5rem;
        padding: 0.2rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="settings-page">

    @if(session('success'))
        <div style="background:rgba(34,197,94,0.1);border:1px solid #22c55e;color:#16a34a;border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:1rem;font-weight:600;">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    {{-- معلومات الحساب --}}
    <div class="settings-card">
        <h3><i class="fas fa-user-circle"></i> معلومات الحساب</h3>
        <div class="info-row">
            <span class="info-label">الاسم الكامل</span>
            <span class="info-value">{{ Auth::user()->full_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">البريد الإلكتروني</span>
            <span class="info-value">{{ Auth::user()->email }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">اسم المستخدم</span>
            <span class="info-value">{{ Auth::user()->username }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الدور الوظيفي</span>
            <span class="info-value"><span class="badge-role">موظف الشؤون</span></span>
        </div>
        <div class="info-row">
            <span class="info-label">حالة الحساب</span>
            <span class="info-value" style="color:#22c55e;"><i class="fas fa-circle" style="font-size:0.6rem;margin-left:0.3rem;"></i> نشط</span>
        </div>
    </div>

    {{-- تغيير كلمة المرور --}}
    <div class="settings-card">
        <h3><i class="fas fa-lock"></i> تغيير كلمة المرور</h3>
        <form method="POST" action="{{ route('affairs.settings.password') }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>كلمة المرور الحالية</label>
                <input type="password" name="current_password" class="form-control" placeholder="أدخل كلمة المرور الحالية" required>
                @error('current_password') <p style="color:#ef4444;font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label>كلمة المرور الجديدة</label>
                <input type="password" name="password" class="form-control" placeholder="أدخل كلمة المرور الجديدة (6 أحرف على الأقل)" required minlength="6">
                @error('password') <p style="color:#ef4444;font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" class="form-control" placeholder="أعد إدخال كلمة المرور الجديدة" required>
            </div>
            <button type="submit" class="btn-save"><i class="fas fa-save me-1"></i> حفظ التغييرات</button>
        </form>
    </div>

    {{-- إعدادات النظام --}}
    <div class="settings-card">
        <h3><i class="fas fa-cog"></i> إعدادات النظام</h3>
        <div class="info-row">
            <span class="info-label">إصدار النظام</span>
            <span class="info-value">EduBridge v1.0</span>
        </div>
        <div class="info-row">
            <span class="info-label">اللغة</span>
            <span class="info-value">العربية</span>
        </div>
        <div class="info-row">
            <span class="info-label">المنطقة الزمنية</span>
            <span class="info-value">{{ config('app.timezone') }}</span>
        </div>
    </div>

</div>
@endsection
