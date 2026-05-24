@extends('layouts.teacher')
@section('title', 'الملف الشخصي')

@push('styles')
<style>
    .info-row { background: var(--bg-secondary); border-radius: 1rem; padding: 1rem 1.25rem; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--shadow); }
    .info-icon { width: 40px; height: 40px; border-radius: 0.75rem; background: var(--bg-primary); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-secondary); flex-shrink: 0; }
    .form-input { width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; }
    .form-input:focus { outline: none; border-color: var(--accent-color); }
    .section-card { background: var(--bg-secondary); border-radius: 1.5rem; padding: 1.75rem; box-shadow: var(--shadow); margin-bottom: 1.5rem; position: relative; }
    
    /* Modals */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-card { background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); position: relative; }
    .close-btn { position: absolute; top: 1.5rem; left: 1.5rem; background: none; border: none; font-size: 1.2rem; color: var(--text-secondary); cursor: pointer; }
    .close-btn:hover { color: var(--text-primary); }

    .edit-btn { background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; }
    .edit-btn:hover { background: var(--accent-color); color: #1a1a1a; border-color: var(--accent-color); }
</style>
@endpush

@section('content')
    <div style="max-width: 800px; margin: 0 auto;">

        <!-- Profile Overview -->
        <div class="section-card" style="text-align: center; display: flex; flex-direction: column; align-items: center;">
            <!-- Avatar -->
            <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--accent-color); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; font-size: 3rem; font-weight: 800; color: #1a1a1a; border: 4px solid var(--bg-primary); box-shadow: 0 0 0 4px var(--accent-color);">
                {{ mb_substr($user->full_name, 0, 1) }}
            </div>
            <h2 style="font-weight: 800; font-size: 1.5rem;">{{ $user->full_name }}</h2>
            <p style="color: var(--text-secondary); font-size: 0.95rem; font-weight: 600; margin-top: 0.25rem;">أستاذ · {{ $teacher->specialization ?? 'غير محدد' }}</p>

            <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center;">
                @foreach($courses as $c)
                    <span style="background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); padding: 0.4rem 0.8rem; border-radius: 0.75rem; font-size: 0.85rem; font-weight: 700;">{{ $c->title }}</span>
                @endforeach
            </div>
        </div>

        <!-- Personal Info Display -->
        <div class="section-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <p style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary);">البيانات الشخصية</p>
                <button class="edit-btn" onclick="openModal('edit-profile-modal')" title="تعديل البيانات">
                    <i class="fa-solid fa-pen"></i>
                </button>
            </div>
            
            <div class="info-row">
                <div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.2rem;">الاسم الكامل</div>
                    <div style="font-weight: 700; font-size: 1rem;">{{ $user->full_name }}</div>
                </div>
                <div class="info-icon"><i class="fa-solid fa-user"></i></div>
            </div>

            <div class="info-row">
                <div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.2rem;">رقم الهاتف</div>
                    <div style="font-weight: 700; font-size: 1rem;" dir="ltr">{{ $user->phone ?? 'غير محدد' }}</div>
                </div>
                <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
            </div>

            <div class="info-row">
                <div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.2rem;">البريد الإلكتروني</div>
                    <div style="font-weight: 700; font-size: 1rem;" dir="ltr">{{ $user->email }}</div>
                </div>
                <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
            </div>
        </div>

        <!-- Password Display -->
        <div class="section-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <p style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary);">كلمة المرور</p>
                <button class="edit-btn" onclick="openModal('edit-password-modal')" title="تغيير كلمة المرور">
                    <i class="fa-solid fa-pen"></i>
                </button>
            </div>
            
            <div class="info-row">
                <div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.2rem;">كلمة المرور الحالية</div>
                    <div style="font-weight: 700; font-size: 1.2rem; letter-spacing: 2px;">••••••••</div>
                </div>
                <div class="info-icon"><i class="fa-solid fa-lock"></i></div>
            </div>
        </div>

    </div>

    <!-- Modals -->

    <!-- Edit Profile Modal -->
    <div id="edit-profile-modal" class="modal-overlay">
        <div class="modal-card">
            <button class="close-btn" onclick="closeModal('edit-profile-modal')"><i class="fa-solid fa-xmark"></i></button>
            <h3 style="font-weight: 800; margin-bottom: 1.5rem; font-size: 1.2rem;">تعديل البيانات الشخصية</h3>
            <form id="profile-form" onsubmit="handleProfileSubmit(event)">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">الاسم الكامل</label>
                    <input type="text" name="full_name" id="full_name" class="form-input" value="{{ $user->full_name }}" required>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">رقم الهاتف</label>
                    <input type="text" name="phone" id="phone" class="form-input" value="{{ $user->phone ?? '' }}">
                </div>
                <div id="profile-error" style="color: #ef4444; font-size: 0.85rem; margin-bottom: 1rem; display: none;"></div>
                <button type="submit" id="profile-btn" style="width: 100%; padding: 0.85rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-size: 1rem;">
                    <i class="fa-solid fa-save"></i> حفظ التعديلات
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Password Modal -->
    <div id="edit-password-modal" class="modal-overlay">
        <div class="modal-card">
            <button class="close-btn" onclick="closeModal('edit-password-modal')"><i class="fa-solid fa-xmark"></i></button>
            <h3 style="font-weight: 800; margin-bottom: 1.5rem; font-size: 1.2rem;">تغيير كلمة المرور</h3>
            <form id="password-form" onsubmit="handlePasswordSubmit(event)">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">كلمة المرور الحالية</label>
                    <input type="password" name="current_password" id="current_password" class="form-input" placeholder="••••••••" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">كلمة المرور الجديدة</label>
                    <input type="password" name="new_password" id="new_password" class="form-input" placeholder="••••••••" required minlength="6">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">تأكيد كلمة المرور</label>
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-input" placeholder="••••••••" required minlength="6">
                </div>
                <div id="password-error" style="color: #ef4444; font-size: 0.85rem; margin-bottom: 1rem; display: none;"></div>
                <button type="submit" id="password-btn" style="width: 100%; padding: 0.85rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-size: 1rem;">
                    <i class="fa-solid fa-key"></i> تغيير كلمة المرور
                </button>
            </form>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div id="otp-modal" class="modal-overlay">
        <div class="modal-card" style="text-align: center;">
            <button class="close-btn" onclick="closeModal('otp-modal')"><i class="fa-solid fa-xmark"></i></button>
            <div style="width: 60px; height: 60px; border-radius: 50%; background: #fefce8; color: #ca8a04; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h3 style="font-weight: 800; margin-bottom: 0.5rem; font-size: 1.2rem;">التحقق من الهوية</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">تم إرسال رمز تحقق (OTP) إلى هاتفك/بريدك. يرجى إدخاله للمتابعة.</p>
            
            <form id="otp-form" onsubmit="verifyOTP(event)">
                <div style="margin-bottom: 1.5rem;">
                    <input type="text" id="otp_code" class="form-input" placeholder="مثال: 1234" required style="text-align: center; font-size: 1.2rem; letter-spacing: 5px; font-weight: 800;" autocomplete="off" maxlength="4">
                </div>
                <div id="otp-error" style="color: #ef4444; font-size: 0.85rem; margin-bottom: 1rem; display: none;"></div>
                <button type="submit" id="otp-btn" style="width: 100%; padding: 0.85rem; background: #1a1a1a; color: #fff; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-size: 1rem;">
                    تأكيد الرمز
                </button>
            </form>

            <div id="demo-otp" style="margin-top: 1rem; padding: 0.75rem; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 700; display: none;">
                الرمز للتجربة: <span></span>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // إعداد توكن الحماية لطلبات AJAX
    const csrfToken = '{{ csrf_token() }}';

    function openModal(id) {
        document.getElementById(id).classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Reset errors
        document.getElementById('profile-error').style.display = 'none';
        document.getElementById('password-error').style.display = 'none';
        document.getElementById('otp-error').style.display = 'none';
        document.getElementById('demo-otp').style.display = 'none';
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });

    // Handle Profile Edit Submission
    function handleProfileSubmit(e) {
        e.preventDefault();
        const fullName = document.getElementById('full_name').value;
        const phone = document.getElementById('phone').value;
        const btn = document.getElementById('profile-btn');
        const errorDiv = document.getElementById('profile-error');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الإرسال...';
        errorDiv.style.display = 'none';

        fetch('{{ route("teacher.profile.send_otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ full_name: fullName, phone: phone })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save"></i> حفظ التعديلات';
            
            if(data.success) {
                closeModal('edit-profile-modal');
                showOTPModal(data.otp);
            } else {
                errorDiv.innerText = data.message || 'حدث خطأ غير متوقع';
                errorDiv.style.display = 'block';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save"></i> حفظ التعديلات';
            errorDiv.innerText = 'فشل الاتصال بالخادم';
            errorDiv.style.display = 'block';
        });
    }

    // Handle Password Change Submission
    function handlePasswordSubmit(e) {
        e.preventDefault();
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('new_password_confirmation').value;
        const btn = document.getElementById('password-btn');
        const errorDiv = document.getElementById('password-error');

        if(newPassword !== confirmPassword) {
            errorDiv.innerText = 'كلمة المرور الجديدة غير متطابقة';
            errorDiv.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الإرسال...';
        errorDiv.style.display = 'none';

        fetch('{{ route("teacher.profile.send_otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ 
                current_password: currentPassword,
                new_password: newPassword 
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-key"></i> تغيير كلمة المرور';
            
            if(data.success) {
                closeModal('edit-password-modal');
                document.getElementById('password-form').reset();
                showOTPModal(data.otp);
            } else {
                errorDiv.innerText = data.message || 'حدث خطأ غير متوقع';
                errorDiv.style.display = 'block';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-key"></i> تغيير كلمة المرور';
            errorDiv.innerText = 'فشل الاتصال بالخادم';
            errorDiv.style.display = 'block';
        });
    }

    function showOTPModal(otp) {
        openModal('otp-modal');
        document.getElementById('otp_code').value = '';
        
        // للعرض التجريبي فقط (كي نتمكن من اختباره)
        const demoDiv = document.getElementById('demo-otp');
        demoDiv.querySelector('span').innerText = otp;
        demoDiv.style.display = 'block';
    }

    function verifyOTP(e) {
        e.preventDefault();
        const otp = document.getElementById('otp_code').value;
        const btn = document.getElementById('otp-btn');
        const errorDiv = document.getElementById('otp-error');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري التحقق...';
        errorDiv.style.display = 'none';

        fetch('{{ route("teacher.profile.verify_otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ otp: otp })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // نجاح التحقق، نعيد تحميل الصفحة
                window.location.reload();
            } else {
                btn.disabled = false;
                btn.innerHTML = 'تأكيد الرمز';
                errorDiv.innerText = data.message || 'الرمز غير صحيح';
                errorDiv.style.display = 'block';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = 'تأكيد الرمز';
            errorDiv.innerText = 'فشل الاتصال بالخادم';
            errorDiv.style.display = 'block';
        });
    }
</script>
@endpush
