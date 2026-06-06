@extends('layouts.affairs')

@section('title', 'الإعدادات')

@push('styles')
<style>
    .profile-summary {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #e5e7eb;
        object-fit: cover;
    }
    
    .profile-info h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .profile-info p {
        color: var(--text-secondary);
    }
    
    .settings-group {
        background-color: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
    }
    
    .settings-group-title {
        color: var(--text-secondary);
        font-size: 0.875rem;
        margin-bottom: 1rem;
        font-weight: 600;
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
        font-size: 1.1rem;
    }
    
    .setting-label i {
        color: var(--text-secondary);
        width: 20px;
        text-align: center;
    }
    
    /* Range Slider for Font */
    .range-slider {
        -webkit-appearance: none;
        width: 150px;
        height: 8px;
        border-radius: 5px;
        background: var(--border-color);
        outline: none;
    }
    
    .range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: var(--accent-color);
        cursor: pointer;
    }
    
    .font-size-control {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    /* Custom Toggle */
    .toggle-container {
        display: flex;
        background-color: var(--border-color);
        border-radius: 2rem;
        padding: 0.25rem;
        position: relative;
    }
    
    .toggle-btn {
        padding: 0.5rem 1rem;
        border-radius: 1.5rem;
        border: none;
        background: transparent;
        color: var(--text-secondary);
        font-weight: 600;
        cursor: pointer;
        z-index: 1;
        transition: color 0.3s;
    }
    
    .toggle-btn.active {
        background-color: var(--bg-secondary);
        color: var(--text-primary);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>
@endpush

@section('content')

    <div class="card">
        <div class="profile-summary">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->full_name ?? 'موظف شؤون') }}&background=random" class="profile-avatar" alt="Avatar">
            <div class="profile-info">
                <h3>{{ Auth::user()->full_name ?? 'موظف شؤون' }}</h3>
                <p>موظف شؤون</p>
            </div>
        </div>
    </div>

    <div class="settings-group">
        <div class="settings-group-title">المظهر</div>
        
        <div class="setting-item">
            <div class="setting-label">حجم الخط</div>
            <div class="font-size-control">
                <span style="font-size: 0.9rem; color: var(--text-secondary);">صغير</span>
                <input type="range" min="14" max="22" value="16" class="range-slider" id="fontSizeSlider">
                <span style="font-size: 1.2rem; font-weight: bold; color: var(--text-secondary);">كبير</span>
            </div>
        </div>
        
        <div class="setting-item">
            <div class="setting-label">
                الوضع الداكن
                <i class="fa-solid fa-moon" style="margin-right: 0.5rem;"></i>
            </div>
            <label class="switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="slider"></span>
            </label>
        </div>
    </div>

    <div class="settings-group">
        <div class="settings-group-title">اللغة</div>
        <div class="setting-item">
            <div class="setting-label">
                لغة التطبيق
                <i class="fa-solid fa-globe" style="margin-right: 0.5rem;"></i>
            </div>
            <div class="toggle-container">
                <button class="toggle-btn" onclick="setLang('en')">EN</button>
                <button class="toggle-btn active" onclick="setLang('ar')">عربي</button>
            </div>
        </div>
    </div>

    <div class="settings-group">
        <div class="settings-group-title">الإشعارات</div>
        
        <div class="setting-item">
            <div class="setting-label">
                تفعيل الإشعارات
                <i class="fa-solid fa-bell" style="margin-right: 0.5rem;"></i>
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
        
        <div class="setting-item">
            <div class="setting-label">
                الأصوات
                <i class="fa-solid fa-volume-high" style="margin-right: 0.5rem;"></i>
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function setLang(lang) {
        const settings = JSON.parse(localStorage.getItem('affairsSettings')) || {};
        settings.lang = lang;
        localStorage.setItem('affairsSettings', JSON.stringify(settings));
        
        if (lang === 'en') {
            document.documentElement.setAttribute('dir', 'ltr');
            document.documentElement.setAttribute('lang', 'en');
            document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('button[onclick="setLang(\'en\')"]').classList.add('active');
        } else {
            document.documentElement.setAttribute('dir', 'rtl');
            document.documentElement.setAttribute('lang', 'ar');
            document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('button[onclick="setLang(\'ar\')"]').classList.add('active');
        }
    }
</script>
@endpush
