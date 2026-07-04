document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const darkModeToggle = document.getElementById('darkModeToggle');
    const langToggle = document.getElementById('langToggle');
    const fontSizeSlider = document.getElementById('fontSizeSlider');

    // Default Settings
    const defaultSettings = {
        theme: 'light',
        lang: 'ar',
        fontSize: '16'
    };

    // Load Settings from LocalStorage
    const loadSettings = () => {
        // Sync from admin's color-theme if present and hodSettings is missing/default
        const colorTheme = localStorage.getItem('color-theme');
        const rawHod = localStorage.getItem('hodSettings');
        if (colorTheme && !rawHod) {
            const s = Object.assign({}, defaultSettings, { theme: colorTheme });
            localStorage.setItem('hodSettings', JSON.stringify(s));
        } else if (colorTheme && rawHod) {
            const parsed = JSON.parse(rawHod);
            if (parsed.theme !== colorTheme) {
                parsed.theme = colorTheme;
                localStorage.setItem('hodSettings', JSON.stringify(parsed));
            }
        }

        const settings = JSON.parse(localStorage.getItem('hodSettings')) || defaultSettings;
        applySettings(settings);

        // Update UI elements if they exist on the page
        if (darkModeToggle) {
            darkModeToggle.checked = settings.theme === 'dark';
        }

        const icon = document.getElementById('dark-mode-icon');
        if (icon) {
            if (settings.theme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }
        if (langToggle) {
            // Depending on how you implement the toggle (checkbox or select)
            if(langToggle.type === 'checkbox') {
                 langToggle.checked = settings.lang === 'en';
            }
        }
        if (fontSizeSlider) {
            fontSizeSlider.value = settings.fontSize;
        }
    };

    // Apply Settings to the DOM
    const applySettings = (settings) => {
        // Theme
        document.documentElement.setAttribute('data-theme', settings.theme);
        
        // Language
        document.documentElement.setAttribute('dir', settings.lang === 'ar' ? 'rtl' : 'ltr');
        document.documentElement.setAttribute('lang', settings.lang);
        
        // Font Size
        document.documentElement.style.setProperty('--base-font-size', `${settings.fontSize}px`);
    };

    // Save Settings
    const saveSettings = (key, value) => {
        const settings = JSON.parse(localStorage.getItem('hodSettings')) || defaultSettings;
        settings[key] = value;
        localStorage.setItem('hodSettings', JSON.stringify(settings));
        // Sync with admin's color-theme key so all layouts share the preference
        if (key === 'theme') {
            localStorage.setItem('color-theme', value);
            if (value === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
            }
        }
        applySettings(settings);
    };

    // Event Listeners for UI controls
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', (e) => {
            saveSettings('theme', e.target.checked ? 'dark' : 'light');
        });
    }

    if (langToggle) {
        langToggle.addEventListener('change', (e) => {
            // Assuming checkbox: checked = EN, unchecked = AR
            saveSettings('lang', e.target.checked ? 'en' : 'ar');
        });
    }

    if (fontSizeSlider) {
        fontSizeSlider.addEventListener('input', (e) => {
            saveSettings('fontSize', e.target.value);
        });
    }

    // Initialize
    loadSettings();

    // Global function for button toggles
    window.toggleDarkMode = () => {
        const settings = JSON.parse(localStorage.getItem('hodSettings')) || defaultSettings;
        const newTheme = settings.theme === 'dark' ? 'light' : 'dark';
        saveSettings('theme', newTheme);

        const icon = document.getElementById('dark-mode-icon');
        if (icon) {
            if (newTheme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }
        
        if (darkModeToggle) {
            darkModeToggle.checked = newTheme === 'dark';
        }
    };

    // Global Language Toggle
    window.toggleLanguage = () => {
        const settings = JSON.parse(localStorage.getItem('hodSettings')) || defaultSettings;
        const newLang = settings.lang === 'ar' ? 'en' : 'ar';
        saveSettings('lang', newLang);
        updateLangUI(newLang);
        if (langToggle && langToggle.type === 'checkbox') {
            langToggle.checked = newLang === 'en';
        }
    };

    const updateLangUI = (lang) => {
        // Update header lang button text/icon
        const langBtnText = document.getElementById('lang-btn-text');
        if (langBtnText) {
            langBtnText.textContent = lang === 'ar' ? 'EN' : 'عر';
        }
        // Update settings page toggle if present
        const langSettingStatus = document.getElementById('lang-setting-status');
        if (langSettingStatus) {
            langSettingStatus.textContent = lang === 'ar' ? 'العربية' : 'English';
        }
        const langSettingToggle = document.getElementById('langSettingToggle');
        if (langSettingToggle) {
            langSettingToggle.checked = lang === 'en';
        }
    };

    // Call updateLangUI on load
    const settings = JSON.parse(localStorage.getItem('hodSettings')) || defaultSettings;
    updateLangUI(settings.lang);
});
