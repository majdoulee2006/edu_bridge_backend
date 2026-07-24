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

<!-- Info Cards -->
<div class="profile-grid">
    <div class="glass-card" style="padding: 1.5rem;">
        <div class="card-title"><i class="fa-solid fa-user-pen"></i> المعلومات الشخصية</div>
        
        <div class="info-list">
            <div class="info-card">
                <div class="info-right">
                    <div class="info-icon-wrapper" style="background: rgba(255, 215, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-user"></i></div>
                    <div>
                        <div class="info-label">الاسم الكامل</div>
                        <div class="info-value">{{ $user->full_name }}</div>
                    </div>
                </div>
                <button class="edit-btn" onclick="openEditModal('name')"><i class="fa-solid fa-pen"></i></button>
            </div>

            <div class="info-card">
                <div class="info-right">
                    <div class="info-icon-wrapper" style="background: rgba(255, 215, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-phone"></i></div>
                    <div>
                        <div class="info-label">رقم الهاتف</div>
                        <div class="info-value" dir="ltr" style="text-align: right;">{{ $user->phone ?? 'غير محدد' }}</div>
                    </div>
                </div>
                <button class="edit-btn" onclick="openEditModal('phone')"><i class="fa-solid fa-pen"></i></button>
            </div>

            <div class="info-card">
                <div class="info-right">
                    <div class="info-icon-wrapper" style="background: rgba(255, 215, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <div class="info-label">البريد الإلكتروني</div>
                        <div class="info-value">{{ $user->email }}</div>
                    </div>
                </div>
                <button class="edit-btn" onclick="openEditModal('email')"><i class="fa-solid fa-pen"></i></button>
            </div>
        </div>
    </div>

    <div class="glass-card" style="padding: 1.5rem; display: flex; flex-direction: column;">
        <div class="card-title"><i class="fa-solid fa-shield-halved"></i> الأمان والإعدادات</div>
        
        <div class="action-rows" style="margin-top: auto; margin-bottom: auto;">
            <button class="action-row" onclick="openPasswordModal()">
                <div class="action-row-inner">
                    <div class="action-row-icon" style="background: rgba(255, 215, 0, 0.15); color: var(--accent-color);">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div style="text-align: right;">
                        <div class="action-row-title">تغيير كلمة المرور</div>
                        <div class="action-row-desc">حماية إضافية لحسابك</div>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-left" style="color: var(--text-secondary);"></i>
            </button>
        </div>

        <form action="{{ route('student.logout') }}" method="POST" style="margin-top: auto;">
            @csrf
            <button type="submit" class="btn-submit" style="background: #ef4444; color: white; border: none; margin-top: 1rem;">
                تسجيل الخروج <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </button>
        </form>
    </div>
</div>

<!-- Edit Info Modal -->
<div class="modal-overlay" id="editInfoModal">
    <div class="modal-card">
        <h3 id="editModalTitle" style="margin-bottom: 1.5rem; font-weight: 800; color: var(--text-primary);">تعديل البيانات</h3>
        <form id="profileUpdateForm" onsubmit="handleProfileSubmit(event)" style="text-align: right;">
            <div id="nameInputGroup" style="display: none; margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color: var(--text-primary);">الاسم الكامل</label>
                <input type="text" id="edit_full_name" class="form-control" value="{{ $user->full_name }}">
            </div>
            <div id="phoneInputGroup" style="display: none; margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color: var(--text-primary);">رقم الهاتف</label>
                <input type="text" id="edit_phone" class="form-control" value="{{ $user->phone ?? '' }}">
            </div>
            <div id="emailInputGroup" style="display: none; margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color: var(--text-primary);">البريد الإلكتروني</label>
                <input type="email" id="edit_email" class="form-control" value="{{ $user->email ?? '' }}">
            </div>
            
            <div id="telegramInputGroup" style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--accent-color);">
                    <i class="fa-brands fa-telegram"></i> معرف حساب تيليغرام (لإرسال OTP)
                </label>
                <input type="text" id="edit_telegram" class="form-control" placeholder="مثال: @username أو معرف الحساب">
                <small style="display:block; margin-top:0.3rem; color:var(--text-secondary); font-size:0.8rem;">اختياري إذا كان مسجلاً مسبقاً</small>
            </div>

            <div id="profile-error" style="color: #ef4444; font-size: 0.85rem; margin-bottom: 1rem; display: none;"></div>
            <button type="submit" id="profile-btn" class="btn-submit" style="margin-top:0;">
                <i class="fa-solid fa-paper-plane"></i> إرسال رمز التحقق
            </button>
            <button type="button" class="btn-submit" style="background: transparent; color: var(--text-secondary); margin-top:0.5rem;" onclick="closeModals()">إلغاء</button>
        </form>
    </div>
</div>

<!-- Edit Password Modal -->
<div class="modal-overlay" id="editPasswordModal">
    <div class="modal-card">
        <h3 style="margin-bottom: 1.5rem; font-weight: 800; color: var(--text-primary);">تغيير كلمة المرور</h3>
        <form id="password-form" onsubmit="handlePasswordSubmit(event)" style="text-align: right;">
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color: var(--text-primary);">كلمة المرور الحالية</label>
                <input type="password" id="current_password" class="form-control" placeholder="••••••••" required>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color: var(--text-primary);">كلمة المرور الجديدة</label>
                <input type="password" id="new_password" class="form-control" placeholder="••••••••" required minlength="6">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color: var(--text-primary);">تأكيد كلمة المرور</label>
                <input type="password" id="new_password_confirmation" class="form-control" placeholder="••••••••" required minlength="6">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--accent-color);">
                    <i class="fa-brands fa-telegram"></i> معرف حساب تيليغرام (لإرسال OTP)
                </label>
                <input type="text" id="password_telegram" class="form-control" placeholder="مثال: @username أو معرف الحساب">
                <small style="display:block; margin-top:0.3rem; color:var(--text-secondary); font-size:0.8rem;">اختياري إذا كان مسجلاً مسبقاً</small>
            </div>

            <div id="password-error" style="color: #ef4444; font-size: 0.85rem; margin-bottom: 1rem; display: none;"></div>
            <button type="submit" id="password-btn" class="btn-submit" style="margin-top:0;">
                <i class="fa-solid fa-paper-plane"></i> إرسال رمز التحقق
            </button>
            <button type="button" class="btn-submit" style="background: transparent; color: var(--text-secondary); margin-top:0.5rem;" onclick="closeModals()">إلغاء</button>
        </form>
    </div>
</div>

<!-- OTP Modal -->
<div class="modal-overlay" id="otpModal">
    <div class="modal-card" style="text-align: center;">
        <div style="width: 70px; height: 70px; background: rgba(255, 215, 0, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--accent-color); margin: 0 auto 1rem;">
            <i class="fa-solid fa-paper-plane"></i>
        </div>
        <h3 style="font-size: 1.4rem; color: var(--text-primary); margin-bottom: 0.5rem; font-weight: 800;">التحقق عبر تيليغرام (OTP)</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">تم إرسال رمز التحقق المكون من 6 أرقام إلى حسابك في بوت تيليغرام.</p>
        <div class="otp-input-row">
            <input class="otp-digit" type="text" maxlength="1">
            <input class="otp-digit" type="text" maxlength="1">
            <input class="otp-digit" type="text" maxlength="1">
            <input class="otp-digit" type="text" maxlength="1">
            <input class="otp-digit" type="text" maxlength="1">
            <input class="otp-digit" type="text" maxlength="1">
        </div>
        <div id="otp-error" style="color: #ef4444; font-size: 0.85rem; margin-bottom: 1rem; display: none;"></div>
        <button type="button" class="btn-submit" id="otp-btn" style="margin-top:0;" onclick="verifyOTP()">تأكيد وحفظ</button>
        <button type="button" class="btn-submit" style="background: transparent; color: var(--text-secondary); margin-top:0.5rem;" onclick="closeOTPModal()">إلغاء</button>
    </div>
</div>

@endsection

@push('scripts')
<style>
    .info-list { display: flex; flex-direction: column; gap: 0.8rem; }
    .info-card {
        background: var(--bg-primary); border-radius: 1rem; padding: 1rem 1.5rem;
        display: flex; align-items: center; justify-content: space-between; border: 1px solid var(--border-color);
    }
    .info-right { display: flex; align-items: center; gap: 1rem; text-align: right; }
    .info-icon-wrapper {
        width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }
    .info-label { color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.2rem; }
    .info-value { font-weight: 800; font-size: 1rem; color: var(--text-primary); }
    .edit-btn { background: none; border: none; color: var(--accent-color); font-size: 1rem; cursor: pointer; padding: 0.4rem; transition: opacity 0.2s; }
    .edit-btn:hover { opacity: 0.7; }

    .action-rows { display: flex; flex-direction: column; gap: 0.8rem; }
    .action-row {
        background: var(--bg-primary); border-radius: 1rem; padding: 1rem 1.5rem; border: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: transform 0.2s; width: 100%; text-align: right;
    }
    .action-row-inner { display: flex; align-items: center; gap: 1rem; }
    .action-row-icon { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .action-row-title { font-weight: 800; color: var(--text-primary); }
    .action-row-desc { font-size: 0.8rem; color: var(--text-secondary); }

    .modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center; z-index: 10000; backdrop-filter: blur(5px);
    }
    .modal-overlay.active { display: flex; }
    .modal-card {
        background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; width: 90%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: slideUp 0.3s ease; position: relative;
    }
    @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    
    .otp-input-row { display: flex; gap: 0.5rem; justify-content: center; margin-bottom: 1.5rem; direction: ltr; }
    .otp-digit {
        width: 45px; height: 50px; border: 2px solid var(--border-color); border-radius: 0.75rem; text-align: center; font-size: 1.5rem; font-weight: 800; background: var(--bg-primary); color: var(--text-primary);
    }
    .otp-digit:focus { border-color: var(--accent-color); outline: none; }
</style>

<script>
const csrfToken = '{{ csrf_token() }}';
let currentEditField = null;

function openEditModal(field) {
    currentEditField = field;
    document.getElementById('nameInputGroup').style.display = 'none';
    document.getElementById('phoneInputGroup').style.display = 'none';
    document.getElementById('emailInputGroup').style.display = 'none';
    document.getElementById('profile-error').style.display = 'none';
    document.getElementById('edit_telegram').value = '';

    if (field === 'name') {
        document.getElementById('editModalTitle').innerText = 'تعديل الاسم الكامل';
        document.getElementById('nameInputGroup').style.display = 'block';
    } else if (field === 'phone') {
        document.getElementById('editModalTitle').innerText = 'تعديل رقم الهاتف';
        document.getElementById('phoneInputGroup').style.display = 'block';
    } else if (field === 'email') {
        document.getElementById('editModalTitle').innerText = 'تعديل البريد الإلكتروني';
        document.getElementById('emailInputGroup').style.display = 'block';
    }
    document.getElementById('editInfoModal').classList.add('active');
}

function openPasswordModal() {
    document.getElementById('editPasswordModal').classList.add('active');
    document.getElementById('password-error').style.display = 'none';
    document.getElementById('current_password').value = '';
    document.getElementById('new_password').value = '';
    document.getElementById('new_password_confirmation').value = '';
    document.getElementById('password_telegram').value = '';
}

function closeModals() {
    document.getElementById('editInfoModal').classList.remove('active');
    document.getElementById('editPasswordModal').classList.remove('active');
}

function showOTPModal() {
    document.getElementById('otpModal').classList.add('active');
    document.querySelectorAll('.otp-digit').forEach(i => i.value = '');
    document.getElementById('otp-error').style.display = 'none';
    document.querySelector('.otp-digit').focus();
}

function closeOTPModal() {
    document.getElementById('otpModal').classList.remove('active');
    document.querySelectorAll('.otp-digit').forEach(i => i.value = '');
}

window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeModals();
        closeOTPModal();
    }
});

function handleProfileSubmit(e) {
    e.preventDefault();
    const payload = {};
    if (currentEditField === 'name')  payload.full_name = document.getElementById('edit_full_name').value;
    if (currentEditField === 'phone') payload.phone = document.getElementById('edit_phone').value;
    if (currentEditField === 'email') payload.email = document.getElementById('edit_email').value;
    
    payload.telegram_chat_id = document.getElementById('edit_telegram').value;

    const btn      = document.getElementById('profile-btn');
    const errorDiv = document.getElementById('profile-error');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري إرسال OTP...';
    errorDiv.style.display = 'none';

    fetch('{{ route("student.profile.send_otp") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> إرسال رمز التحقق';
        if (data.success) {
            closeModals();
            showOTPModal();
        } else {
            errorDiv.innerText = data.message || 'حدث خطأ';
            errorDiv.style.display = 'block';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> إرسال رمز التحقق';
        errorDiv.innerText = 'فشل الاتصال بالخادم';
        errorDiv.style.display = 'block';
    });
}

function handlePasswordSubmit(e) {
    e.preventDefault();
    const current = document.getElementById('current_password').value;
    const newPw   = document.getElementById('new_password').value;
    const confirm = document.getElementById('new_password_confirmation').value;
    const telegramId = document.getElementById('password_telegram').value;
    const btn     = document.getElementById('password-btn');
    const errorDiv= document.getElementById('password-error');

    if (newPw !== confirm) {
        errorDiv.innerText = 'كلمة المرور الجديدة غير متطابقة';
        errorDiv.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري إرسال OTP...';
    errorDiv.style.display = 'none';

    fetch('{{ route("student.profile.send_otp") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ 
            current_password: current, 
            new_password: newPw,
            telegram_chat_id: telegramId
        })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> إرسال رمز التحقق';
        if (data.success) {
            closeModals();
            showOTPModal();
        } else {
            errorDiv.innerText = data.message || 'حدث خطأ';
            errorDiv.style.display = 'block';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> إرسال رمز التحقق';
        errorDiv.innerText = 'فشل الاتصال بالخادم';
        errorDiv.style.display = 'block';
    });
}

function verifyOTP() {
    const otp = [...document.querySelectorAll('.otp-digit')].map(i => i.value).join('');
    if (otp.length < 6) { alert('يرجى إدخال الرمز كاملاً (6 أرقام)'); return; }

    const btn     = document.getElementById('otp-btn');
    const errorDiv= document.getElementById('otp-error');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري التحقق...';
    errorDiv.style.display = 'none';

    fetch('{{ route("student.profile.verify_otp") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ otp: otp })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            btn.disabled = false;
            btn.innerHTML = 'تأكيد وحفظ';
            errorDiv.innerText = data.message || 'الرمز غير صحيح';
            errorDiv.style.display = 'block';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = 'تأكيد وحفظ';
        errorDiv.innerText = 'فشل الاتصال بالخادم';
        errorDiv.style.display = 'block';
    });
}

/* OTP auto-advance */
document.querySelectorAll('.otp-digit').forEach((input, index, inputs) => {
    input.addEventListener('input', () => {
        if (input.value && index < inputs.length - 1) inputs[index + 1].focus();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !input.value && index > 0) inputs[index - 1].focus();
    });
});
</script>
@endpush
