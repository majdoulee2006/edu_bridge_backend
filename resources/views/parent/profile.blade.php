@extends('layouts.parent')
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
        background: linear-gradient(135deg, #1d2b64 0%, #f8cdda 100%);
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
            rgba(252, 227, 0, 0.04),
            rgba(252, 227, 0, 0.04) 1px,
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

    /* Avatar */
    .profile-avatar-wrapper {
        position: absolute;
        bottom: -55px;
        right: 2rem;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-color), #f9d813);
        border: 5px solid var(--bg-primary);
        box-shadow: 0 4px 20px rgba(252, 227, 0, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: var(--primary-dark);
        cursor: pointer;
        overflow: hidden;
    }
    .avatar-overlay {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
        cursor: pointer;
    }
    .avatar-overlay i { color: white; font-size: 1.5rem; }
    .profile-avatar-wrapper:hover .avatar-overlay { opacity: 1; }

    /* Name & Role */
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
        color: var(--primary-dark);
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

    /* Section Title */
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
    .info-list {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        margin-bottom: 2rem;
    }
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
    .info-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .info-icon-wrapper {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .info-details {}
    .info-label {
        color: var(--text-secondary);
        font-size: 0.8rem;
        margin-bottom: 0.2rem;
    }
    .info-value {
        font-weight: 800;
        font-size: 1rem;
        color: var(--text-primary);
    }
    .edit-btn {
        background: none;
        border: none;
        color: var(--accent-color);
        font-size: 1.1rem;
        cursor: pointer;
        padding: 0.4rem;
        transition: opacity 0.2s;
    }
    .edit-btn:hover { opacity: 0.7; }

    /* Action Rows */
    .action-rows {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        margin-bottom: 1.5rem;
    }
    .action-row {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow);
        cursor: pointer;
        transition: transform 0.2s;
        width: 100%;
        border: none;
        text-align: right;
    }
    .action-row:hover { transform: translateX(-3px); }
    .action-row-inner { display: flex; align-items: center; gap: 1rem; }
    .action-row-icon {
        width: 44px; height: 44px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }
    .action-row-title { font-weight: 800; color: var(--text-primary); }
    .action-row-desc { font-size: 0.8rem; color: var(--text-secondary); }

    /* Logout */
    .logout-btn {
        width: 100%;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: none;
        border-radius: 1rem;
        padding: 1.2rem;
        font-size: 1.1rem;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        transition: opacity 0.2s, transform 0.2s;
    }
    .logout-btn:hover { opacity: 0.9; transform: translateY(-1px); }

    /* Modal Overlay */
    .modal-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.6);
        display: none; align-items: center; justify-content: center;
        z-index: 10000;
        backdrop-filter: blur(5px);
    }
    .modal-overlay.active { display: flex; }
    .modal-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 2rem;
        width: 90%; max-width: 420px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: slideUp 0.3s ease;
    }
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .modal-icon {
        width: 70px; height: 70px;
        background: rgba(252, 227, 0, 0.15);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; color: var(--accent-color);
        margin: 0 auto 1rem;
    }
    .modal-card h3 { font-size: 1.4rem; color: var(--text-primary); margin-bottom: 0.5rem; }
    .modal-card p { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem; }
    
    .otp-input-row {
        display: flex; gap: 0.8rem; justify-content: center; margin-bottom: 1.5rem;
    }
    .otp-digit {
        width: 55px; height: 60px;
        border: 2px solid var(--border-color);
        border-radius: 0.75rem;
        text-align: center; font-size: 1.8rem; font-weight: 800;
        background: var(--bg-primary); color: var(--text-primary);
        transition: border-color 0.2s;
    }
    .otp-digit:focus { border-color: var(--accent-color); outline: none; }

    .modal-confirm-btn {
        width: 100%; padding: 1rem; border-radius: 1rem; border: none;
        background: var(--accent-color); color: var(--primary-dark);
        font-weight: 800; font-size: 1.1rem; cursor: pointer; margin-bottom: 0.8rem;
    }
    .modal-cancel-btn {
        width: 100%; padding: 0.8rem; border-radius: 1rem; border: none;
        background: transparent; color: var(--text-secondary); font-weight: 700; cursor: pointer;
    }
    .modal-cancel-btn:hover { color: var(--text-primary); }

    .form-control {
        width: 100%;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.8rem;
        border-radius: 0.5rem;
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
    }
    .form-control:focus {
        border-color: var(--accent-color);
    }
</style>
@endpush

@section('content')
<div class="profile-page">
    
    <!-- Cover -->
    <div class="profile-cover">
        <div class="cover-pattern"></div>
        <div class="cover-accent"></div>
        <div class="profile-avatar-wrapper">
            <i class="fa-solid fa-user-shield"></i>
            <div class="avatar-overlay">
                <i class="fa-solid fa-camera"></i>
            </div>
        </div>
    </div>

    <!-- Name & Role -->
    <div class="profile-identity">
        <h2 class="profile-name">{{ $user->full_name }}</h2>
        <span class="profile-role-badge">ولي أمر</span>
    </div>

    <!-- Stats -->
    <div class="profile-stats">
        <div class="stat-card">
            <span class="stat-value">{{ count($parent_children) }}</span>
            <span class="stat-label">الأبناء المسجلين</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ \DB::table('report_requests')->where('head_id', $user->user_id)->count() }}</span>
            <span class="stat-label">طلبات التقارير</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ \DB::table('notifications')->where('user_id', $user->user_id)->count() }}</span>
            <span class="stat-label">الإشعارات الواردة</span>
        </div>
    </div>

    <!-- Personal Info -->
    <p class="section-heading"><i class="fa-solid fa-circle-info"></i> المعلومات الشخصية</p>
    <div class="info-list">
        <!-- Full Name -->
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(252, 227, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-user"></i></div>
                <div class="info-details">
                    <div class="info-label">الاسم الكامل</div>
                    <div class="info-value">{{ $user->full_name }}</div>
                </div>
            </div>
            <button class="edit-btn" style="cursor: not-allowed; opacity: 0.5;" title="لا يمكن تعديل الاسم"><i class="fa-solid fa-lock"></i></button>
        </div>

        <!-- Phone -->
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(252, 227, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-phone"></i></div>
                <div class="info-details">
                    <div class="info-label">رقم الهاتف</div>
                    <div class="info-value" dir="ltr" style="text-align: right;">{{ $user->phone ?? 'غير محدد' }}</div>
                </div>
            </div>
            <button class="edit-btn" onclick="openEditModal('phone')"><i class="fa-solid fa-pen"></i></button>
        </div>

        <!-- Email -->
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(252, 227, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-envelope"></i></div>
                <div class="info-details">
                    <div class="info-label">البريد الإلكتروني</div>
                    <div class="info-value">{{ $user->email }}</div>
                </div>
            </div>
            <button class="edit-btn" onclick="openEditModal('email')"><i class="fa-solid fa-pen"></i></button>
        </div>

        <!-- Birthdate -->
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(252, 227, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-calendar"></i></div>
                <div class="info-details">
                    <div class="info-label">تاريخ الميلاد</div>
                    <div class="info-value">{{ $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->format('d / m / Y') : 'غير محدد' }}</div>
                </div>
            </div>
            <button class="edit-btn" style="cursor: not-allowed; opacity: 0.5;" title="لا يمكن تعديل تاريخ الميلاد"><i class="fa-solid fa-lock"></i></button>
        </div>

        <!-- Gender -->
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(252, 227, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-venus-mars"></i></div>
                <div class="info-details">
                    <div class="info-label">الجنس</div>
                    <div class="info-value">{{ $user->gender ?? 'غير محدد' }}</div>
                </div>
            </div>
            <button class="edit-btn" style="cursor: not-allowed; opacity: 0.5;" title="لا يمكن تعديل الجنس"><i class="fa-solid fa-lock"></i></button>
        </div>
    </div>

    <!-- Action Rows -->
    <p class="section-heading"><i class="fa-solid fa-sliders"></i> إعدادات الحساب</p>
    <div class="action-rows">
        <button class="action-row" onclick="openPasswordModal()">
            <div class="action-row-inner">
                <div class="action-row-icon" style="background: rgba(252,227,0,0.15); color: var(--accent-color);">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div style="text-align: right;">
                    <div class="action-row-title">تغيير كلمة المرور</div>
                    <div class="action-row-desc">حماية إضافية للحساب وتأكيد بالبريد الإلكتروني</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-left" style="color: var(--text-secondary);"></i>
        </button>
    </div>

    <!-- Logout -->
    <form action="{{ route('parent.logout') }}" method="POST" style="margin-top: 1rem;">
        @csrf
        <button type="submit" class="logout-btn">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            تسجيل الخروج
        </button>
    </form>

</div>

<!-- Modals -->
<!-- Update Info Modal -->
<div class="modal-overlay" id="editInfoModal">
    <div class="modal-card">
        <h3 id="editModalTitle" style="margin-bottom: 1.5rem; font-weight:800; color:var(--text-primary);">تعديل البيانات</h3>
        <form id="profileUpdateForm" onsubmit="handleProfileSubmit(event)">
            <div id="phoneInputGroup" style="display: none; text-align: right; margin-bottom: 1.25rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--text-primary);">رقم الهاتف</label>
                <input type="text" id="edit_phone" class="form-control" value="{{ $user->phone ?? '' }}">
            </div>

            <div id="emailInputGroup" style="display: none; text-align: right; margin-bottom: 1.25rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--text-primary);">البريد الإلكتروني</label>
                <input type="email" id="edit_email" class="form-control" value="{{ $user->email ?? '' }}">
            </div>

            <div id="telegramInputGroup" style="text-align: right; margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--accent-color);">
                    <i class="fa-brands fa-telegram"></i> معرف حساب تيليغرام (لإرسال OTP)
                </label>
                <input type="text" id="edit_telegram" class="form-control" placeholder="مثال: @username أو معرف الحساب">
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

<!-- Update Password Modal -->
<div class="modal-overlay" id="editPasswordModal">
    <div class="modal-card">
        <h3 style="margin-bottom: 1.5rem; font-weight:800; color:var(--text-primary);">تغيير كلمة المرور</h3>
        <form id="password-form" onsubmit="handlePasswordSubmit(event)" style="text-align: right;">
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--text-primary);">كلمة المرور الحالية</label>
                <input type="password" id="current_password" required class="form-control" placeholder="••••••••">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--text-primary);">كلمة المرور الجديدة</label>
                <input type="password" id="new_password" required minlength="6" class="form-control" placeholder="••••••••">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--text-primary);">تأكيد كلمة المرور الجديدة</label>
                <input type="password" id="new_password_confirmation" required minlength="6" class="form-control" placeholder="••••••••">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700; color:var(--accent-color);">
                    <i class="fa-brands fa-telegram"></i> معرف حساب تيليغرام (لإرسال OTP)
                </label>
                <input type="text" id="password_telegram" class="form-control" placeholder="مثال: @username أو معرف الحساب">
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
    <div class="modal-card">
        <div class="modal-icon"><i class="fa-solid fa-paper-plane"></i></div>
        <h3 style="font-weight:800; color:var(--text-primary);">التحقق عبر تيليغرام (OTP)</h3>
        <p>تم إرسال رمز التحقق المكون من 6 أرقام إلى حسابك في بوت تيليغرام.</p>
        <div class="otp-input-row">
            <input class="otp-digit" type="text" maxlength="1">
            <input class="otp-digit" type="text" maxlength="1">
            <input class="otp-digit" type="text" maxlength="1">
            <input class="otp-digit" type="text" maxlength="1">
            <input class="otp-digit" type="text" maxlength="1">
            <input class="otp-digit" type="text" maxlength="1">
        </div>
        <div id="otp-error" style="color: #ef4444; font-size: 0.85rem; margin-bottom: 1rem; display: none;"></div>
        <button type="button" class="modal-confirm-btn" id="otp-btn" onclick="verifyOTP()">تأكيد و تحقق</button>
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
        document.getElementById('editInfoModal').classList.add('active');
        document.getElementById('phoneInputGroup').style.display = 'none';
        document.getElementById('emailInputGroup').style.display = 'none';
        document.getElementById('profile-error').style.display = 'none';
        document.getElementById('edit_telegram').value = '';

        if (field === 'phone') {
            document.getElementById('editModalTitle').innerText = 'تعديل رقم الهاتف';
            document.getElementById('phoneInputGroup').style.display = 'block';
        } else if (field === 'email') {
            document.getElementById('editModalTitle').innerText = 'تعديل البريد الإلكتروني';
            document.getElementById('emailInputGroup').style.display = 'block';
        }
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
        if (currentEditField === 'phone') payload.phone = document.getElementById('edit_phone').value;
        if (currentEditField === 'email') payload.email = document.getElementById('edit_email').value;
        
        payload.telegram_chat_id = document.getElementById('edit_telegram').value;

        const btn      = document.getElementById('profile-btn');
        const errorDiv = document.getElementById('profile-error');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري إرسال OTP...';
        errorDiv.style.display = 'none';

        fetch('{{ route("parent.profile.send_otp") }}', {
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

        fetch('{{ route("parent.profile.send_otp") }}', {
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
        const code = [...document.querySelectorAll('.otp-digit')].map(i => i.value).join('');
        if(code.length < 6) {
            alert('يرجى إدخال الرمز كاملاً (6 أرقام)');
            return;
        }

        const btn     = document.getElementById('otp-btn');
        const errorDiv= document.getElementById('otp-error');
        btn.disabled  = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري التحقق...';
        errorDiv.style.display = 'none';

        fetch("{{ route('parent.profile.verify_otp') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ otp: code })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                btn.disabled = false;
                btn.innerHTML = 'تأكيد و تحقق';
                errorDiv.innerText = data.message || 'رمز التحقق غير صحيح.';
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = 'تأكيد و تحقق';
            errorDiv.innerText = 'حدث خطأ أثناء التحقق.';
            errorDiv.style.display = 'block';
        });
    }

    // Auto-advance OTP inputs
    document.querySelectorAll('.otp-digit').forEach((input, index, inputs) => {
        input.addEventListener('input', () => {
            if(input.value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });
        input.addEventListener('keydown', (e) => {
            if(e.key === 'Backspace' && !input.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });
</script>
@endpush
