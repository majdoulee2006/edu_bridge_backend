@extends('layouts.affairs')
@section('title', 'الملف الشخصي')

@push('styles')
<style>
    .profile-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 1rem;
    }
    .profile-avatar-wrapper {
        position: relative;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin-bottom: 1rem;
        background: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80') center/cover;
        border: 4px solid var(--bg-secondary);
        box-shadow: var(--shadow);
    }
    .camera-btn {
        position: absolute;
        bottom: 5px;
        left: 5px;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--accent-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid var(--bg-primary);
        cursor: pointer;
    }
    .profile-name {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    .profile-role {
        background: var(--accent-color);
        color: var(--primary-dark);
        padding: 0.2rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 2rem;
    }
    .info-list {
        width: 100%;
        max-width: 600px;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .info-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow);
    }
    .info-details {
        display: flex;
        flex-direction: column;
    }
    .info-label {
        color: var(--text-secondary);
        font-size: 0.8rem;
        margin-bottom: 0.2rem;
    }
    .info-value {
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--text-primary);
    }
    .info-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--bg-primary); color: var(--accent-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .edit-btn {
        color: var(--accent-color);
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
    }
    .edit-btn:hover {
        opacity: 0.8;
    }
    .action-row {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow);
        cursor: pointer;
        width: 100%;
        max-width: 600px;
        margin-top: 1rem;
        font-weight: 800;
    }
    .action-row:hover { background: #f8fafc; }
    .logout-btn {
        background: var(--accent-color);
        color: var(--primary-dark);
        border-radius: 1.5rem;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-weight: 800;
        width: 100%;
        max-width: 600px;
        margin-top: 1rem;
        border: none;
        cursor: pointer;
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }

    /* Modal Styles for OTP */
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.5);
        display: none; align-items: center; justify-content: center; z-index: 10000;
    }
    .modal-overlay.active { display: flex; }
    .modal-card {
        background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem;
        width: 90%; max-width: 400px; text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .modal-card input {
        width: 100%; padding: 1rem; border-radius: 1rem; border: 2px solid var(--border-color);
        margin: 1rem 0; font-size: 1rem; font-weight: 700; text-align: center;
    }
    .modal-card button {
        width: 100%; padding: 1rem; border-radius: 1rem; border: none;
        background: var(--accent-color); color: #1a1a1a; font-weight: 800; font-size: 1rem;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="profile-container">
    <div class="profile-avatar-wrapper">
        <div class="camera-btn"><i class="fa-solid fa-camera"></i></div>
    </div>
    <div class="profile-name">{{ auth()->user()->full_name ?? 'أحمد محمد' }}</div>
    <div class="profile-role">موظف شؤون</div>

    <div class="info-list">
        <!-- Phone -->
        <div class="info-card">
            <button class="edit-btn" onclick="openOTPModal('phone')"><i class="fa-solid fa-pen"></i></button>
            <div class="info-details" style="text-align: right; flex: 1; padding-right: 1rem;">
                <div class="info-label">رقم الهاتف</div>
                <div class="info-value" dir="ltr">050 123 4567</div>
            </div>
            <div class="info-icon-wrapper" style="background: #e0f2fe; color: #0284c7;"><i class="fa-solid fa-phone"></i></div>
        </div>

        <!-- Email -->
        <div class="info-card">
            <button class="edit-btn" onclick="openOTPModal('email')"><i class="fa-solid fa-pen"></i></button>
            <div class="info-details" style="text-align: right; flex: 1; padding-right: 1rem;">
                <div class="info-label">البريد الإلكتروني</div>
                <div class="info-value">...ed@edubridge.com</div>
            </div>
            <div class="info-icon-wrapper" style="background: #e0e7ff; color: #4f46e5;"><i class="fa-solid fa-envelope"></i></div>
        </div>

        <!-- Birthdate -->
        <div class="info-card">
            <div></div> <!-- Spacer -->
            <div class="info-details" style="text-align: right; flex: 1; padding-right: 1rem;">
                <div class="info-label">تاريخ الميلاد</div>
                <div class="info-value">15 أغسطس 1993</div>
            </div>
            <div class="info-icon-wrapper" style="background: #f1f5f9; color: #64748b;"><i class="fa-solid fa-calendar"></i></div>
        </div>

        <!-- Gender -->
        <div class="info-card">
            <div></div> <!-- Spacer -->
            <div class="info-details" style="text-align: right; flex: 1; padding-right: 1rem;">
                <div class="info-label">الجنس</div>
                <div class="info-value">ذكر</div>
            </div>
            <div class="info-icon-wrapper" style="background: #dbeafe; color: #2563eb;"><i class="fa-solid fa-person"></i></div>
        </div>

        <!-- Department -->
        <div class="info-card">
            <i class="fa-solid fa-lock" style="color: var(--text-secondary);"></i>
            <div class="info-details" style="text-align: right; flex: 1; padding-right: 1rem;">
                <div class="info-label">القسم</div>
                <div class="info-value">شؤون الطلاب</div>
            </div>
            <div class="info-icon-wrapper" style="background: #f3f4f6; color: #4b5563;"><i class="fa-solid fa-building"></i></div>
        </div>
    </div>

    <!-- Password -->
    <div class="action-row">
        <i class="fa-solid fa-chevron-left" style="color: var(--text-secondary);"></i>
        <div style="flex: 1; text-align: right; padding-right: 1rem; color: var(--text-primary);">تغيير كلمة المرور</div>
        <i class="fa-solid fa-arrow-rotate-left" style="font-size: 1.2rem;"></i>
    </div>

    <!-- Logout -->
    <form action="{{ route('affairs.logout') }}" method="POST" style="width: 100%; max-width: 600px;">
        @csrf
        <button type="submit" class="logout-btn">
            تسجيل الخروج <i class="fa-solid fa-arrow-right-from-bracket" style="transform: scaleX(-1);"></i>
        </button>
    </form>
</div>

<!-- OTP Modal -->
<div class="modal-overlay" id="otpModal">
    <div class="modal-card">
        <h3 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-shield-halved" style="color: var(--accent-color);"></i> تحقق أمني (OTP)</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">لقد أرسلنا رمز تحقق إلى هاتفك المحمول. يرجى إدخاله للمتابعة.</p>
        <input type="text" placeholder="----" maxlength="4" style="letter-spacing: 0.5rem;">
        <button type="button" onclick="verifyOTP()">تأكيد وإرسال</button>
        <button type="button" style="background: transparent; color: var(--text-secondary); margin-top: 0.5rem;" onclick="closeOTPModal()">إلغاء</button>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openOTPModal(type) {
        document.getElementById('otpModal').classList.add('active');
    }
    function closeOTPModal() {
        document.getElementById('otpModal').classList.remove('active');
    }
    function verifyOTP() {
        alert('تم تأكيد الرمز (Mockup)');
        closeOTPModal();
    }
</script>
@endpush
