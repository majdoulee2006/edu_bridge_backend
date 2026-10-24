/**
 * EduBridge Turbo Bridge & Enhancer
 * Provides smooth SPA-like transitions, CSRF integration, and event bridging across all dashboards.
 */
(function () {
    // 1. Ensure CSRF Token is present on all Turbo fetch requests
    document.addEventListener('turbo:before-fetch-request', function (event) {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            event.detail.fetchOptions.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
        }
    });

    // 2. Dispatch DOMContentLoaded on turbo:render so existing Blade page scripts re-initialize
    document.addEventListener('turbo:render', function () {
        try {
            document.dispatchEvent(new Event('DOMContentLoaded'));
        } catch (e) {
            // Ignore if browser restricts synthetic event
        }
    });

    // 3. Automatically bypass Turbo for file downloads, exports, and logout actions
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href) return;

        // Skip anchors, javascript calls, downloads, and exports
        if (
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            href.includes('/download') ||
            href.includes('/export') ||
            href.endsWith('.pdf') ||
            href.endsWith('.xlsx') ||
            href.endsWith('.csv') ||
            link.hasAttribute('download')
        ) {
            link.setAttribute('data-turbo', 'false');
        }
    }, true);

    // 4. Ensure logout forms never use Turbo to guarantee clean full-page session termination
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form) return;
        const action = form.getAttribute('action') || '';
        if (action.includes('logout')) {
            form.setAttribute('data-turbo', 'false');
        }
    }, true);
})();
