@extends('layouts.hod')

@section('title', 'الإشعارات')

@push('styles')
<style>
    .notifications-group {
        margin-bottom: 2rem;
    }
    
    .group-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    
    .notification-card {
        background-color: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 1rem;
        display: flex;
        gap: 1.5rem;
        position: relative;
    }
    
    .notification-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .icon-blue { background-color: #e0f2fe; color: #0284c7; }
    .icon-purple { background-color: #f3e8ff; color: #9333ea; }
    .icon-orange { background-color: #ffedd5; color: #ea580c; }
    .icon-green { background-color: #dcfce7; color: #16a34a; }
    
    .notification-content {
        flex: 1;
    }
    
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }
    
    .notification-title {
        font-size: 1.1rem;
        font-weight: 700;
    }
    
    .notification-time {
        color: var(--text-secondary);
        font-size: 0.85rem;
    }
    
    .notification-text {
        color: var(--text-secondary);
        font-size: 0.95rem;
        line-height: 1.5;
        margin-bottom: 1rem;
    }
    
    .notification-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.4rem 1rem;
        border-radius: 1.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
    }
    
    .btn-approve-sm { background-color: var(--accent-color); color: #1a1a1a; }
    .btn-reject-sm { background-color: var(--bg-primary); color: var(--text-primary); }
</style>
@endpush

@section('content')

    <div class="notifications-group">
        <h3 class="group-title">اليوم</h3>
        
        <!-- Item 1 -->
        <div class="notification-card">
            <div class="notification-icon icon-blue"><i class="fa-solid fa-clock"></i></div>
            <div class="notification-content">
                <div class="notification-header">
                    <h4 class="notification-title">طلب مغادرة ساعية</h4>
                    <span class="notification-time">منذ 5 د</span>
                </div>
                <p class="notification-text">د. محمد الفهد يطلب مغادرة لمدة ساعتين لظروف خاصة.</p>
                <div class="notification-actions">
                    <button class="btn-sm btn-reject-sm">رفض</button>
                    <button class="btn-sm btn-approve-sm">موافقة</button>
                </div>
            </div>
        </div>

        <!-- Item 2 -->
        <div class="notification-card">
            <div class="notification-icon icon-purple"><i class="fa-regular fa-calendar-check"></i></div>
            <div class="notification-content">
                <div class="notification-header">
                    <h4 class="notification-title">طلب إجازة اعتيادية</h4>
                    <span class="notification-time">10:30 ص</span>
                </div>
                <p class="notification-text">أ. سارة العمر • لمدة 3 أيام<br>تبدأ من يوم الأحد القادم الموافق 25/10</p>
            </div>
        </div>

        <!-- Item 3 -->
        <div class="notification-card">
            <div class="notification-icon icon-orange"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="notification-content">
                <div class="notification-header">
                    <h4 class="notification-title">تنبيه إداري عاجل</h4>
                    <span class="notification-time">08:00 ص</span>
                </div>
                <p class="notification-text">يرجى الانتهاء من رصد درجات أعمال السنة قبل نهاية دوام اليوم.</p>
            </div>
        </div>
    </div>

    <div class="notifications-group">
        <h3 class="group-title">الأمس</h3>
        
        <!-- Item 4 -->
        <div class="notification-card">
            <div class="notification-icon icon-green"><i class="fa-solid fa-users"></i></div>
            <div class="notification-content">
                <div class="notification-header">
                    <h4 class="notification-title">اجتماع مجلس القسم</h4>
                    <span class="notification-time">01:00 م</span>
                </div>
                <p class="notification-text">تذكير بموعد الاجتماع الدوري في قاعة الاجتماعات الرئيسية.</p>
            </div>
        </div>
    </div>

@endsection
