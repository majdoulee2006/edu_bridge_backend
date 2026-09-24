@extends('layouts.parent')
@section('title', 'الإعدادات')

@push('styles')
<style>
    .settings-page {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 1rem 3rem;
    }
    
    .settings-group {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 1.25rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
    }
    
    .settings-group-title {
        color: var(--text-secondary);
        font-size: 0.85rem;
        margin-bottom: 1.25rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .setting-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .setting-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .setting-label {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-weight: 600;
        font-size: 1rem;
        color: var(--text-primary);
    }
    
    .setting-label i {
        color: var(--text-secondary);
        width: 22px;
        text-align: center;
        font-size: 1.1rem;
    }

    /* Switch styling */
    .switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 26px;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--border-color);
        transition: .4s;
        border-radius: 34px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider {
        background-color: var(--accent-color);
    }
    input:checked + .slider:before {
        transform: translateX(22px);
        background-color: #1a1a1a;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.85rem 0;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.9rem;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        color: var(--text-secondary);
    }
    .info-value {
        font-weight: 600;
        color: var(--text-primary);
    }
</style>
@endpush

@section('content')
<div class="settings-page">

    <!-- Profile Summary Widget -->
    <div class="settings-group" style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--accent-color); color: #1a1a1a; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; box-shadow: 0 4px 15px rgba(252,227,0,0.3);">
                {{ mb_substr(Auth::user()->full_name ?? 'و', 0, 1) }}
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--text-primary);">{{ Auth::user()->full_name }}</h3>
                <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: var(--text-secondary);">حساب ولي أمر</p>
            </div>
        </div>
        <span style="background: rgba(252,227,0,0.15); color: var(--accent-color); padding: 0.3rem 0.85rem; border-radius: 2rem; font-size: 0.8rem; font-weight: 700;">
            نشط
        </span>
    </div>

    <!-- Appearance -->
    <div class="settings-group">
        <div class="settings-group-title">المظهر والعرض</div>
        
        <div class="setting-item">
            <div class="setting-label">
                <i class="fa-solid fa-moon"></i>
                الوضع الداكن
            </div>
            <label class="switch">
                <input type="checkbox" id="darkModeToggle" onchange="toggleDarkMode()">
                <span class="slider"></span>
            </label>
        </div>
    </div>

    <!-- Notifications -->
    <div class="settings-group">
        <div class="settings-group-title">الإشعارات والتنبيهات</div>
        
        <div class="setting-item">
            <div class="setting-label">
                <i class="fa-solid fa-bell"></i>
                تفعيل التنبيهات الفورية
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
        
        <div class="setting-item">
            <div class="setting-label">
                <i class="fa-solid fa-volume-high"></i>
                أصوات التنبيهات
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
    </div>

    <!-- System info -->
    <div class="settings-group">
        <div class="settings-group-title">معلومات النظام</div>
        <div class="info-row">
            <span class="info-label">إصدار النظام</span>
            <span class="info-value">EduBridge v1.0.2</span>
        </div>
        <div class="info-row">
            <span class="info-label">المنطقة الزمنية</span>
            <span class="info-value">{{ config('app.timezone') }}</span>
        </div>
    </div>

    <!-- Ultra-Premium About App & Privacy Showcase Cards -->
    <div class="settings-group" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 1.5rem; padding: 1.5rem; margin-top: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <span style="font-size: 0.85rem; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">الدعم والمعلومات والأمان</span>
            <span style="font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.65rem; border-radius: 9999px; background: rgba(242, 242, 13, 0.15); color: #eab308; border: 1px solid rgba(242, 242, 13, 0.3);">منظومة معتمدة</span>
        </div>

        <div style="margin-top: 1rem;">
            <!-- About Card -->
            <div onclick="openEduBridgeModal('tab-about')" style="background: var(--bg-primary, #1e1e1e); border: 1px solid var(--border-color); border-radius: 1.25rem; padding: 1.25rem; cursor: pointer; transition: all 0.25s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='#eab308';" onmouseout="this.style.transform='none'; this.style.borderColor='var(--border-color)';">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(242, 242, 13, 0.15); color: #eab308; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <span style="font-size: 0.8rem; font-weight: 700; color: #eab308; display: flex; align-items: center; gap: 0.35rem;">
                        استعراض <i class="fa-solid fa-arrow-left" style="font-size: 0.7rem;"></i>
                    </span>
                </div>
                <div style="margin-top: 1rem;">
                    <h4 style="margin: 0; font-size: 1rem; font-weight: 800; color: var(--text-primary);">حول المنصة وفريق التطوير</h4>
                    <p style="margin: 0.35rem 0 0; font-size: 0.82rem; color: var(--text-secondary); line-height: 1.5;">
                        معلومات نظام Edu-Bridge الأكاديمي، كادر الهندسة والبرمجة، سياسة الخصوصية، والتقنيات المعتمدة.
                    </p>
                </div>
                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-secondary);">
                    <span>5 مهندسين معتمدين</span>
                    <span style="color: #eab308; font-weight: 700;">منظومة معتمدة</span>
                </div>
            </div>
        </div>
    </div>

</div>

@include('partials.about_privacy_modal')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const darkToggle = document.getElementById('darkModeToggle');
        if (darkToggle) {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            darkToggle.checked = currentTheme === 'dark';
        }
    });
</script>
@endsection
