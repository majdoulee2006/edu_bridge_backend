@extends('layouts.parent')
@section('title', 'الملف الشخصي')
@section('subtitle', 'إدارة وتحديث بيانات الحساب الشخصي وكلمة المرور')

@push('styles')
<style>
    .profile-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    @media(min-width: 992px) {
        .profile-container {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    .profile-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 2rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }
    
    .profile-card h3 {
        font-size: 1.2rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0.75rem;
    }
    
    .profile-card h3 i {
        color: var(--accent-color);
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }
    
    .form-control {
        width: 100%;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        font-family: inherit;
        font-size: 0.9rem;
        outline: none;
    }
    
    .form-control:focus {
        border-color: var(--accent-color);
    }
    
    .btn-submit {
        background: var(--accent-color);
        color: #1a1a1a;
        font-weight: 700;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: background 0.2s ease;
    }
    
    .btn-submit:hover {
        background: #e6b800;
    }
</style>
@endpush

@section('content')

<div class="profile-container">
    <!-- Personal Information Card -->
    <div class="profile-card">
        <h3><i class="fa-solid fa-user-gear"></i> البيانات الشخصية</h3>
        
        <form action="{{ route('parent.profile.update') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="full_name">الاسم الكامل</label>
                <input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name', $user->full_name) }}" required>
            </div>
            
            <div class="form-group">
                <label for="username">اسم المستخدم (للقراءة فقط)</label>
                <input type="text" id="username" class="form-control" value="{{ $user->username }}" readonly style="opacity: 0.7; cursor: not-allowed; background: var(--bg-secondary);">
            </div>
            
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            
            <div class="form-group">
                <label for="phone">رقم الهاتف</label>
                <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-floppy-disk"></i> حفظ التغييرات
            </button>
        </form>
    </div>
    
    <!-- Change Password Card -->
    <div class="profile-card">
        <h3><i class="fa-solid fa-key"></i> تغيير كلمة المرور</h3>
        
        <form action="{{ route('parent.profile.password') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="current_password">كلمة المرور الحالية</label>
                <input type="password" name="current_password" id="current_password" class="form-control" placeholder="أدخل كلمة المرور الحالية" required>
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور الجديدة</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="أدخل كلمة المرور الجديدة (6 أحرف على الأقل)" required>
            </div>
            
            <div class="form-group">
                <label for="password_confirmation">تأكيد كلمة المرور الجديدة</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="أعد إدخال كلمة المرور الجديدة" required>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-shield-halved"></i> تحديث كلمة المرور
            </button>
        </form>
    </div>
</div>

@endsection
