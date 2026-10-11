@extends('layouts.hod')

@section('title', __('messages.profile'))

@push('styles')
<style>
    .profile-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 3rem;
    }
    
    .avatar-wrapper {
        position: relative;
        margin-bottom: 1rem;
    }
    
    .avatar-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid var(--bg-secondary);
        box-shadow: var(--shadow);
    }
    
    .edit-avatar-btn {
        position: absolute;
        bottom: 5px;
        background-color: var(--accent-color);
        color: #1a1a1a;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--bg-secondary);
        cursor: pointer;
    }
    [dir="rtl"] .edit-avatar-btn { right: 5px; left: auto; }
    [dir="ltr"] .edit-avatar-btn { left: 5px; right: auto; }
    
    .profile-name {
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }
    
    .profile-role {
        background-color: var(--border-color);
        padding: 0.25rem 1rem;
        border-radius: 1rem;
        font-size: 0.9rem;
        color: var(--text-secondary);
    }
    
    .info-section {
        margin-bottom: 2rem;
    }
    
    .section-label {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-secondary);
        margin-bottom: 1rem;
    }
    [dir="rtl"] .section-label { padding-right: 0.5rem; padding-left: 0; }
    [dir="ltr"] .section-label { padding-left: 0.5rem; padding-right: 0; }
    
    .info-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1rem;
        box-shadow: var(--shadow);
    }
    
    .info-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .item-content {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    
    .item-icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background-color: #fefce8;
        color: #ca8a04;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .icon-green  { background-color: #f0fdf4; color: #16a34a; }
    .icon-blue   { background-color: #eff6ff; color: #2563eb; }
    .icon-purple { background-color: #faf5ff; color: #9333ea; }
    .icon-gold   { background-color: #fefce8; color: #ca8a04; }
    .icon-teal   { background-color: #f0fdfa; color: #0d9488; }
    .icon-red    { background-color: #fef2f2; color: #dc2626; }
    
    .item-details p {
        color: var(--text-secondary);
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }
    
    .item-details h5 {
        font-size: 1.1rem;
        font-weight: 700;
    }

    [dir="rtl"] .item-details { margin-right: 1.5rem; margin-left: 0; }
    [dir="ltr"] .item-details { margin-left: 1.5rem; margin-right: 0; }
    
    .edit-icon {
        color: var(--text-secondary);
        cursor: pointer;
    }
    
    /* OTP Modal & SMS Mockup */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .modal-overlay.active {
        opacity: 1;
        pointer-events: auto;
    }
    .otp-modal-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 2.5rem;
        width: 90%;
        max-width: 400px;
        box-shadow: var(--shadow);
        text-align: center;
        transform: translateY(20px);
        transition: transform 0.3s ease;
    }
    .modal-overlay.active .otp-modal-card {
        transform: translateY(0);
    }
    .otp-inputs {
        display: flex;
        justify-content: center;
        gap: 0.75rem;
        margin: 1.5rem 0;
        direction: ltr;
    }
    .otp-input-field {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
        font-weight: 700;
        text-align: center;
        border: 2px solid var(--border-color);
        background-color: var(--bg-primary);
        color: var(--text-primary);
        border-radius: 0.75rem;
        outline: none;
        transition: border-color 0.3s;
    }
    .otp-input-field:focus {
        border-color: var(--accent-color);
    }
    
    /* SMS Premium Toast Notification */
    .sms-toast {
        position: fixed;
        top: 20px;
        background-color: #1e293b;
        color: #ffffff;
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        gap: 1rem;
        z-index: 2000;
        max-width: 350px;
        transform: translateY(-100px);
        opacity: 0;
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    }
    [dir="rtl"] .sms-toast {
        left: 20px;
        right: auto;
        border-right: 4px solid var(--accent-color);
        direction: rtl;
    }
    [dir="ltr"] .sms-toast {
        right: 20px;
        left: auto;
        border-left: 4px solid var(--accent-color);
        direction: ltr;
    }
    .sms-toast.active {
        transform: translateY(0);
        opacity: 1;
    }
    .sms-toast-icon {
        font-size: 1.5rem;
        color: var(--accent-color);
    }
    .sms-toast-content h6 {
        margin: 0 0 0.25rem 0;
        font-size: 0.95rem;
        font-weight: 700;
    }
    .sms-toast-content p {
        margin: 0;
        font-size: 0.85rem;
        color: #94a3b8;
    }
</style>
@endpush

@section('content')

    <div class="profile-header">
        <div class="avatar-wrapper">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name ?? __('messages.user')) }}&background=random" class="avatar-large" alt="Profile">
            <div class="edit-avatar-btn">
                <i class="fa-solid fa-pen"></i>
            </div>
        </div>
        <h2 class="profile-name">{{ $user->full_name ?? __('messages.academic_hod_role') }}</h2>
        <span class="profile-role">{{ __('messages.academic_hod_role') }}</span>
    </div>

    {{-- Section 1: Contact Info --}}
    <div class="info-section">
        <h3 class="section-label">{{ __('messages.contact_info') }}</h3>
        <div class="info-card">
            {{-- Phone Number --}}
            <div class="info-item">
                <div class="item-content" style="width: 100%;">
                    <div class="item-icon icon-green"><i class="fa-solid fa-phone"></i></div>
                    <div class="item-details" style="flex: 1;">
                        <p>{{ __('messages.phone_number') }}</p>
                        <div id="phone-display" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <h5 dir="ltr" style="text-align: start;">{{ $user->phone ?? __('messages.unspecified') }}</h5>
                            <i class="fa-solid fa-pen edit-icon" onclick="toggleEdit('phone')"></i>
                        </div>
                        <form id="phone-form" action="{{ route('hod.profile.update') }}" method="POST" style="display: none; align-items: center; gap: 1rem; width: 100%; margin-top: 0.5rem;">
                            @csrf
                            <input type="text" name="phone" value="{{ $user->phone }}" class="form-control" style="flex: 1; padding: 0.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary);">
                            <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer;">{{ __('messages.save') }}</button>
                            <button type="button" class="btn" onclick="toggleEdit('phone')" style="background-color: var(--border-color); color: var(--text-primary); padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer;">{{ __('messages.cancel') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div style="height: 1px; background-color: var(--border-color); margin: 0 1rem;"></div>

            {{-- Email Address --}}
            <div class="info-item">
                <div class="item-content" style="width: 100%;">
                    <div class="item-icon icon-blue"><i class="fa-solid fa-envelope"></i></div>
                    <div class="item-details" style="flex: 1;">
                        <p>{{ __('messages.email_address') }}</p>
                        <div id="email-display" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <h5 dir="ltr" style="text-align: start;">{{ $user->email ?? 'email@example.com' }}</h5>
                            <i class="fa-solid fa-lock edit-icon" style="opacity: 0.4;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 2: Personal Info --}}
    <div class="info-section">
        <h3 class="section-label">{{ __('messages.personal_info') }}</h3>
        <div class="info-card">
            <div class="info-item">
                <div class="item-content">
                    <div class="item-icon icon-purple"><i class="fa-solid fa-building"></i></div>
                    <div class="item-details">
                        <p>{{ __('messages.department') }}</p>
                        <h5>{{ $departmentName ?? __('messages.unspecified') }}</h5>
                    </div>
                </div>
                <i class="fa-solid fa-lock edit-icon" style="opacity: 0.4;"></i>
            </div>
        </div>
    </div>

    {{-- Section 3: Permissions & System --}}
    <div class="info-section">
        <h3 class="section-label">{{ __('messages.permissions_and_system') }}</h3>
        <div class="info-card">
            <div class="info-item">
                <div class="item-content">
                    <div class="item-icon icon-gold"><i class="fa-solid fa-user-shield"></i></div>
                    <div class="item-details">
                        <p>{{ __('messages.account_status') }}</p>
                        <h5>{{ __('messages.academic_hod_role') }}</h5>
                    </div>
                </div>
                <i class="fa-solid fa-lock edit-icon" style="opacity: 0.4;"></i>
            </div>

            <div style="height: 1px; background-color: var(--border-color); margin: 0 1rem;"></div>

            <div class="info-item">
                <div class="item-content">
                    <div class="item-icon icon-teal"><i class="fa-regular fa-clock"></i></div>
                    <div class="item-details">
                        <p>{{ __('messages.last_login') }}</p>
                        <h5 dir="ltr" style="text-align: start;">{{ $user && $user->updated_at ? \Carbon\Carbon::parse($user->updated_at)->format('d/m/Y H:i') : __('messages.unspecified') }}</h5>
                    </div>
                </div>
                <i class="fa-solid fa-lock edit-icon" style="opacity: 0.4;"></i>
            </div>
        </div>
    </div>

    {{-- Section 4: Security Settings --}}
    <div class="info-section">
        <h3 class="section-label">{{ __('messages.security_settings') }}</h3>
        <div class="info-card">
            <div class="info-item">
                <div class="item-content" style="width: 100%;">
                    <div class="item-icon icon-red"><i class="fa-solid fa-lock"></i></div>
                    <div class="item-details" style="flex: 1;">
                        <p>{{ __('messages.password') }}</p>
                        <div id="password-display" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <h5>••••••••</h5>
                            <button onclick="toggleEdit('password')" style="background-color: var(--border-color); color: var(--text-primary); border-radius: 1rem; padding: 0.25rem 1rem; font-size: 0.85rem; border: none; cursor: pointer; font-weight: 700;">{{ __('messages.change') }}</button>
                        </div>
                        <form id="password-form" action="{{ route('hod.profile.update') }}" method="POST" style="display: none; align-items: center; gap: 1rem; width: 100%; margin-top: 0.5rem;">
                            @csrf
                            <input type="password" name="password" placeholder="{{ __('messages.new_password') }}" class="form-control" style="flex: 1; padding: 0.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary);" minlength="6" required>
                            <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer;">{{ __('messages.save') }}</button>
                            <button type="button" class="btn" onclick="toggleEdit('password')" style="background-color: var(--border-color); color: var(--text-primary); padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer;">{{ __('messages.cancel') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Modal Overlay -->
    <div id="otp-modal" class="modal-overlay">
        <div class="otp-modal-card">
            <div style="font-size: 3rem; color: var(--accent-color); margin-bottom: 1rem;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">{{ __('messages.two_factor_otp') }}</h4>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">{{ __('messages.otp_desc') }}</p>
            
            <div class="otp-inputs">
                <input type="text" maxlength="1" class="otp-input-field" oninput="moveNext(this, 'otp-2')" id="otp-1">
                <input type="text" maxlength="1" class="otp-input-field" oninput="moveNext(this, 'otp-3')" id="otp-2">
                <input type="text" maxlength="1" class="otp-input-field" oninput="moveNext(this, 'otp-4')" id="otp-3">
                <input type="text" maxlength="1" class="otp-input-field" oninput="verifyInputOTP()" id="otp-4">
            </div>

            <button onclick="submitVerifyOTP()" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem; margin-bottom: 1rem;">{{ __('messages.confirm_and_save') }}</button>
            <button onclick="closeOTPModal()" class="btn" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); width: 100%; padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">{{ __('messages.cancel') }}</button>
        </div>
    </div>

    <!-- SMS Mock Toast Notification -->
    <div id="sms-toast" class="sms-toast">
        <div class="sms-toast-icon">
            <i class="fa-solid fa-comment-sms"></i>
        </div>
        <div class="sms-toast-content">
            <h6>{{ __('messages.sms_new_message') }}</h6>
            <p id="sms-text">{{ __('messages.sms_otp_text', ['otp' => '....']) }}</p>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    const profileStrings = {
        noInternet: "{{ __('messages.no_internet') }}",
        smsOtpTemplate: "{{ __('messages.sms_otp_text', ['otp' => ':otp']) }}",
        enterFullCode: "{{ __('messages.enter_full_code') }}",
        updatedSuccess: "{{ __('messages.data_updated_success') }}"
    };

    let activeFormId = null;

    function toggleEdit(field) {
        const displayDiv = document.getElementById(field + '-display');
        const formDiv = document.getElementById(field + '-form');
        
        if (displayDiv.style.display === 'none') {
            displayDiv.style.display = 'flex';
            formDiv.style.display = 'none';
        } else {
            displayDiv.style.display = 'none';
            formDiv.style.display = 'flex';
        }
    }

    // Intercept forms to request OTP first
    const phoneForm = document.getElementById('phone-form');
    if (phoneForm) {
        phoneForm.addEventListener('submit', function(e) {
            e.preventDefault();
            activeFormId = 'phone-form';
            sendOTPRequest({ phone: this.querySelector('input[name="phone"]').value });
        });
    }

    const emailForm = document.getElementById('email-form');
    if (emailForm) {
        emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            activeFormId = 'email-form';
            sendOTPRequest({ email: this.querySelector('input[name="email"]').value });
        });
    }

    const passwordForm = document.getElementById('password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            activeFormId = 'password-form';
            sendOTPRequest({ password: this.querySelector('input[name="password"]').value });
        });
    }

    function sendOTPRequest(data) {
        if (!navigator.onLine) {
            alert(profileStrings.noInternet);
            return;
        }

        fetch('{{ route("hod.profile.send_otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                document.getElementById('sms-text').innerText = profileStrings.smsOtpTemplate.replace(':otp', res.otp);
                const smsToast = document.getElementById('sms-toast');
                smsToast.classList.add('active');
                
                setTimeout(() => {
                    smsToast.classList.remove('active');
                }, 8000);

                document.getElementById('otp-modal').classList.add('active');
                document.getElementById('otp-1').focus();
            }
        });
    }

    function moveNext(current, nextId) {
        if (current.value.length === 1) {
            document.getElementById(nextId).focus();
        }
    }

    function verifyInputOTP() {
        const otp4 = document.getElementById('otp-4');
        if (otp4.value.length === 1) {
            submitVerifyOTP();
        }
    }

    function submitVerifyOTP() {
        const otp = 
            document.getElementById('otp-1').value +
            document.getElementById('otp-2').value +
            document.getElementById('otp-3').value +
            document.getElementById('otp-4').value;

        if (otp.length < 4) {
            alert(profileStrings.enterFullCode);
            return;
        }

        fetch('{{ route("hod.profile.verify_otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ otp: otp })
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                closeOTPModal();
                alert(profileStrings.updatedSuccess);
                window.location.reload();
            } else {
                alert(res.message);
                clearOTPInputs();
            }
        });
    }

    function clearOTPInputs() {
        for(let i=1; i<=4; i++) {
            document.getElementById('otp-' + i).value = '';
        }
        document.getElementById('otp-1').focus();
    }

    function closeOTPModal() {
        document.getElementById('otp-modal').classList.remove('active');
        document.getElementById('sms-toast').classList.remove('active');
        clearOTPInputs();
    }
</script>
@endpush
