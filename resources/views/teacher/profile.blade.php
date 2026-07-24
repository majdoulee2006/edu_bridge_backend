@extends('layouts.teacher')
@section('title', 'الملف الشخصي')

@push('styles')
<style>
    .profile-page {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 1rem 3rem;
    }

    /* Cover & Avatar */
    .profile-cover {
        height: 200px;
        border-radius: 1.5rem;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        position: relative;
        margin-bottom: 4rem;
        overflow: visible;
        box-shadow: var(--shadow);
    }
    .cover-pattern {
        position: absolute;
        inset: 0;
        border-radius: 1.5rem;
        background: repeating-linear-gradient(
            45deg,
            rgba(242, 242, 13, 0.04),
            rgba(242, 242, 13, 0.04) 1px,
            transparent 1px,
            transparent 20px
        );
    }
    .cover-accent {
        position: absolute;
        bottom: -2px;
        right: 2rem;
        width: 100px;
        height: 100px;
        background: var(--accent-color);
        border-radius: 50%;
        opacity: 0.08;
        filter: blur(30px);
    }

    .profile-avatar-wrapper {
        position: absolute;
        bottom: -55px;
        right: 2rem;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-color), #d9d905);
        border: 5px solid var(--bg-primary);
        box-shadow: 0 4px 20px rgba(242, 242, 13, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 800;
        color: #1a1a1a;
        overflow: hidden;
    }

    .profile-identity {
        padding: 0 2rem;
        margin-bottom: 2rem;
    }
    .profile-name {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0 0 0.4rem 0;
    }
    .profile-role-badge {
        display: inline-block;
        background: var(--accent-color);
        color: #1a1a1a;
        padding: 0.3rem 1.2rem;
        border-radius: 2rem;
        font-size: 0.9rem;
        font-weight: 800;
    }

    /* Stats */
    .profile-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1.2rem;
        text-align: center;
        box-shadow: var(--shadow);
    }
    .stat-value {
        font-size: 1.8rem;
        font-weight: 900;
        color: var(--accent-color);
        display: block;
    }
    .stat-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        font-weight: 600;
    }

    .section-heading {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .section-heading i { color: var(--accent-color); }

    /* Info Cards */
    .info-list { display: flex; flex-direction: column; gap: 0.8rem; margin-bottom: 2rem; }
    .info-card {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow);
        transition: transform 0.2s;
    }
    .info-card:hover { transform: translateX(-3px); }
    .info-right { display: flex; align-items: center; gap: 1rem; }
    .info-icon-wrapper {
        width: 44px; height: 44px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .info-label { color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.2rem; }
    .info-value { font-weight: 800; font-size: 1rem; color: var(--text-primary); }
    .edit-btn {
        background: none; border: none; color: var(--accent-color);
        font-size: 1rem; cursor: pointer; padding: 0.4rem; transition: opacity 0.2s;
    }
    .edit-btn:hover { opacity: 0.7; }

    /* Action Rows */
    .action-rows { display: flex; flex-direction: column; gap: 0.8rem; margin-bottom: 1.5rem; }
    .action-row {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        display: flex; align-items: center; justify-content: space-between;
        box-shadow: var(--shadow);
        cursor: pointer; transition: transform 0.2s;
        width: 100%; border: none; text-align: right;
    }
    .action-row:hover { transform: translateX(-3px); }
    .action-row-inner { display: flex; align-items: center; gap: 1rem; }
    .action-row-icon {
        width: 44px; height: 44px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }
    .action-row-title { font-weight: 800; color: var(--text-primary); }
    .action-row-desc { font-size: 0.8rem; color: var(--text-secondary); }

    /* Logout */
    .logout-btn {
        width: 100%;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white; border: none; border-radius: 1rem;
        padding: 1.2rem; font-size: 1.1rem; font-weight: 800; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 0.75rem;
        transition: opacity 0.2s, transform 0.2s; font-family: inherit;
    }
    .logout-btn:hover { opacity: 0.9; transform: translateY(-1px); }

    /* Modals */
    .modal-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.6);
        display: none; align-items: center; justify-content: center;
        z-index: 10000; backdrop-filter: blur(5px);
    }
    .modal-overlay.active { display: flex; }
    .modal-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem; padding: 2rem;
        width: 90%; max-width: 420px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: slideUp 0.3s ease;
        position: relative;
    }
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    .form-input {
        width: 100%; padding: 0.85rem 1rem;
        border: 1px solid var(--border-color); border-radius: 0.75rem;
        background: var(--bg-primary); color: var(--text-primary);
        font-family: inherit; font-size: 0.95rem; box-sizing: border-box;
    }
    .form-input:focus { outline: none; border-color: var(--accent-color); }
    .modal-confirm-btn {
        width: 100%; padding: 1rem; border-radius: 1rem; border: none;
        background: var(--accent-color); color: #1a1a1a;
        font-weight: 800; font-size: 1.1rem; cursor: pointer; margin-bottom: 0.8rem;
        font-family: inherit;
    }
    .modal-cancel-btn {
        width: 100%; padding: 0.8rem; border-radius: 1rem; border: none;
        background: transparent; color: var(--text-secondary); font-weight: 700;
        cursor: pointer; font-family: inherit;
    }
    .modal-cancel-btn:hover { color: var(--text-primary); }

    .otp-input-row { display: flex; gap: 0.8rem; justify-content: center; margin-bottom: 1.5rem; }
    .otp-digit {
        width: 55px; height: 60px;
        border: 2px solid var(--border-color); border-radius: 0.75rem;
        text-align: center; font-size: 1.8rem; font-weight: 800;
        background: var(--bg-primary); color: var(--text-primary);
        transition: border-color 0.2s;
    }
    .otp-digit:focus { border-color: var(--accent-color); outline: none; }
</style>
@endpush

@section('content')
<div class="profile-page">

    <!-- Cover -->
    <div class="profile-cover">
        <div class="cover-pattern"></div>
        <div class="cover-accent"></div>
        <div class="profile-avatar-wrapper">
            {{ mb_substr($user->full_name, 0, 1) }}
        </div>
    </div>

    <!-- Name & Role -->
    <div class="profile-identity">
        <h2 class="profile-name">{{ $user->full_name }}</h2>
        <span class="profile-role-badge">أستاذ · {{ $teacher->specialization ?? 'معلم' }}</span>
    </div>

    <!-- Stats -->
    <div class="profile-stats">
        <div class="stat-card">
            <span class="stat-value">{{ $courses->count() }}</span>
            <span class="stat-label">المواد الدراسية</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ $totalStudents ?? 0 }}</span>
            <span class="stat-label">الطلاب</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ now()->year - ($user->created_at ? $user->created_at->year : now()->year) }}</span>
            <span class="stat-label">سنوات الخدمة</span>
        </div>
    </div>

    <!-- Personal Info -->
    <p class="section-heading"><i class="fa-solid fa-circle-info"></i> المعلومات الشخصية</p>
    <div class="info-list">
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(242,242,13,0.15); color: var(--accent-color);"><i class="fa-solid fa-user"></i></div>
                <div>
                    <div class="info-label">الاسم الكامل</div>
                    <div class="info-value">{{ $user->full_name }}</div>
                </div>
            </div>
            <button class="edit-btn" onclick="openEditModal('name')"><i class="fa-solid fa-pen"></i></button>
        </div>

        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(242,242,13,0.15); color: var(--accent-color);"><i class="fa-solid fa-phone"></i></div>
                <div>
                    <div class="info-label">رقم الهاتف</div>
                    <div class="info-value" dir="ltr" style="text-align: right;">{{ $user->phone ?? 'غير محدد' }}</div>
                </div>
            </div>
            <button class="edit-btn" onclick="openEditModal('phone')"><i class="fa-solid fa-pen"></i></button>
        </div>

        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(242,242,13,0.15); color: var(--accent-color);"><i class="fa-solid fa-envelope"></i></div>
                <div>
                    <div class="info-label">البريد الإلكتروني</div>
                    <div class="info-value">{{ $user->email }}</div>
                </div>
            </div>
            <button class="edit-btn" onclick="openEditModal('email')"><i class="fa-solid fa-pen"></i></button>
        </div>

        @if($courses->count())
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(242,242,13,0.15); color: var(--accent-color);"><i class="fa-solid fa-book"></i></div>
                <div>
                    <div class="info-label">المواد التي يدرّسها</div>
                    <div class="info-value" style="font-size: 0.9rem;">{{ $courses->pluck('title')->implode(' · ') }}</div>
                </div>
            </div>
            <div></div>
        </div>
        @endif
    </div>

    <!-- Settings -->
    <p class="section-heading"><i class="fa-solid fa-sliders"></i> إعدادات الحساب</p>
    <div class="action-rows">
        <button class="action-row" onclick="openPasswordModal()">
            <div class="action-row-inner">
                <div class="action-row-icon" style="background: rgba(242,242,13,0.15); color: var(--accent-color);">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div style="text-align: right;">
                    <div class="action-row-title">تغيير كلمة المرور</div>
                    <div class="action-row-desc">حماية إضافية للحساب</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-left" style="color: var(--text-secondary);"></i>
        </button>
    </div>

    <!-- Logout -->
    <form action="{{ route('teacher.logout') }}" method="POST">
        @csrf
        <button type="submit" class="logout-btn">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            تسجيل الخروج
        </button>
    </form>

</div>

<!-- Edit Info Modal -->
<div class="modal-overlay" id="editInfoModal">
    <div class="modal-card">
        <h3 id="editModalTitle" style="margin-bottom: 1.5rem; font-weight: 800;">تعديل البيانات</h3>
        <form id="profileUpdateForm" onsubmit="handleProfileSubmit(event)">
            <div id="nameInputGroup" style="display: none; margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700;">الاسم الكامل</label>
                <input type="text" id="edit_full_name" class="form-input" value="{{ $user->full_name }}">
            </div>
            <div id="phoneInputGroup" style="display: none; margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700;">رقم الهاتف</label>
                <input type="text" id="edit_phone" class="form-input" value="{{ $user->phone ?? '' }}">
            </div>
            <div id="emailInputGroup" style="display: none; margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700;">البريد الإلكتروني</label>
                <input type="email" id="edit_email" class="form-input" value="{{ $user->email ?? '' }}">
            </div>
            
            <div id="telegramInputGroup" style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--accent-color);">
                    <i class="fa-brands fa-telegram"></i> معرف حساب تيليغرام (لإرسال OTP)
                </label>
                <input type="text" id="edit_telegram" class="form-input" placeholder="مثال: @username أو معرف الحساب">
                <small style="display:block; margin-top:0.3rem; color:var(--text-secondary); font-size:0.8rem;">اختياري إذا كان مسجلاً مسبقاً</small>
            </div>

            <div id="profile-error" style="color: #ef4444; font-size: 0.85rem; margin-bottom: 1rem; display: none;"></div>
            <button type="submit" id="profile-btn" class="modal-confirm-btn">
                <i class="fa-solid fa-paper-plane"></i> إرسال رمز التحقق
            </button>
            <button type="button" class="modal-cancel-btn" onclick="closeModals()">إلغاء</button>
        </form>
    </div>
</div>

<!-- Edit Password Modal -->
<div class="modal-overlay" id="editPasswordModal">
    <div class="modal-card">
        <h3 style="margin-bottom: 1.5rem; font-weight: 800;">تغيير كلمة المرور</h3>
        <form id="password-form" onsubmit="handlePasswordSubmit(event)">
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700;">كلمة المرور الحالية</label>
                <input type="password" id="current_password" class="form-input" placeholder="••••••••" required>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700;">كلمة المرور الجديدة</label>
                <input type="password" id="new_password" class="form-input" placeholder="••••••••" required minlength="6">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700;">تأكيد كلمة المرور</label>
                <input type="password" id="new_password_confirmation" class="form-input" placeholder="••••••••" required minlength="6">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--accent-color);">
                    <i class="fa-brands fa-telegram"></i> معرف حساب تيليغرام (لإرسال OTP)
                </label>
                <input type="text" id="password_telegram" class="form-input" placeholder="مثال: @username أو معرف الحساب">
                <small style="display:block; margin-top:0.3rem; color:var(--text-secondary); font-size:0.8rem;">اختياري إذا كان مسجلاً مسبقاً</small>
            </div>

            <div id="password-error" style="color: #ef4444; font-size: 0.85rem; margin-bottom: 1rem; display: none;"></div>
            <button type="submit" id="password-btn" class="modal-confirm-btn">
                <i class="fa-solid fa-paper-plane"></i> إرسال رمز التحقق
            </button>
            <button type="button" class="modal-cancel-btn" onclick="closeModals()">إلغاء</button>
        </form>
    </div>
</div>

<!-- OTP Modal -->
<div class="modal-overlay" id="otpModal">
    <div class="modal-card" style="text-align: center;">
        <div style="width: 70px; height: 70px; background: rgba(242,242,13,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--accent-color); margin: 0 auto 1rem;">
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
        <button type="button" class="modal-confirm-btn" id="otp-btn" onclick="verifyOTP()">تأكيد وحفظ</button>
        <button type="button" class="modal-cancel-btn" onclick="closeOTPModal()">إلغاء</button>
    </div>
</div>

@endsection

@push('scripts')
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

    fetch('{{ route("teacher.profile.send_otp") }}', {
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

    fetch('{{ route("teacher.profile.send_otp") }}', {
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

function showOTPModal() {
    document.getElementById('otpModal').classList.add('active');
    document.querySelectorAll('.otp-digit').forEach(i => i.value = '');
    document.getElementById('otp-error').style.display = 'none';
    document.querySelector('.otp-digit').focus();
}

function verifyOTP() {
    const otp = [...document.querySelectorAll('.otp-digit')].map(i => i.value).join('');
    if (otp.length < 6) { alert('يرجى إدخال الرمز كاملاً (6 أرقام)'); return; }

    const btn     = document.getElementById('otp-btn');
    const errorDiv= document.getElementById('otp-error');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري التحقق...';
    errorDiv.style.display = 'none';

    fetch('{{ route("teacher.profile.verify_otp") }}', {
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
