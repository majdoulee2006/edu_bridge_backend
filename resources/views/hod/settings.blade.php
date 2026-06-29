@extends('layouts.hod')

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
            <img src="https://ui-avatars.com/api/?name=أحمد+محمد&background=random" class="profile-avatar" alt="Avatar">
            <div class="profile-info">
                <h3>أحمد محمد</h3>
                <p>رئيس قسم</p>
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
        <div class="settings-group-title">إعدادات الحضور والغياب</div>
        
        <form action="{{ route('hod.settings.attendance_policy') }}" method="POST" style="margin-top: 1rem;">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.5; margin-bottom: 0.5rem;">
                    اختر سياسة مزامنة الحضور للطلاب الذين يسجلون حضورهم بدون إنترنت (Offline Sync):
                </p>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="offline_sync_policy" value="anytime" 
                               {{ ($department && $department->offline_sync_policy === 'anytime') || !$department ? 'checked' : '' }}
                               style="margin-top: 0.25rem; scale: 1.2; accent-color: var(--accent-color);">
                        <div>
                            <span style="font-weight: 700; color: var(--text-primary);">مفتوح (في أي وقت)</span>
                            <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                                يتم قبول الحضور ومزامنته فور اتصال الطالب بالإنترنت، حتى بعد مضي عدة أيام على المحاضرة.
                            </p>
                        </div>
                    </label>
                    
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="offline_sync_policy" value="same_day" 
                               {{ $department && $department->offline_sync_policy === 'same_day' ? 'checked' : '' }}
                               style="margin-top: 0.25rem; scale: 1.2; accent-color: var(--accent-color);">
                        <div>
                            <span style="font-weight: 700; color: var(--text-primary);">محدد (نهاية نفس اليوم)</span>
                            <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                                يجب مزامنة الحضور قبل نهاية اليوم الدراسي الفعلي للمحاضرة (قبل الساعة 11:59 مساءً من نفس اليوم)، وإلا يعتبر الطالب غائباً بشكل نهائي.
                            </p>
                        </div>
                    </label>
                </div>
                
                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; padding: 0.6rem 1.5rem; border-radius: 0.5rem; border: none; font-weight: 700; cursor: pointer; transition: opacity 0.2s;">
                        حفظ الإعدادات
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="settings-group">
        <div class="settings-group-title">اللغة والمنطقة</div>

        <div class="setting-item">
            <div class="setting-label">
                <i class="fa-solid fa-globe"></i>
                اللغة
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span id="lang-setting-status" style="color: var(--text-secondary); font-size: 0.9rem;">العربية</span>
                <label class="switch">
                    <input type="checkbox" id="langSettingToggle" onchange="toggleLanguage()">
                    <span class="slider"></span>
                </label>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">EN</span>
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
</script>
@endpush
