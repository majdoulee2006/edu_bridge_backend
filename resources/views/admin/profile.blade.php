@extends('layouts.admin')

@section('title', __('messages.profile'))

@section('content')

{{-- ===== Cover ===== --}}
<div class="relative w-full mb-20">
    {{-- الخلفية مع overflow-hidden منفصلة --}}
    <div class="w-full h-48 rounded-3xl shadow-glow"
         style="background: linear-gradient(135deg, #080808 0%, #111827 50%, #1f2937 100%); border: 1px solid rgba(242,242,13,0.2); overflow:hidden; position:relative;">
        <div style="position:absolute;inset:0;background:repeating-linear-gradient(45deg,rgba(242,242,13,0.05) 0,rgba(242,242,13,0.05) 1px,transparent 1px,transparent 18px);"></div>
        <div style="position:absolute;bottom:0;right:3rem;width:180px;height:180px;border-radius:50%;opacity:0.1;filter:blur(50px);" class="bg-primary"></div>
        <div style="position:absolute;top:0;left:2rem;width:120px;height:120px;border-radius:50%;opacity:0.06;filter:blur(40px);" class="bg-primary"></div>
    </div>
    {{-- Avatar تظهر خارج الـ cover --}}
    <div class="absolute flex items-center justify-center text-4xl font-black bg-primary text-primary-content"
         style="width:100px;height:100px;border-radius:50%;bottom:-50px;{{ app()->getLocale() === 'en' ? 'left:2.5rem;' : 'right:2.5rem;' }}border:5px solid #111827;box-shadow:0 4px 20px rgba(0,0,0,0.35);">
        {{ mb_substr($user->full_name ?? 'A', 0, 1) }}
    </div>
</div>

{{-- ===== Name + Stats ===== --}}
<div class="flex items-start justify-between gap-4 mb-8 px-1">
    <div style="{{ app()->getLocale() === 'en' ? 'margin-left: 130px;' : 'margin-right: 130px;' }}">
        <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ $user->full_name }}</h2>
        <span class="inline-block mt-1 px-4 py-1 rounded-full text-sm font-bold bg-primary text-primary-content">{{ __('messages.system_admin') }}</span>
    </div>
    <div class="flex gap-3 flex-shrink-0">
        <div class="text-center px-5 py-3 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700">
            <span class="block text-2xl font-black text-primary">{{ $totalUsers }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">{{ __('messages.accounts_count') }}</span>
        </div>
        <div class="text-center px-5 py-3 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700">
            <span class="block text-2xl font-black text-primary">{{ $totalCourses }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">{{ __('messages.courses_count') }}</span>
        </div>
    </div>
</div>

{{-- ===== Main Grid ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ── المعلومات الشخصية ──────────────────── --}}
    <div class="flex flex-col gap-4">
        <h3 class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1 flex items-center gap-2">
            <span class="w-1 h-4 rounded-full inline-block bg-primary"></span>
            {{ __('messages.personal_information') }}
        </h3>

        {{-- الهاتف --}}
        <div class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700 hover:-translate-x-1 transition-transform">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full flex items-center justify-center bg-primary/10 text-primary">
                    <i class="fa-solid fa-phone"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-0.5">{{ __('messages.phone_number') }}</p>
                    <p class="font-bold text-slate-900 dark:text-white" dir="ltr" style="text-align:{{ app()->getLocale() === 'en' ? 'left' : 'right' }}">{{ $user->phone ?? __('messages.unspecified') }}</p>
                </div>
            </div>
            <button onclick="openEditModal('phone')" class="w-8 h-8 rounded-full flex items-center justify-center text-sm transition-colors text-primary hover:opacity-70" style="background:none; border:none; cursor:pointer;">
                <i class="fa-solid fa-pen"></i>
            </button>
        </div>

        {{-- البريد --}}
        <div class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700 hover:-translate-x-1 transition-transform">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full flex items-center justify-center bg-primary/10 text-primary">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-0.5">{{ __('messages.email') }}</p>
                    <p class="font-bold text-slate-900 dark:text-white">{{ $user->email }}</p>
                </div>
            </div>
            <i class="fa-solid fa-lock text-slate-400 opacity-60"></i>
        </div>
    </div>

    {{-- ── إعدادات الحساب ──────────────────── --}}
    <div class="flex flex-col gap-4">
        <h3 class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1 flex items-center gap-2">
            <span class="w-1 h-4 rounded-full inline-block bg-primary"></span>
            {{ __('messages.account_settings') }}
        </h3>

        {{-- تغيير كلمة المرور --}}
        <button onclick="openPasswordModal()"
                class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700 hover:-translate-x-1 transition-transform w-full text-right">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full flex items-center justify-center bg-primary/10 text-primary">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-900 dark:text-white">{{ __('messages.change_password') }}</p>
                    <p class="text-xs text-slate-400">{{ __('messages.account_protection_hint') }}</p>
                </div>
            </div>
            <i class="fa-solid {{ app()->getLocale() === 'en' ? 'fa-chevron-right' : 'fa-chevron-left' }} text-slate-400 text-sm"></i>
        </button>

        {{-- الإعدادات العامة --}}
        <button onclick="window.location.href='/admin/settings'"
                class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-800 shadow-soft border border-slate-100 dark:border-slate-700 hover:-translate-x-1 transition-transform w-full text-right">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full flex items-center justify-center bg-primary/10 text-primary">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-900 dark:text-white">{{ __('messages.settings') }}</p>
                    <p class="text-xs text-slate-400">{{ __('messages.customize_dashboard_settings') }}</p>
                </div>
            </div>
            <i class="fa-solid {{ app()->getLocale() === 'en' ? 'fa-chevron-right' : 'fa-chevron-left' }} text-slate-400 text-sm"></i>
        </button>
    </div>

</div>{{-- end grid --}}

{{-- ===== Modals ===== --}}
<div class="modal-overlay" id="editInfoModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;z-index:10000;backdrop-filter:blur(5px);">
<div style="background:#1e2d3d;border-radius:1.5rem;padding:2rem;width:90%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.4);">
    <h3 id="modalTitle" style="margin-bottom:1.5rem;font-weight:800;color:#f9fafb;font-size:1.2rem;">{{ __('messages.edit') }}</h3>
    <form id="profileUpdateForm" action="{{ route('admin.profile.update') }}" method="POST">
        @csrf
        <input type="hidden" name="full_name" value="{{ $user->full_name }}">
        <div id="phoneInputGroup" style="display:none;margin-bottom:1rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:700;color:#9ca3af;font-size:0.85rem;">{{ __('messages.phone_number') }}</label>
            <input type="text" name="phone" value="{{ $user->phone }}" style="width:100%;padding:0.8rem 1rem;border:1px solid #374151;border-radius:0.75rem;background:#1a2633;color:#f9fafb;outline:none;font-size:0.95rem;box-sizing:border-box;">
        </div>
        <button type="submit" class="w-full py-3 rounded-xl font-bold bg-primary text-primary-content shadow-glow mb-2">{{ __('messages.save_changes') }}</button>
        <button type="button" onclick="closeModals()" style="width:100%;padding:0.8rem;border-radius:0.75rem;border:none;background:transparent;color:#9ca3af;font-weight:700;cursor:pointer;">{{ __('messages.cancel') }}</button>
    </form>
</div>
</div>

<div class="modal-overlay" id="editPasswordModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;z-index:10000;backdrop-filter:blur(5px);">
<div style="background:#1e2d3d;border-radius:1.5rem;padding:2rem;width:90%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.4);">
    <h3 style="margin-bottom:1.5rem;font-weight:800;color:#f9fafb;font-size:1.2rem;">{{ __('messages.change_password') }}</h3>
    <form action="{{ route('admin.profile.password') }}" method="POST">
        @csrf
        <div style="margin-bottom:1rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:700;color:#9ca3af;font-size:0.85rem;">{{ __('messages.current_password') }}</label>
            <input type="password" name="current_password" required style="width:100%;padding:0.8rem 1rem;border:1px solid #374151;border-radius:0.75rem;background:#1a2633;color:#f9fafb;outline:none;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:1rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:700;color:#9ca3af;font-size:0.85rem;">{{ __('messages.new_password') }}</label>
            <input type="password" name="new_password" required style="width:100%;padding:0.8rem 1rem;border:1px solid #374151;border-radius:0.75rem;background:#1a2633;color:#f9fafb;outline:none;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:1.5rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:700;color:#9ca3af;font-size:0.85rem;">{{ __('messages.confirm_new_password') }}</label>
            <input type="password" name="new_password_confirmation" required style="width:100%;padding:0.8rem 1rem;border:1px solid #374151;border-radius:0.75rem;background:#1a2633;color:#f9fafb;outline:none;box-sizing:border-box;">
        </div>
        <button type="submit" class="w-full py-3 rounded-xl font-bold bg-primary text-primary-content shadow-glow mb-2">{{ __('messages.change_password') }}</button>
        <button type="button" onclick="closeModals()" style="width:100%;padding:0.8rem;border-radius:0.75rem;border:none;background:transparent;color:#9ca3af;font-weight:700;cursor:pointer;">{{ __('messages.cancel') }}</button>
    </form>
</div>
</div>

@endsection

@push('scripts')
<script>
    function openEditModal(field) {
        document.getElementById('phoneInputGroup').style.display = 'block';
        document.getElementById('modalTitle').innerText = @json(__('messages.phone_number'));
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

