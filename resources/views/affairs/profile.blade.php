@extends('layouts.affairs')
@section('title', __('messages.profile'))

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
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
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
        inset-inline-end: 2rem;
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
        inset-inline-end: 2rem;
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
        text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
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
        text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
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
    .info-card:hover { transform: translateX({{ app()->getLocale() === 'ar' ? '-3px' : '3px' }}); }
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
    .info-details {
        text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
    }
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
        font-size: 1rem;
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
        text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
    }
    .action-row:hover { transform: translateX({{ app()->getLocale() === 'ar' ? '-3px' : '3px' }}); }
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

    /* OTP Modal */
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
</style>
@endpush

@section('content')
<div class="profile-page">
    
    <!-- Cover -->
    <div class="profile-cover">
        <div class="cover-pattern"></div>
        <div class="cover-accent"></div>
        <div class="profile-avatar-wrapper">
            <i class="fa-solid fa-user-tie"></i>
            <div class="avatar-overlay">
                <i class="fa-solid fa-camera"></i>
            </div>
        </div>
    </div>

    <!-- Name & Role -->
    <div class="profile-identity">
        <h2 class="profile-name">{{ $user->full_name }}</h2>
        <span class="profile-role-badge">{{ __('messages.student_affairs_officer') }}</span>
    </div>

    <!-- Stats -->
    <div class="profile-stats">
        <div class="stat-card">
            <span class="stat-value">{{ $reviewedLeaves }}</span>
            <span class="stat-label">{{ __('messages.processed_requests_stat') }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ $sentMessages }}</span>
            <span class="stat-label">{{ __('messages.sent_messages_stat') }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">{{ now()->year - ($user->created_at ? $user->created_at->year : now()->year) }}</span>
            <span class="stat-label">{{ __('messages.years_of_service') }}</span>
        </div>
    </div>

    <!-- Personal Info -->
    <p class="section-heading"><i class="fa-solid fa-circle-info"></i> {{ __('messages.personal_info') }}</p>
    <div class="info-list">
        <!-- Phone -->
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(252, 227, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-phone"></i></div>
                <div class="info-details">
                    <div class="info-label">{{ __('messages.phone') }}</div>
                    <div class="info-value" dir="ltr" style="text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">{{ $user->phone ?? __('messages.unspecified') }}</div>
                </div>
            </div>
            <button class="edit-btn" onclick="openEditModal('phone')"><i class="fa-solid fa-pen"></i></button>
        </div>

        <!-- Email -->
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(252, 227, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-envelope"></i></div>
                <div class="info-details">
                    <div class="info-label">{{ __('messages.email') }}</div>
                    <div class="info-value">{{ $user->email }}</div>
                </div>
            </div>
            <i class="fa-solid fa-lock" style="opacity: 0.4; font-size: 1rem; color: var(--text-secondary); margin-inline-start: 0.5rem;"></i>
        </div>

        <!-- Birthdate -->
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(252, 227, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-calendar"></i></div>
                <div class="info-details">
                    <div class="info-label">{{ __('messages.date_of_birth') }}</div>
                    <div class="info-value">{{ $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->format('d / m / Y') : __('messages.unspecified') }}</div>
                </div>
            </div>
            <div></div>
        </div>

        <!-- Department -->
        <div class="info-card">
            <div class="info-right">
                <div class="info-icon-wrapper" style="background: rgba(252, 227, 0, 0.15); color: var(--accent-color);"><i class="fa-solid fa-building"></i></div>
                <div class="info-details">
                    <div class="info-label">{{ __('messages.department') }}</div>
                    <div class="info-value">{{ __('messages.student_affairs') }}</div>
                </div>
            </div>
            <div></div>
        </div>
    </div>

    <!-- Action Rows -->
    <p class="section-heading"><i class="fa-solid fa-sliders"></i> {{ __('messages.account_settings') }}</p>
    <div class="action-rows">
        <button class="action-row" onclick="openPasswordModal()">
            <div class="action-row-inner">
                <div class="action-row-icon" style="background: rgba(252,227,0,0.15); color: var(--accent-color);">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div style="text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">
                    <div class="action-row-title">{{ __('messages.change_password') }}</div>
                    <div class="action-row-desc">{{ __('messages.password_security_desc') }}</div>
                </div>
            </div>
            <i class="fa-solid {{ app()->getLocale() === 'ar' ? 'fa-chevron-left' : 'fa-chevron-right' }}" style="color: var(--text-secondary);"></i>
        </button>

        <button class="action-row" onclick="window.location.href='/affairs/settings'">
            <div class="action-row-inner">
                <div class="action-row-icon" style="background: rgba(252,227,0,0.15); color: var(--accent-color);">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <div style="text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">
                    <div class="action-row-title">{{ __('messages.general_settings') }}</div>
                    <div class="action-row-desc">{{ __('messages.general_settings_desc') }}</div>
                </div>
            </div>
            <i class="fa-solid {{ app()->getLocale() === 'ar' ? 'fa-chevron-left' : 'fa-chevron-right' }}" style="color: var(--text-secondary);"></i>
        </button>
    </div>

    <!-- Logout -->
    <form action="{{ route('affairs.logout') }}" method="POST">
        @csrf
        <button type="submit" class="logout-btn">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            {{ __('messages.logout') }}
        </button>
    </form>

</div>

<!-- Modals -->
<!-- Update Info Modal -->
<div class="modal-overlay" id="editInfoModal">
    <div class="modal-card">
        <h3 id="modalTitle" style="margin-bottom: 1.5rem;">{{ __('messages.edit_data') }}</h3>
        <form id="profileUpdateForm" action="{{ route('affairs.profile.update') }}" method="POST">
            @csrf
            <input type="hidden" name="full_name" value="{{ $user->full_name }}">
            
            <div id="phoneInputGroup" style="display: none; text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700;">{{ __('messages.phone') }}</label>
                <input type="text" name="phone" class="form-control" value="{{ $user->phone }}" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-primary) !important; color: var(--text-primary) !important; -webkit-text-fill-color: var(--text-primary) !important; caret-color: var(--text-primary);">
            </div>

            <button type="submit" class="modal-confirm-btn">{{ __('messages.save_changes') }}</button>
            <button type="button" class="modal-cancel-btn" onclick="closeModals()">{{ __('messages.cancel') }}</button>
        </form>
    </div>
</div>

<!-- Update Password Modal -->
<div class="modal-overlay" id="editPasswordModal">
    <div class="modal-card">
        <h3 style="margin-bottom: 1.5rem;">{{ __('messages.change_password') }}</h3>
        <form action="{{ route('affairs.profile.password') }}" method="POST" style="text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700;">{{ __('messages.current_password') }}</label>
                <input type="password" name="current_password" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-primary) !important; color: var(--text-primary) !important; -webkit-text-fill-color: var(--text-primary) !important; caret-color: var(--text-primary);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700;">{{ __('messages.new_password') }}</label>
                <input type="password" name="password" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-primary) !important; color: var(--text-primary) !important; -webkit-text-fill-color: var(--text-primary) !important; caret-color: var(--text-primary);">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:700;">{{ __('messages.confirm_new_password') }}</label>
                <input type="password" name="password_confirmation" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-primary) !important; color: var(--text-primary) !important; -webkit-text-fill-color: var(--text-primary) !important; caret-color: var(--text-primary);">
            </div>

            <button type="submit" class="modal-confirm-btn">{{ __('messages.change_password') }}</button>
            <button type="button" class="modal-cancel-btn" onclick="closeModals()">{{ __('messages.cancel') }}</button>
        </form>
    </div>
</div>

<!-- OTP Modal -->
<div class="modal-overlay" id="otpModal">
    <div class="modal-card">
        <div class="modal-icon" style="width: 70px; height: 70px; background: rgba(252, 227, 0, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--accent-color); margin: 0 auto 1rem;"><i class="fa-solid fa-shield-halved"></i></div>
        <h3 style="font-size: 1.4rem; color: var(--text-primary); margin-bottom: 0.5rem;">{{ __('messages.otp_verification') }}</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">{{ __('messages.otp_desc') }}</p>
        <div style="display: flex; gap: 0.8rem; justify-content: center; margin-bottom: 1.5rem;">
            <input class="otp-digit" type="text" maxlength="1" style="width: 55px; height: 60px; border: 2px solid var(--border-color); border-radius: 0.75rem; text-align: center; font-size: 1.8rem; font-weight: 800; background: var(--bg-primary) !important; color: var(--text-primary) !important; -webkit-text-fill-color: var(--text-primary) !important; caret-color: var(--text-primary);">
            <input class="otp-digit" type="text" maxlength="1" style="width: 55px; height: 60px; border: 2px solid var(--border-color); border-radius: 0.75rem; text-align: center; font-size: 1.8rem; font-weight: 800; background: var(--bg-primary) !important; color: var(--text-primary) !important; -webkit-text-fill-color: var(--text-primary) !important; caret-color: var(--text-primary);">
            <input class="otp-digit" type="text" maxlength="1" style="width: 55px; height: 60px; border: 2px solid var(--border-color); border-radius: 0.75rem; text-align: center; font-size: 1.8rem; font-weight: 800; background: var(--bg-primary) !important; color: var(--text-primary) !important; -webkit-text-fill-color: var(--text-primary) !important; caret-color: var(--text-primary);">
            <input class="otp-digit" type="text" maxlength="1" style="width: 55px; height: 60px; border: 2px solid var(--border-color); border-radius: 0.75rem; text-align: center; font-size: 1.8rem; font-weight: 800; background: var(--bg-primary) !important; color: var(--text-primary) !important; -webkit-text-fill-color: var(--text-primary) !important; caret-color: var(--text-primary);">
        </div>
        <button type="button" class="modal-confirm-btn" onclick="submitProfileForm()">{{ __('messages.confirm_and_save') }}</button>
        <button type="button" class="modal-cancel-btn" onclick="closeOTPModal()">{{ __('messages.cancel') }}</button>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let pendingForm = null;

    function openEditModal(field) {
        document.getElementById('editInfoModal').classList.add('active');
        document.getElementById('phoneInputGroup').style.display = 'block';
        document.getElementById('modalTitle').innerText = '{{ __('messages.edit_phone') }}';
    }

    function openPasswordModal() {
        document.getElementById('editPasswordModal').classList.add('active');
    }

    function closeModals() {
        document.getElementById('editInfoModal').classList.remove('active');
        document.getElementById('editPasswordModal').classList.remove('active');
    }

    let otpBusy = false;

    document.getElementById('profileUpdateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        pendingForm = this;

        const csrfToken = pendingForm.querySelector('input[name="_token"]').value;
        const fullName  = pendingForm.querySelector('input[name="full_name"]').value;
        const phoneEl   = pendingForm.querySelector('input[name="phone"]');
        const phone     = phoneEl ? phoneEl.value : null;

        setOtpBusy(true);
        fetch(@json(route('affairs.profile.send_otp')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ full_name: fullName, phone: phone }),
        })
        .then(res => res.json())
        .then(data => {
            setOtpBusy(false);
            if (data.success) {
                closeModals();
                openOTPModal();
            } else {
                alert(data.message || '{{ __('messages.error') }}');
            }
        })
        .catch(() => {
            setOtpBusy(false);
            alert('{{ __('messages.error') }}');
        });
    });

    function setOtpBusy(busy) {
        otpBusy = busy;
        const btn = pendingForm ? pendingForm.querySelector('.modal-confirm-btn') : null;
        if (btn) btn.disabled = busy;
    }

    function openOTPModal() {
        document.getElementById('otpModal').classList.add('active');
        document.querySelector('.otp-digit').focus();
    }

    function closeOTPModal() {
        document.getElementById('otpModal').classList.remove('active');
        document.querySelectorAll('.otp-digit').forEach(i => i.value = '');
    }

    function submitProfileForm() {
        const code = [...document.querySelectorAll('.otp-digit')].map(i => i.value).join('');
        if (code.length < 4) {
            alert('{{ app()->getLocale() === 'ar' ? 'يرجى إدخال الرمز كاملاً (4 أرقام)' : 'Please enter the full 4-digit code' }}');
            return;
        }
        if (!pendingForm || otpBusy) return;

        const csrfToken = pendingForm.querySelector('input[name="_token"]').value;

        otpBusy = true;
        fetch(@json(route('affairs.profile.verify_otp')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ otp: code }),
        })
        .then(res => res.json())
        .then(data => {
            otpBusy = false;
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || '{{ app()->getLocale() === 'ar' ? 'رمز التحقق غير صحيح.' : 'Invalid verification code.' }}');
            }
        })
        .catch(() => {
            otpBusy = false;
            alert('{{ __('messages.error') }}');
        });
    }

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

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeModals();
            closeOTPModal();
        }
    });

    @if($errors->has('current_password') || $errors->has('password'))
        openPasswordModal();
    @endif
</script>
@endpush
