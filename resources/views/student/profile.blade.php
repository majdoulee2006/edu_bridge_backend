@extends('layouts.student')
@section('title', 'الملف الشخصي')
@section('subtitle', 'إدارة حسابك ومعلوماتك الشخصية')

@push('styles')
<style>
    /* Profile Header */
    .profile-header {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
        position: relative;
    }
    .profile-cover {
        height: 140px;
        background: linear-gradient(135deg, var(--accent-color) 0%, hsl(30, 90%, 65%) 100%);
        position: relative;
    }
    .profile-cover::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0; height: 40px;
        background: linear-gradient(to top, var(--bg-secondary) 0%, transparent 100%);
    }
    .profile-info-bar {
        padding: 0 2rem 2rem 2rem;
        display: flex;
        align-items: flex-end;
        gap: 1.5rem;
        margin-top: -50px;
        position: relative;
        z-index: 10;
        flex-wrap: wrap;
    }
    .profile-avatar {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: var(--bg-secondary);
        border: 4px solid var(--bg-secondary);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 900;
        color: var(--accent-color);
        flex-shrink: 0;
    }
    .profile-titles {
        flex: 1;
        padding-bottom: 0.5rem;
    }
    .profile-titles h2 {
        margin: 0 0 0.25rem 0;
        font-weight: 900;
        font-size: 1.5rem;
    }
    .profile-titles .role {
        color: var(--text-secondary);
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .profile-stats {
        display: flex;
        gap: 1.5rem;
        padding-bottom: 0.5rem;
    }
    .stat-box {
        text-align: center;
        background: var(--bg-primary);
        padding: 0.75rem 1.25rem;
        border-radius: 1rem;
        min-width: 100px;
    }
    .stat-value {
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--text-primary);
    }
    .stat-label {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-top: 0.2rem;
    }

    /* Grid Layout */
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    @media (max-width: 768px) {
        .profile-grid { grid-template-columns: 1fr; }
    }

    /* Cards */
    .glass-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 2rem;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .card-title {
        font-size: 1.1rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--text-primary);
    }
    .card-title i {
        background: var(--bg-primary);
        width: 36px; height: 36px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 0.75rem;
        color: var(--accent-color);
    }

    /* Forms */
    .form-group { margin-bottom: 1.25rem; }
    .form-label {
        display: block;
        font-weight: 700;
        font-size: 0.85rem;
        margin-bottom: 0.6rem;
        color: var(--text-secondary);
    }
    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .input-wrapper i {
        position: absolute;
        right: 1.25rem;
        color: var(--text-secondary);
        opacity: 0.7;
        pointer-events: none;
    }
    .form-control {
        width: 100%;
        padding: 0.875rem 3rem 0.875rem 1rem;
        border: 2px solid var(--bg-primary);
        border-radius: 0.85rem;
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.25s ease;
    }
    .form-control:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.1);
    }
    .btn-submit {
        background: var(--text-primary);
        color: var(--bg-secondary);
        border: none;
        padding: 1rem;
        border-radius: 0.85rem;
        font-size: 0.95rem;
        font-weight: 800;
        cursor: pointer;
        font-family: inherit;
        width: 100%;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }
    .btn-submit:hover {
        background: var(--accent-color);
        color: #1a1a1a;
        transform: translateY(-2px);
    }
</style>
@endpush

@section('content')

@if(session('success'))
    <div style="background: hsl(120,70%,90%); color: hsl(120,50%,30%); padding: 1rem 1.25rem; border-radius: 0.85rem; margin-bottom: 1.5rem; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; box-shadow: var(--shadow);">
        <i class="fa-solid fa-circle-check" style="font-size: 1.15rem;"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if($errors->any())
    <div style="background: hsl(0,70%,90%); color: hsl(0,50%,30%); padding: 1rem 1.25rem; border-radius: 0.85rem; margin-bottom: 1.5rem; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; box-shadow: var(--shadow);">
        <i class="fa-solid fa-circle-exclamation" style="font-size: 1.15rem;"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- Profile Header --}}
<div class="profile-header">
    <div class="profile-cover"></div>
    <div class="profile-info-bar">
        <div class="profile-avatar">
            {{ mb_substr($user->full_name ?? 'ط', 0, 1) }}
        </div>
        <div class="profile-titles">
            <h2>{{ $user->full_name }}</h2>
            <div class="role">
                <i class="fa-solid fa-graduation-cap"></i> طالب جامعي
                <span style="opacity: 0.4;">|</span>
                <i class="fa-solid fa-id-card"></i> {{ $student->university_id ?? $user->university_id ?? '—' }}
            </div>
        </div>
        <div class="profile-stats">
            <div class="stat-box">
                <div class="stat-value">{{ $student->level ?? $user->academic_year ?? '—' }}</div>
                <div class="stat-label">السنة الدراسية</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="font-size: 0.95rem;">{{ $user->department ?? '—' }}</div>
                <div class="stat-label">التخصص</div>
            </div>
        </div>
    </div>
</div>

<div class="profile-grid">
    {{-- Edit Info --}}
    <div class="glass-card">
        <div class="card-title">
            <i class="fa-solid fa-user-pen"></i> المعلومات الشخصية
        </div>
        <form action="{{ route('student.profile.update') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">الاسم الكامل</label>
                <div class="input-wrapper">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="full_name" class="form-control" value="{{ $user->full_name }}" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">البريد الإلكتروني</label>
                <div class="input-wrapper">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">رقم الهاتف</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-phone"></i>
                    <input type="text" name="phone" class="form-control" value="{{ $user->phone ?? '' }}" placeholder="أدخل رقم هاتفك">
                </div>
            </div>
            <button type="submit" class="btn-submit">
                حفظ التغييرات <i class="fa-solid fa-check"></i>
            </button>
        </form>
    </div>

    {{-- Change Password --}}
    <div class="glass-card">
        <div class="card-title">
            <i class="fa-solid fa-shield-halved"></i> الأمان وكلمة المرور
        </div>
        <form action="{{ route('student.profile.password') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">كلمة المرور الحالية</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-unlock-keyhole"></i>
                    <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">كلمة المرور الجديدة</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-key"></i>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">تأكيد كلمة المرور</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-check-double"></i>
                    <input type="password" name="new_password_confirmation" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn-submit" style="background: var(--bg-primary); color: var(--text-primary); border: 2px solid var(--border-color);">
                تحديث كلمة المرور <i class="fa-solid fa-arrows-rotate"></i>
            </button>
        </form>
    </div>
</div>

@endsection
