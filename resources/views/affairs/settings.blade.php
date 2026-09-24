@extends('layouts.affairs')
@section('title', 'الإعدادات')

@push('styles')
<style>
    .settings-page { max-width: 800px; margin: 0 auto; padding: 0 1rem 3rem; }
    .settings-card {
        background: var(--card);
        border-radius: 1.5rem;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
    }
    .settings-card h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--border);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .settings-card h3 i { color: var(--primary); }
    .form-group { margin-bottom: 1.25rem; }
    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
    }
    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1.5px solid var(--border);
        border-radius: 0.75rem;
        background: var(--bg);
        color: var(--text);
        font-size: 0.9rem;
        transition: border-color 0.2s;
        outline: none;
    }
    .form-control:focus { border-color: var(--primary); }
    .btn-save {
        background: var(--primary);
        color: #000;
        border: none;
        border-radius: 0.75rem;
        padding: 0.75rem 2rem;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .btn-save:hover { opacity: 0.85; }
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border);
        font-size: 0.9rem;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: var(--text-muted); }
    .info-value { font-weight: 600; color: var(--text); }
    .badge-role {
        background: rgba(252,227,0,0.15);
        color: #b8a000;
        border-radius: 0.5rem;
        padding: 0.2rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="settings-page">

    {{-- معلومات الحساب --}}
    <div class="settings-card">
        <h3><i class="fas fa-user-circle"></i> معلومات الحساب</h3>
        <div class="info-row">
            <span class="info-label">الاسم الكامل</span>
            <span class="info-value">{{ Auth::user()->full_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">البريد الإلكتروني</span>
            <span class="info-value">{{ Auth::user()->email }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">اسم المستخدم</span>
            <span class="info-value">{{ Auth::user()->username }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الدور الوظيفي</span>
            <span class="info-value"><span class="badge-role">موظف الشؤون</span></span>
        </div>
        <div class="info-row">
            <span class="info-label">حالة الحساب</span>
            <span class="info-value" style="color:#22c55e;"><i class="fas fa-circle" style="font-size:0.6rem;margin-left:0.3rem;"></i> نشط</span>
        </div>
    </div>


    {{-- إعدادات النظام --}}
    <div class="settings-card">
        <h3><i class="fas fa-cog"></i> إعدادات النظام</h3>
        <div class="info-row">
            <span class="info-label">إصدار النظام</span>
            <span class="info-value">EduBridge v1.0.2</span>
        </div>

        <div class="info-row">
            <span class="info-label">المنطقة الزمنية</span>
            <span class="info-value">{{ config('app.timezone') }}</span>
        </div>
    </div>

    {{-- الدعم والمعلومات والأمان --}}
    <div class="settings-card" style="padding: 1.75rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="margin: 0; padding: 0; border: none; font-size: 1.15rem; font-weight: 800; color: var(--text);">
                <i class="fas fa-shield-alt" style="color: var(--primary);"></i> الدعم والمعلومات والأمان
            </h3>
            <span style="font-size: 0.78rem; font-weight: 700; background: rgba(255,204,0,0.15); color: #eab308; padding: 0.25rem 0.75rem; border-radius: 20px; border: 1px solid rgba(255,204,0,0.3);">
                منظومة معتمدة
            </span>
        </div>

        <div style="margin-top: 1rem;">
            <!-- About Card -->
            <div onclick="openEduBridgeModal('tab-about')" style="background: var(--bg); border: 1px solid var(--border); border-radius: 1.25rem; padding: 1.25rem; cursor: pointer; transition: all 0.25s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='var(--primary)';" onmouseout="this.style.transform='none'; this.style.borderColor='var(--border)';">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(255, 204, 0, 0.2); color: #eab308; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 0.35rem;">
                        استعراض <i class="fas fa-arrow-left" style="font-size: 0.7rem;"></i>
                    </span>
                </div>
                <div style="margin-top: 1rem;">
                    <h4 style="margin: 0; font-size: 1rem; font-weight: 800; color: var(--text);">حول المنصة وفريق التطوير</h4>
                    <p style="margin: 0.35rem 0 0; font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">
                        معلومات نظام Edu-Bridge الأكاديمي، كادر الهندسة والبرمجة، سياسة الخصوصية، والتقنيات المعتمدة.
                    </p>
                </div>
                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted);">
                    <span>5 مهندسين معتمدين</span>
                    <span style="color: #eab308; font-weight: 700;">منظومة معتمدة</span>
                </div>
            </div>
        </div>
    </div>

</div>

@include('partials.about_privacy_modal')
@endsection
