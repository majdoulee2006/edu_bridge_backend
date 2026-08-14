<!-- Pitch-Black Confirmation Modal for Logout -->
<div id="logoutConfirmModal" class="logout-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 99999; align-items: center; justify-content: center; font-family: 'Cairo', sans-serif;">
    <div style="background: #121212; border: 1px solid #262626; border-radius: 24px; padding: 2rem; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.9); animation: modalFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        <div style="width: 68px; height: 68px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; color: #ef4444; font-size: 1.8rem; box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </div>
        <h3 style="font-size: 1.25rem; font-weight: 800; color: #ffffff; margin-bottom: 0.5rem;">تأكيد تسجيل الخروج</h3>
        <p style="color: #a1a1aa; font-size: 0.92rem; font-weight: 600; line-height: 1.5; margin-bottom: 1.75rem;">هل أنت متأكد أنك تريد إنهاء الجلسة وتسجيل الخروج من حسابك؟</p>
        
        <div style="display: flex; gap: 0.75rem; justify-content: center;">
            <button type="button" onclick="closeGlobalLogoutModal()" style="flex: 1; padding: 0.85rem; border-radius: 14px; border: 1px solid #333; background: #1a1a1a; color: #e4e4e7; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: all 0.2s;">
                إلغاء وتراجع
            </button>
            <button type="button" onclick="submitGlobalLogoutForm()" style="flex: 1; padding: 0.85rem; border-radius: 14px; border: none; background: #ef4444; color: #ffffff; font-weight: 800; font-size: 0.95rem; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4);">
                نعم، خروج
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
        } else if (confirm('هل أنت متأكد من تسجيل الخروج؟')) {
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
