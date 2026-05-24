@extends('layouts.affairs')
@section('title', 'الإعدادات')

@push('styles')
<style>
    .settings-section {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
    }
    .settings-title {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin-bottom: 1rem;
        text-align: right;
    }
    .settings-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--border-color);
    }
    .settings-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .settings-label {
        font-weight: 700;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    /* Toggle Switch */
    .custom-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 28px;
    }
    .custom-switch input { opacity: 0; width: 0; height: 0; }
    .custom-slider {
        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1; transition: .4s; border-radius: 34px;
    }
    .custom-slider:before {
        position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px;
        background-color: white; transition: .4s; border-radius: 50%;
    }
    .custom-switch input:checked + .custom-slider {
        background-color: #007BFF;
    }
    .custom-switch input:checked + .custom-slider:before {
        transform: translateX(22px);
    }

    /* Range Slider */
    .range-wrapper {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
    }
    .range-wrapper input[type=range] {
        flex: 1;
        -webkit-appearance: none;
        height: 4px;
        background: #e2e8f0;
        border-radius: 5px;
        outline: none;
    }
    .range-wrapper input[type=range]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #007BFF;
        cursor: pointer;
    }

    /* Lang Toggle */
    .lang-toggle {
        display: flex;
        background: #f1f5f9;
        border-radius: 20px;
        overflow: hidden;
    }
    .lang-btn {
        padding: 0.4rem 1rem;
        border: none;
        background: transparent;
        font-weight: 700;
        cursor: pointer;
    }
    .lang-btn.active {
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 20px;
    }
</style>
@endpush

@section('content')

<!-- Header Profile Info -->
<div class="settings-section" style="display: flex; align-items: center; gap: 1rem;">
    <div style="width: 50px; height: 50px; border-radius: 50%; background: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80') center/cover;"></div>
    <div>
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800;">أحمد محمد</h3>
        <p style="margin: 0; font-size: 0.8rem; color: var(--text-secondary);">موظف شؤون</p>
    </div>
</div>

<!-- Appearance -->
<div class="settings-section">
    <div class="settings-title">المظهر</div>
    
    <div class="settings-row" style="flex-direction: column; align-items: stretch;">
        <div style="text-align: right; font-weight: 700;">حجم الخط</div>
        <div class="range-wrapper">
            <span style="font-size: 0.8rem; color: var(--text-secondary);">صغير</span>
            <input type="range" min="1" max="3" value="2">
            <span style="font-size: 1.1rem; font-weight: 800; color: var(--text-secondary);">T<span style="font-size: 0.8rem;">T</span></span>
        </div>
        <div style="text-align: center; font-size: 0.8rem; color: #007BFF; margin-top: 0.5rem; font-weight: 700;">متوسط</div>
    </div>

    <div class="settings-row">
        <label class="custom-switch">
            <input type="checkbox" onchange="toggleDarkMode()" id="darkModeToggle">
            <span class="custom-slider"></span>
        </label>
        <div class="settings-label">
            الوضع الداكن <i class="fa-solid fa-moon" style="color: var(--text-secondary);"></i>
        </div>
    </div>
</div>

<!-- Language -->
<div class="settings-section">
    <div class="settings-title">اللغة</div>
    
    <div class="settings-row">
        <div class="lang-toggle">
            <button class="lang-btn">EN</button>
            <button class="lang-btn active">عربي</button>
        </div>
        <div class="settings-label">
            لغة التطبيق <i class="fa-solid fa-globe" style="color: var(--text-secondary);"></i>
        </div>
    </div>
</div>

<!-- Notifications -->
<div class="settings-section">
    <div class="settings-title">الإشعارات</div>
    
    <div class="settings-row">
        <label class="custom-switch">
            <input type="checkbox" checked>
            <span class="custom-slider"></span>
        </label>
        <div class="settings-label">
            تفعيل الإشعارات <i class="fa-solid fa-bell" style="color: var(--text-secondary);"></i>
        </div>
    </div>

    <div class="settings-row">
        <label class="custom-switch">
            <input type="checkbox" checked>
            <span class="custom-slider"></span>
        </label>
        <div class="settings-label">
            الأصوات <i class="fa-solid fa-volume-high" style="color: var(--text-secondary);"></i>
        </div>
    </div>

    <div class="settings-row">
        <label class="custom-switch">
            <input type="checkbox">
            <span class="custom-slider"></span>
        </label>
        <div class="settings-label">
            الاهتزاز <i class="fa-solid fa-mobile-screen-button" style="color: var(--text-secondary);"></i>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Sync toggle switch with actual dark mode
    window.addEventListener('DOMContentLoaded', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        document.getElementById('darkModeToggle').checked = isDark;
    });
</script>
@endpush
