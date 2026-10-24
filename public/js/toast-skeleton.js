/**
 * EduBridge - Glassmorphic Toasts, Audio Chime Feedback & Skeleton Loaders
 * High-performance, offline-first micro-interactions.
 */
(function () {
    window.EduBridge = window.EduBridge || {};

    // ─── 1. Web Audio API Chime Synthesizer ──────────────────────────────────
    let audioCtx = null;

    function getAudioContext() {
        if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
            const AudioClass = window.AudioContext || window.webkitAudioContext;
            audioCtx = new AudioClass();
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    /**
     * Plays a soothing, luxury synthesized chime using Web Audio API.
     * Zero external audio files, 100% offline, crystal clear.
     */
    function playChime(type) {
        try {
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const masterGain = ctx.createGain();
            masterGain.gain.setValueAtTime(0.06, now);
            masterGain.connect(ctx.destination);

            if (type === 'success') {
                // Two-tone harmonic chime (C5 -> G5)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(523.25, now); // C5
                gain1.gain.setValueAtTime(0.08, now);
                gain1.gain.exponentialRampToValueAtTime(0.0001, now + 0.35);
                osc1.connect(gain1);
                gain1.connect(masterGain);
                osc1.start(now);
                osc1.stop(now + 0.35);

                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(783.99, now + 0.08); // G5
                gain2.gain.setValueAtTime(0.07, now + 0.08);
                gain2.gain.exponentialRampToValueAtTime(0.0001, now + 0.45);
                osc2.connect(gain2);
                gain2.connect(masterGain);
                osc2.start(now + 0.08);
                osc2.stop(now + 0.45);

            } else if (type === 'error') {
                // Soft double low tone
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(220, now);
                osc.frequency.linearRampToValueAtTime(170, now + 0.25);
                gain.gain.setValueAtTime(0.08, now);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.28);
                osc.connect(gain);
                gain.connect(masterGain);
                osc.start(now);
                osc.stop(now + 0.28);

            } else {
                // Info / Warning: Gentle subtle ping
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(659.25, now); // E5
                gain.gain.setValueAtTime(0.06, now);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.25);
                osc.connect(gain);
                gain.connect(masterGain);
                osc.start(now);
                osc.stop(now + 0.25);
            }
        } catch (e) {
            // Audio context not allowed until first user interaction in some browsers
        }
    }

    // ─── 2. Toast Notification Engine ────────────────────────────────────────
    const DEFAULT_ICONS = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };

    const DEFAULT_TITLES = {
        success: 'تم بنجاح',
        error: 'حدث خطأ',
        warning: 'تنبيه',
        info: 'معلومة'
    };

    function ensureContainer() {
        let container = document.getElementById('edubridge-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'edubridge-toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Display a floating glassmorphic toast notification.
     *
     * @param {string} message Text message to display
     * @param {string} [type='success'] 'success' | 'error' | 'warning' | 'info'
     * @param {string} [title] Optional custom title
     * @param {number} [duration=3800] Duration in milliseconds
     */
    EduBridge.toast = function (message, type = 'success', title = null, duration = 3800) {
        if (!message) return;

        const container = ensureContainer();
        const toast = document.createElement('div');
        toast.className = `edubridge-toast edubridge-toast-${type}`;

        const iconClass = DEFAULT_ICONS[type] || DEFAULT_ICONS.info;
        const toastTitle = title || DEFAULT_TITLES[type] || '';

        toast.innerHTML = `
            <div class="edubridge-toast-icon">
                <i class="fa-solid ${iconClass}"></i>
            </div>
            <div class="edubridge-toast-body">
                <div class="edubridge-toast-title">${toastTitle}</div>
                <div class="edubridge-toast-message">${message}</div>
            </div>
            <button type="button" class="edubridge-toast-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="edubridge-toast-progress">
                <div class="edubridge-toast-progress-bar" style="animation-duration: ${duration}ms;"></div>
            </div>
        `;

        container.appendChild(toast);

        // Play gentle audio feedback
        playChime(type);

        // Close on button click
        const closeBtn = toast.querySelector('.edubridge-toast-close');
        function closeToast() {
            toast.classList.add('toast-hiding');
            setTimeout(() => {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 280);
        }
        closeBtn.addEventListener('click', closeToast);

        // Auto close after duration
        let timeoutId = setTimeout(closeToast, duration);

        // Pause on hover
        toast.addEventListener('mouseenter', () => {
            clearTimeout(timeoutId);
            const pBar = toast.querySelector('.edubridge-toast-progress-bar');
            if (pBar) pBar.style.animationPlayState = 'paused';
        });

        toast.addEventListener('mouseleave', () => {
            const pBar = toast.querySelector('.edubridge-toast-progress-bar');
            if (pBar) pBar.style.animationPlayState = 'running';
            timeoutId = setTimeout(closeToast, 1500);
        });

        return toast;
    };

    // ─── 3. Skeleton Loaders Helper ──────────────────────────────────────────
    /**
     * Replace a container with shimmering skeleton cards.
     */
    EduBridge.renderSkeletonCards = function (containerSelector, count = 4) {
        const container = typeof containerSelector === 'string'
            ? document.querySelector(containerSelector)
            : containerSelector;
        if (!container) return;

        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-card">
                    <div class="skeleton-text skeleton-circle skeleton-avatar" style="margin-bottom: 12px;"></div>
                    <div class="skeleton-text short"></div>
                    <div class="skeleton-text medium"></div>
                </div>
            `;
        }
        container.innerHTML = html;
    };

    /**
     * Replace a table body with shimmering skeleton rows.
     */
    EduBridge.renderSkeletonTable = function (tbodySelector, rows = 5, cols = 5) {
        const tbody = typeof tbodySelector === 'string'
            ? document.querySelector(tbodySelector)
            : tbodySelector;
        if (!tbody) return;

        let html = '';
        for (let r = 0; r < rows; r++) {
            html += '<tr>';
            for (let c = 0; c < cols; c++) {
                const widthStyle = c === 0 ? 'width: 40px;' : (c === 1 ? 'width: 30%;' : '');
                html += `
                    <td class="p-3">
                        <div class="skeleton-text" style="${widthStyle}"></div>
                    </td>
                `;
            }
            html += '</tr>';
        }
        tbody.innerHTML = html;
    };

    // ─── 4. Auto-detect Laravel Flash Session Messages ───────────────────────
    function checkLaravelFlash() {
        // 1. Check data attributes on flash element if rendered
        const flashEl = document.getElementById('laravel-flash-data');
        if (flashEl) {
            const success = flashEl.getAttribute('data-success');
            const error = flashEl.getAttribute('data-error');
            const info = flashEl.getAttribute('data-info');
            const warning = flashEl.getAttribute('data-warning');

            if (success) EduBridge.toast(success, 'success');
            if (error) EduBridge.toast(error, 'error');
            if (info) EduBridge.toast(info, 'info');
            if (warning) EduBridge.toast(warning, 'warning');

            // Remove to prevent duplicate on re-render
            flashEl.remove();
        }
    }

    // Run on both DOMContentLoaded and Turbo Load
    document.addEventListener('DOMContentLoaded', checkLaravelFlash);
    document.addEventListener('turbo:load', checkLaravelFlash);

})();
