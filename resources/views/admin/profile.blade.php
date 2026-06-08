@extends('layouts.admin')

@section('title', 'الملف الشخصي')

@section('content')

{{-- ===== Cover ===== --}}
<div class="relative w-full mb-20">
    {{-- الخلفية مع overflow-hidden منفصلة --}}
    <div class="w-full h-48 rounded-3xl shadow-glow"
         style="background: linear-gradient(135deg, #080808 0%, #111827 50%, #1f2937 100%); border: 1px solid rgba(242,242,13,0.2); overflow:hidden; position:relative;">
        <div style="position:absolute;inset:0;background:repeating-linear-gradient(45deg,rgba(242,242,13,0.05) 0,rgba(242,242,13,0.05) 1px,transparent 1px,transparent 18px);"></div>
        <div style="position:absolute;bottom:0;right:3rem;width:180px;height:180px;border-radius:50%;background:#f2f20d;opacity:0.1;filter:blur(50px);"></div>
        <div style="position:absolute;top:0;left:2rem;width:120px;height:120px;border-radius:50%;background:#f2f20d;opacity:0.06;filter:blur(40px);"></div>
    </div>
    {{-- Avatar تظهر خارج الـ cover --}}
    <div class="absolute flex items-center justify-center text-4xl font-black"
         style="width:100px;height:100px;border-radius:50%;bottom:-50px;right:2.5rem;background:linear-gradient(135deg,#f2f20d,#d9d905);color:#101924;border:5px solid #111827;box-shadow:0 4px 20px rgba(242,242,13,0.35);">
        {{ mb_substr($user->full_name ?? 'م', 0, 1) }}
    </div>
</div>

{{-- ===== Name + Stats ===== --}}
<div class="flex items-start justify-between gap-4 mb-8 px-1">
    <div style="margin-right: 130px;">
        <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ $user->full_name }}</h2>
        <span class="inline-block mt-1 px-4 py-1 rounded-full text-sm font-bold" style="background:#f2f20d;color:#101924;">مدير النظام التعليمي</span>
    </div>
    <div class="flex gap-3 flex-shrink-0">
        <div class="text-center px-5 py-3 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700">
            <span class="block text-2xl font-black" style="color:#f2f20d;">{{ $totalUsers }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">الحسابات</span>
        </div>
        <div class="text-center px-5 py-3 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700">
            <span class="block text-2xl font-black" style="color:#f2f20d;">{{ $totalCourses }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">الدورات</span>
        </div>
    </div>
</div>

{{-- ===== Main Grid ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ── يمين: المعلومات الشخصية ──────────────────── --}}
    <div class="flex flex-col gap-4">
        <h3 class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1 flex items-center gap-2">
            <span class="w-1 h-4 rounded-full inline-block" style="background:#f2f20d;"></span>
            المعلومات الشخصية
        </h3>

        {{-- الهاتف --}}
        <div class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700 hover:-translate-x-1 transition-transform">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full flex items-center justify-center" style="background: rgba(242,242,13,0.12); color: #f2f20d;">
                    <i class="fa-solid fa-phone"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-0.5">رقم الهاتف</p>
                    <p class="font-bold text-slate-900 dark:text-white" dir="ltr" style="text-align:right">{{ $user->phone ?? 'غير محدد' }}</p>
                </div>
            </div>
            <button onclick="openEditModal('phone')" class="w-8 h-8 rounded-full flex items-center justify-center text-sm transition-colors hover:opacity-70" style="color:#f2f20d; background:none; border:none; cursor:pointer;">
                <i class="fa-solid fa-pen"></i>
            </button>
        </div>

        {{-- البريد --}}
        <div class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700 hover:-translate-x-1 transition-transform">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full flex items-center justify-center" style="background: rgba(242,242,13,0.12); color: #f2f20d;">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-0.5">البريد الإلكتروني</p>
                    <p class="font-bold text-slate-900 dark:text-white">{{ $user->email }}</p>
                </div>
            </div>
            <button onclick="openEditModal('email')" class="w-8 h-8 rounded-full flex items-center justify-center text-sm transition-colors hover:opacity-70" style="color:#f2f20d; background:none; border:none; cursor:pointer;">
                <i class="fa-solid fa-pen"></i>
            </button>
        </div>
    </div>

    {{-- ── يسار: إعدادات الحساب ──────────────────── --}}
    <div class="flex flex-col gap-4">
        <h3 class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1 flex items-center gap-2">
            <span class="w-1 h-4 rounded-full inline-block" style="background:#f2f20d;"></span>
            إعدادات الحساب
        </h3>

        {{-- تغيير كلمة المرور --}}
        <button onclick="openPasswordModal()"
                class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700 hover:-translate-x-1 transition-transform w-full text-right">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full flex items-center justify-center" style="background: rgba(242,242,13,0.12); color: #f2f20d;">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-900 dark:text-white">تغيير كلمة المرور</p>
                    <p class="text-xs text-slate-400">حماية إضافية للحساب</p>
                </div>
            </div>
            <i class="fa-solid fa-chevron-left text-slate-400 text-sm"></i>
        </button>

        {{-- الإعدادات العامة --}}
        <button onclick="window.location.href='/admin/settings'"
                class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700 hover:-translate-x-1 transition-transform w-full text-right">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full flex items-center justify-center" style="background: rgba(242,242,13,0.12); color: #f2f20d;">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-900 dark:text-white">الإعدادات العامة</p>
                    <p class="text-xs text-slate-400">المظهر، الإشعارات، اللغة</p>
                </div>
            </div>
            <i class="fa-solid fa-chevron-left text-slate-400 text-sm"></i>
        </button>
    </div>

</div>{{-- end grid --}}

{{-- ===== Modals ===== --}}
<div class="modal-overlay" id="editInfoModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;z-index:10000;backdrop-filter:blur(5px);">
<div style="background:#1e2d3d;border-radius:1.5rem;padding:2rem;width:90%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.4);">
    <h3 id="modalTitle" style="margin-bottom:1.5rem;font-weight:800;color:#f9fafb;font-size:1.2rem;">تعديل البيانات</h3>
    <form id="profileUpdateForm" action="{{ route('admin.profile.update') }}" method="POST">
        @csrf
        <input type="hidden" name="full_name" value="{{ $user->full_name }}">
        <div id="phoneInputGroup" style="display:none;margin-bottom:1rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:700;color:#9ca3af;font-size:0.85rem;">رقم الهاتف</label>
            <input type="text" name="phone" value="{{ $user->phone }}" style="width:100%;padding:0.8rem 1rem;border:1px solid #374151;border-radius:0.75rem;background:#1a2633;color:#f9fafb;outline:none;font-size:0.95rem;box-sizing:border-box;">
        </div>
        <div id="emailInputGroup" style="display:none;margin-bottom:1rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:700;color:#9ca3af;font-size:0.85rem;">البريد الإلكتروني</label>
            <input type="email" name="email" value="{{ $user->email }}" style="width:100%;padding:0.8rem 1rem;border:1px solid #374151;border-radius:0.75rem;background:#1a2633;color:#f9fafb;outline:none;font-size:0.95rem;box-sizing:border-box;">
        </div>
        <button type="submit" style="width:100%;padding:0.9rem;border-radius:0.75rem;border:none;background:#f2f20d;color:#101924;font-weight:800;font-size:1rem;cursor:pointer;margin-bottom:0.5rem;">حفظ التغييرات</button>
        <button type="button" onclick="closeModals()" style="width:100%;padding:0.8rem;border-radius:0.75rem;border:none;background:transparent;color:#9ca3af;font-weight:700;cursor:pointer;">إلغاء</button>
    </form>
</div>
</div>

<div class="modal-overlay" id="editPasswordModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;z-index:10000;backdrop-filter:blur(5px);">
<div style="background:#1e2d3d;border-radius:1.5rem;padding:2rem;width:90%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.4);">
    <h3 style="margin-bottom:1.5rem;font-weight:800;color:#f9fafb;font-size:1.2rem;">تغيير كلمة المرور</h3>
    <form action="{{ route('admin.profile.password') }}" method="POST">
        @csrf
        <div style="margin-bottom:1rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:700;color:#9ca3af;font-size:0.85rem;">كلمة المرور الحالية</label>
            <input type="password" name="current_password" required style="width:100%;padding:0.8rem 1rem;border:1px solid #374151;border-radius:0.75rem;background:#1a2633;color:#f9fafb;outline:none;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:1rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:700;color:#9ca3af;font-size:0.85rem;">كلمة المرور الجديدة</label>
            <input type="password" name="new_password" required style="width:100%;padding:0.8rem 1rem;border:1px solid #374151;border-radius:0.75rem;background:#1a2633;color:#f9fafb;outline:none;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:1.5rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:700;color:#9ca3af;font-size:0.85rem;">تأكيد كلمة المرور</label>
            <input type="password" name="new_password_confirmation" required style="width:100%;padding:0.8rem 1rem;border:1px solid #374151;border-radius:0.75rem;background:#1a2633;color:#f9fafb;outline:none;box-sizing:border-box;">
        </div>
        <button type="submit" style="width:100%;padding:0.9rem;border-radius:0.75rem;border:none;background:#f2f20d;color:#101924;font-weight:800;font-size:1rem;cursor:pointer;margin-bottom:0.5rem;">تغيير كلمة المرور</button>
        <button type="button" onclick="closeModals()" style="width:100%;padding:0.8rem;border-radius:0.75rem;border:none;background:transparent;color:#9ca3af;font-weight:700;cursor:pointer;">إلغاء</button>
    </form>
</div>
</div>

@endsection

@push('scripts')
<script>
    function openEditModal(field) {
        document.getElementById('phoneInputGroup').style.display = 'none';
        document.getElementById('emailInputGroup').style.display = 'none';
        if (field === 'phone') {
            document.getElementById('modalTitle').innerText = 'تعديل رقم الهاتف';
            document.getElementById('phoneInputGroup').style.display = 'block';
        } else {
            document.getElementById('modalTitle').innerText = 'تعديل البريد الإلكتروني';
            document.getElementById('emailInputGroup').style.display = 'block';
        }
        const m = document.getElementById('editInfoModal');
        m.style.display = 'flex';
    }
    function openPasswordModal() {
        document.getElementById('editPasswordModal').style.display = 'flex';
    }
    function closeModals() {
        document.getElementById('editInfoModal').style.display = 'none';
        document.getElementById('editPasswordModal').style.display = 'none';
    }
    window.addEventListener('click', e => {
        if (e.target.id === 'editInfoModal' || e.target.id === 'editPasswordModal') closeModals();
    });

    @if($errors->has('current_password') || $errors->has('password'))
        openPasswordModal();
    @endif
</script>
@endpush
