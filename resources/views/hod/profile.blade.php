@extends('layouts.hod')

@section('title', 'الملف الشخصي')

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
        right: 5px;
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
        padding-right: 0.5rem;
    }
    
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
        right: 20px;
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
        border-left: 4px solid var(--accent-color);
        transform: translateY(-100px);
        opacity: 0;
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        direction: rtl;
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
            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name ?? 'المستخدم') }}&background=random" class="avatar-large" alt="Profile">
            <div class="edit-avatar-btn">
                <i class="fa-solid fa-pen"></i>
            </div>
        </div>
        <h2 class="profile-name">{{ $user->full_name ?? 'رئيس قسم' }}</h2>
        <span class="profile-role">رئيس القسم الأكاديمي</span>
    </div>

    {{-- Section 1: معلومات التواصل --}}
    <div class="info-section">
        <h3 class="section-label">معلومات التواصل</h3>
        <div class="info-card">
            {{-- رقم الهاتف --}}
            <div class="info-item">
                <div class="item-content" style="width: 100%;">
                    <div class="item-icon icon-green"><i class="fa-solid fa-phone"></i></div>
                    <div class="item-details" style="flex: 1; margin-right: 1.5rem;">
                        <p>رقم الهاتف</p>
                        <div id="phone-display" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <h5 dir="ltr" style="text-align: right;">{{ $user->phone ?? 'غير متوفر' }}</h5>
                            <i class="fa-solid fa-pen edit-icon" onclick="toggleEdit('phone')"></i>
                        </div>
                        <form id="phone-form" action="{{ route('hod.profile.update') }}" method="POST" style="display: none; align-items: center; gap: 1rem; width: 100%; margin-top: 0.5rem;">
                            @csrf
                            <input type="text" name="phone" value="{{ $user->phone }}" class="form-control" style="flex: 1; padding: 0.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary);">
                            <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer;">حفظ</button>
                            <button type="button" class="btn" onclick="toggleEdit('phone')" style="background-color: var(--border-color); color: var(--text-primary); padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer;">إلغاء</button>
                        </form>
                    </div>
                </div>
            </div>

            <div style="height: 1px; background-color: var(--border-color); margin: 0 1rem;"></div>

            {{-- البريد الإلكتروني --}}
            <div class="info-item">
                <div class="item-content" style="width: 100%;">
                    <div class="item-icon icon-blue"><i class="fa-solid fa-envelope"></i></div>
                    <div class="item-details" style="flex: 1; margin-right: 1.5rem;">
                        <p>البريد الإلكتروني</p>
                        <div id="email-display" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <h5>{{ $user->email ?? 'email@example.com' }}</h5>
                            <i class="fa-solid fa-pen edit-icon" onclick="toggleEdit('email')"></i>
                        </div>
                        <form id="email-form" action="{{ route('hod.profile.update') }}" method="POST" style="display: none; align-items: center; gap: 1rem; width: 100%; margin-top: 0.5rem;">
                            @csrf
                            <input type="email" name="email" value="{{ $user->email }}" class="form-control" style="flex: 1; padding: 0.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary);" required>
                            <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer;">حفظ</button>
                            <button type="button" class="btn" onclick="toggleEdit('email')" style="background-color: var(--border-color); color: var(--text-primary); padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer;">إلغاء</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 2: المعلومات الشخصية --}}
    <div class="info-section">
        <h3 class="section-label">المعلومات الشخصية</h3>
        <div class="info-card">
            <div class="info-item">
                <div class="item-content">
                    <div class="item-icon icon-purple"><i class="fa-solid fa-building"></i></div>
                    <div class="item-details">
                        <p>القسم</p>
                        <h5>{{ $departmentName ?? 'غير محدد' }}</h5>
                    </div>
                </div>
                <i class="fa-solid fa-lock edit-icon" style="opacity: 0.4;"></i>
            </div>
        </div>
    </div>

    {{-- Section 3: الصلاحيات والنظام --}}
    <div class="info-section">
        <h3 class="section-label">الصلاحيات والنظام</h3>
        <div class="info-card">
            <div class="info-item">
                <div class="item-content">
                    <div class="item-icon icon-gold"><i class="fa-solid fa-user-shield"></i></div>
                    <div class="item-details">
                        <p>نوع الحساب</p>
                        <h5>رئيس القسم</h5>
                    </div>
                </div>
                <i class="fa-solid fa-lock edit-icon" style="opacity: 0.4;"></i>
            </div>

            <div style="height: 1px; background-color: var(--border-color); margin: 0 1rem;"></div>

            <div class="info-item">
                <div class="item-content">
                    <div class="item-icon icon-teal"><i class="fa-regular fa-clock"></i></div>
                    <div class="item-details">
                        <p>آخر تسجيل دخول</p>
                        <h5>{{ $user && $user->updated_at ? \Carbon\Carbon::parse($user->updated_at)->format('d/m/Y H:i') : 'غير متاح' }}</h5>
                    </div>
                </div>
                <i class="fa-solid fa-lock edit-icon" style="opacity: 0.4;"></i>
            </div>
        </div>
    </div>

    {{-- Section 4: الإعدادات والأمان --}}
    <div class="info-section">
        <h3 class="section-label">الإعدادات والأمان</h3>
        <div class="info-card">
            <div class="info-item">
                <div class="item-content" style="width: 100%;">
                    <div class="item-icon icon-red"><i class="fa-solid fa-lock"></i></div>
                    <div class="item-details" style="flex: 1; margin-right: 1.5rem;">
                        <p>كلمة المرور</p>
                        <div id="password-display" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <h5>••••••••</h5>
                            <button onclick="toggleEdit('password')" style="background-color: var(--border-color); color: var(--text-primary); border-radius: 1rem; padding: 0.25rem 1rem; font-size: 0.85rem; border: none; cursor: pointer; font-weight: 700;">تغيير</button>
                        </div>
                        <form id="password-form" action="{{ route('hod.profile.update') }}" method="POST" style="display: none; align-items: center; gap: 1rem; width: 100%; margin-top: 0.5rem;">
                            @csrf
                            <input type="password" name="password" placeholder="كلمة المرور الجديدة" class="form-control" style="flex: 1; padding: 0.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary);" minlength="6" required>
                            <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer;">حفظ</button>
                            <button type="button" class="btn" onclick="toggleEdit('password')" style="background-color: var(--border-color); color: var(--text-primary); padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer;">إلغاء</button>
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
            <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">رمز التحقق الثنائي (OTP)</h4>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">لقد أرسلنا رمز التحقق المكون من 4 أرقام عبر SMS إلى رقم هاتفك لتأكيد التغييرات.</p>
            
            <div class="otp-inputs">
                <input type="text" maxlength="1" class="otp-input-field" oninput="moveNext(this, 'otp-2')" id="otp-1">
                <input type="text" maxlength="1" class="otp-input-field" oninput="moveNext(this, 'otp-3')" id="otp-2">
                <input type="text" maxlength="1" class="otp-input-field" oninput="moveNext(this, 'otp-4')" id="otp-3">
                <input type="text" maxlength="1" class="otp-input-field" oninput="verifyInputOTP()" id="otp-4">
            </div>

            <button onclick="submitVerifyOTP()" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem; margin-bottom: 1rem;">تأكيد وحفظ</button>
            <button onclick="closeOTPModal()" class="btn" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); width: 100%; padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">إلغاء</button>
        </div>
    </div>

    <!-- SMS Mock Toast Notification -->
    <div id="sms-toast" class="sms-toast">
        <div class="sms-toast-icon">
            <i class="fa-solid fa-comment-sms"></i>
        </div>
        <div class="sms-toast-content">
            <h6>📬 رسالة SMS جديدة</h6>
            <p id="sms-text">رمز التحقق الخاص بك لتعديل الحساب هو: ....</p>
        </div>
    </div>

@endsection

@push('scripts')
<script>
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

    // اعتراض النماذج لإرسال OTP أولاً
    document.getElementById('phone-form').addEventListener('submit', function(e) {
        e.preventDefault();
        activeFormId = 'phone-form';
        sendOTPRequest({ phone: this.querySelector('input[name="phone"]').value });
    });

    document.getElementById('email-form').addEventListener('submit', function(e) {
        e.preventDefault();
        activeFormId = 'email-form';
        sendOTPRequest({ email: this.querySelector('input[name="email"]').value });
    });

    document.getElementById('password-form').addEventListener('submit', function(e) {
        e.preventDefault();
        activeFormId = 'password-form';
        sendOTPRequest({ password: this.querySelector('input[name="password"]').value });
    });

    function sendOTPRequest(data) {
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
                // إظهار توست الـ SMS الوهمية
                document.getElementById('sms-text').innerText = `رمز التحقق الخاص بك لتحديث الملف الشخصي هو: ${res.otp}`;
                const smsToast = document.getElementById('sms-toast');
                smsToast.classList.add('active');
                
                // إخفاء التوست تلقائياً بعد 8 ثواني
                setTimeout(() => {
                    smsToast.classList.remove('active');
                }, 8000);

                // إظهار مودال الـ OTP
                document.getElementById('otp-modal').classList.add('active');
                document.getElementById('otp-1').focus();
            }
        });
    }

    // التنقل التلقائي بين حقول الـ OTP
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
            alert('يرجى إدخال الرمز كاملاً');
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
                alert('تم تحديث البيانات بنجاح!');
                window.location.reload();
            } else {
                alert(res.message);
                // تفريغ الحقول وإعادة التركيز
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
