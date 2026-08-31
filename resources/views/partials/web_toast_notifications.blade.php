<style>
    /* Toast Notification Container */
    #web-toast-container {
        position: fixed;
        top: 1.5rem;
        left: 1.5rem;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        max-width: 380px;
        width: 90%;
        pointer-events: none;
    }

    /* Individual Toast Alert */
    .web-toast-item {
        pointer-events: auto;
        background: var(--bg-secondary, #ffffff);
        color: var(--text-primary, #1e293b);
        border: 1px solid var(--border-color, #e2e8f0);
        border-right: 5px solid var(--accent-color, #3b82f6);
        border-radius: 1rem;
        padding: 1rem 1.2rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        transform: translateX(-120%);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        cursor: pointer;
        font-family: inherit;
    }

    .web-toast-item.show {
        transform: translateX(0);
        opacity: 1;
    }

    .web-toast-icon {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(59, 130, 246, 0.12);
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .web-toast-content {
        flex: 1;
    }

    .web-toast-title {
        font-weight: 800;
        font-size: 0.95rem;
        margin-bottom: 0.25rem;
        color: var(--text-primary, #0f172a);
    }

    .web-toast-body {
        font-size: 0.85rem;
        color: var(--text-secondary, #64748b);
        line-height: 1.4;
    }

    .web-toast-close {
        background: transparent;
        border: none;
        color: var(--text-secondary, #94a3b8);
        font-size: 1rem;
        cursor: pointer;
        padding: 0.2rem;
        line-height: 1;
        transition: color 0.2s;
    }

    .web-toast-close:hover {
        color: #ef4444;
    }
</style>

<div id="web-toast-container"></div>

<script>
(function() {
    const seenNotifIds = new Set(JSON.parse(sessionStorage.getItem('seen_web_notif_ids') || '[]'));

    function getLinkForType(notif) {
        const path = window.location.pathname;
        let prefix = '/student';
        if (path.startsWith('/hod')) prefix = '/hod';
        else if (path.startsWith('/affairs')) prefix = '/affairs';
        else if (path.startsWith('/parent')) prefix = '/parent';
        else if (path.startsWith('/teacher')) prefix = '/teacher';
        else if (path.startsWith('/admin')) prefix = '/admin';

        const type = notif.type || '';
        const titleText = ((notif.title || '') + ' ' + (notif.message || notif.body || '')).toLowerCase();
        const isExam = titleText.includes('فحص') || titleText.includes('امتحان') || titleText.includes('اختبار') || type === 'exam';
        const isService = titleText.includes('خدمة') || titleText.includes('استرحام') || titleText.includes('وثيقة') || titleText.includes('إكمال') || type === 'student_service';

        if (isService) {
            if (prefix === '/student') return '/student/student-services';
            if (prefix === '/affairs') return '/affairs/student-services';
            if (prefix === '/hod') return '/hod/student-services';
            if (prefix === '/admin') return '/admin/student-services';
        }

        if (isExam) {
            if (prefix === '/student') return '/student/schedule#exams-section';
            if (prefix === '/parent') return '/parent/schedule#exams-section';
        }

        if (type === 'grade') return prefix === '/student' ? '/student/grades' : prefix + '/grades';
        if (type === 'leave_request' || type === 'leave') {
            if (prefix === '/student') return '/student/leave-requests';
            if (prefix === '/parent') return '/parent/permissions';
            return prefix + '/leaves';
        }
        if (type === 'message' || type === 'chat') return prefix + '/messages';
        if (type === 'assignment') return prefix === '/student' ? '/student/assignments' : prefix + '/assignments';
        return prefix + '/notifications';
    }

    function showWebToast(notif) {
        const container = document.getElementById('web-toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'web-toast-item';

        const titleText = ((notif.title || '') + ' ' + (notif.message || notif.body || '')).toLowerCase();
        const isExam = titleText.includes('فحص') || titleText.includes('امتحان') || titleText.includes('اختبار') || notif.type === 'exam';
        const isLeave = (notif.type === 'leave_request' || notif.type === 'leave');
        const iconClass = isExam ? 'fa-pencil' : (isLeave ? 'fa-calendar-check' : 'fa-bell');
        const targetUrl = getLinkForType(notif);

        toast.innerHTML = `
            <div class="web-toast-icon">
                <i class="fa-solid ${iconClass}"></i>
            </div>
            <div class="web-toast-content">
                <div class="web-toast-title">${escapeHtml(notif.title || 'إشعار جديد')}</div>
                <div class="web-toast-body">${escapeHtml(notif.message || notif.body || '')}</div>
            </div>
            <button class="web-toast-close" onclick="event.stopPropagation(); this.parentElement.remove();">&times;</button>
        `;

        toast.addEventListener('click', function() {
            window.location.href = targetUrl;
        });

        container.appendChild(toast);

        // Animate in
        setTimeout(() => toast.classList.add('show'), 50);

        // Auto remove after 6 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 6000);
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    function checkLatestNotifications() {
        fetch('/web-notifications/latest')
            .then(res => res.json())
            .then(data => {
                if (!data || !data.latest) return;

                let isFirstRun = seenNotifIds.size === 0;

                data.latest.forEach(n => {
                    if (!seenNotifIds.has(n.id)) {
                        seenNotifIds.add(n.id);
                        // Don't toast historic items on initial page load, only new ones arriving during session
                        if (!isFirstRun) {
                            showWebToast(n);
                        }
                    }
                });

                sessionStorage.setItem('seen_web_notif_ids', JSON.stringify(Array.from(seenNotifIds)));
            })
            .catch(() => {});
    }

    // Run check on load and interval
    document.addEventListener('DOMContentLoaded', function() {
        checkLatestNotifications();
        setInterval(checkLatestNotifications, 8000);
    });
})();
</script>
