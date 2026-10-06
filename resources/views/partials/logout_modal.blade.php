<!-- Confirmation Modal for Logout -->
<div id="logoutConfirmModal" class="logout-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 99999; align-items: center; justify-content: center; font-family: 'Cairo', sans-serif;">
    <div class="bg-white dark:bg-[#141417] border border-slate-200 dark:border-[#262626] rounded-3xl p-6 md:p-8 max-w-[400px] w-[90%] text-center shadow-2xl transition-all" style="animation: modalFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        <div class="w-16 h-16 bg-rose-500/10 border border-rose-500/20 rounded-full flex items-center justify-center mx-auto mb-4 text-rose-500 text-2xl shadow-sm">
            <i class="fa-solid fa-arrow-right-from-bracket {{ app()->getLocale() === 'en' ? 'rotate-180' : '' }}"></i>
        </div>
        <h3 class="text-lg font-extrabold text-slate-900 dark:text-white mb-2">{{ __('messages.confirm_logout') ?? 'تأكيد تسجيل الخروج' }}</h3>
        <p class="text-xs text-slate-500 dark:text-zinc-400 font-semibold leading-relaxed mb-6">{{ __('messages.logout_prompt') ?? 'هل أنت متأكد أنك تريد إنهاء الجلسة وتسجيل الخروج من حسابك؟' }}</p>
        
        <div class="flex gap-3 justify-center">
            <button type="button" onclick="closeGlobalLogoutModal()" class="flex-1 py-3 px-4 rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 font-bold text-xs cursor-pointer transition-all shadow-sm">
                {{ __('messages.cancel') ?? 'إلغاء وتراجع' }}
            </button>
            <button type="button" onclick="submitGlobalLogoutForm()" class="flex-1 py-3 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-black text-xs cursor-pointer transition-all shadow-md active:scale-95">
                {{ __('messages.logout') ?? 'نعم، خروج' }}
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
</style>

<script>
    let _activeLogoutForm = null;

    function triggerLogoutConfirmation(formElement) {
        _activeLogoutForm = formElement;
        const modal = document.getElementById('logoutConfirmModal');
        if (modal) {
            modal.style.display = 'flex';
        } else if (confirm(@json(__('messages.confirm_logout') ?? 'هل أنت متأكد من تسجيل الخروج؟'))) {
            formElement.submit();
        }
    }

    function closeGlobalLogoutModal() {
        const modal = document.getElementById('logoutConfirmModal');
        if (modal) modal.style.display = 'none';
        _activeLogoutForm = null;
    }

    function submitGlobalLogoutForm() {
        if (_activeLogoutForm) {
            _activeLogoutForm.submit();
        }
    }

    // Close on overlay click
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('logoutConfirmModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeGlobalLogoutModal();
            });
        }
    });
</script>
