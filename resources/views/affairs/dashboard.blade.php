@extends('layouts.affairs')
@section('title', 'الرئيسية')
@section('subtitle', 'مرحباً، ' . (auth()->user()->full_name ?? 'أحمد محمد'))

@push('styles')
<style>
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        margin-top: 1.5rem;
    }
    .section-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    .view-all {
        color: var(--accent-color);
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none;
    }

    /* Carousel for Announcements */
    .announcements-carousel {
        display: flex;
        gap: 1rem;
        overflow-x: auto;
        padding-bottom: 1rem;
        scrollbar-width: none; /* Firefox */
    }
    .announcements-carousel::-webkit-scrollbar {
        display: none; /* Chrome */
    }
    .announcement-card {
        min-width: 280px;
        height: 160px;
        border-radius: 1rem;
        position: relative;
        overflow: hidden;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 1.25rem;
        box-shadow: var(--shadow);
    }
    .bg-gradient-yellow {
        background: linear-gradient(135deg, var(--accent-color) 0%, #ffd20080 100%);
    }
    .bg-gradient-dark {
        background: linear-gradient(135deg, #111827 0%, #374151 100%);
    }
    .badge-important {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: #ef4444;
        color: white;
        padding: 0.2rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .badge-update {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: var(--accent-color);
        color: white;
        padding: 0.2rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .announcement-card h3 {
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.4;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    }

    /* Posts */
    .post-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: var(--shadow);
    }
    .post-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .post-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #111827;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
    }
    .post-author {
        font-weight: 800;
        font-size: 0.95rem;
        color: var(--text-primary);
    }
    .post-time {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }
    .post-title {
        font-size: 1.1rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }
    .post-content {
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 1rem;
    }
    .post-image {
        width: 100%;
        height: 180px;
        border-radius: 1rem;
        object-fit: cover;
        margin-bottom: 1rem;
    }
    .post-actions {
        display: flex;
        justify-content: space-between;
        border-top: 1px solid var(--border-color);
        padding-top: 1rem;
    }
    .action-btn {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: color 0.2s;
    }
    .action-btn:hover {
        color: var(--accent-color);
    }
</style>
@endpush

@section('content')

<!-- Institution Announcements -->
<div class="section-header">
    <h2 class="section-title">إعلانات المؤسسة</h2>
    <a href="#" class="view-all">عرض الكل</a>
</div>

<div class="announcements-carousel">
    <div class="announcement-card bg-gradient-yellow">
        <span class="badge-important">هام</span>
        <h3>تحديث مواعيد الاختبارات النهائية للفصل الدراسي الأول</h3>
    </div>
    <div class="announcement-card bg-gradient-dark">
        <span class="badge-update">تحديثات</span>
        <h3>إطلاق النظام الإلكتروني الجديد لتسجيل الحضور والانصراف</h3>
    </div>
</div>

<!-- Administration Posts -->
<div class="section-header">
    <h2 class="section-title">منشورات الإدارة</h2>
</div>

<!-- Post 1 -->
<div class="post-card">
    <div class="post-header">
        <div class="post-avatar" style="background: var(--text-primary);"><i class="fa-solid fa-users-gear"></i></div>
        <div>
            <div class="post-author">شؤون الموظفين</div>
            <div class="post-time">منذ ساعتين</div>
        </div>
    </div>
    <h3 class="post-title">اجتماع الكادر الإداري الشهري</h3>
    <p class="post-content">
        يرجى من جميع موظفي الشؤون الحضور للاجتماع الشهري لمناقشة خطة العمل للفترة القادمة وتوزيع المهام الجديدة.
        القاعة رقم 4.
    </p>
    <!-- Placeholder image, usually you'd use asset('images/...') -->
    <div style="width: 100%; height: 180px; border-radius: 1rem; background: url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80') center/cover; margin-bottom: 1rem;"></div>
    
    <div class="post-actions">
        <button class="action-btn"><i class="fa-solid fa-thumbs-up"></i> أعجبني</button>
        <button class="action-btn"><i class="fa-solid fa-comment"></i> تعليق</button>
        <button class="action-btn"><i class="fa-solid fa-share-nodes"></i> مشاركة</button>
    </div>
</div>

<!-- Post 2 -->
<div class="post-card">
    <div class="post-header">
        <div class="post-avatar" style="background: #0f172a;"><i class="fa-solid fa-server"></i></div>
        <div>
            <div class="post-author">الدعم الفني</div>
            <div class="post-time">أمس</div>
        </div>
    </div>
    <h3 class="post-title">صيانة دورية للخوادم</h3>
    <p class="post-content">
        نحيطكم علماً بأنه سيتم إجراء صيانة دورية للخوادم يوم الجمعة القادم، مما قد يؤدي إلى انقطاع مؤقت في الخدمة.
    </p>
    
    <div class="post-actions">
        <button class="action-btn"><i class="fa-solid fa-thumbs-up"></i> أعجبني</button>
        <button class="action-btn"><i class="fa-solid fa-comment"></i> تعليق</button>
        <button class="action-btn"><i class="fa-solid fa-share-nodes"></i> مشاركة</button>
    </div>
</div>

@endsection
