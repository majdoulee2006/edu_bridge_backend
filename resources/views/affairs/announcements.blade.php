@extends('layouts.affairs')
@section('title', 'الإعلانات والمناشير')

@push('styles')
<style>
    .announcements-container {
        max-width: 900px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .page-header h2 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    .page-header h2 i {
        color: var(--accent-color);
        font-size: 1.5rem;
    }
    .back-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.6rem 1.2rem;
        border-radius: 0.8rem;
        font-weight: 700;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: var(--shadow);
    }
    .back-btn:hover {
        background: var(--accent-color);
        color: var(--primary-dark);
        border-color: var(--accent-color);
        transform: translateX(3px);
    }
    .back-btn i { font-size: 0.85rem; }

    .filters {
        display: flex;
        gap: 0.8rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
    }
    .filter-btn {
        background: none;
        color: var(--text-secondary);
        border: none;
        padding: 0.5rem 1rem;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        position: relative;
        transition: color 0.2s;
    }
    .filter-btn:hover { color: var(--text-primary); }
    .filter-btn.active { color: var(--text-primary); }
    .filter-btn.active::after {
        content: '';
        position: absolute;
        bottom: -1rem;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--accent-color);
        border-radius: 3px 3px 0 0;
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
        color: var(--text-primary);
    }
    .post-content {
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.6;
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
<div class="announcements-container">
    
    <div class="page-header">
        <h2><i class="fa-solid fa-bullhorn"></i> الإعلانات والمناشير</h2>
        <a href="{{ route('affairs.dashboard') }}" class="back-btn">
            <i class="fa-solid fa-arrow-right"></i>
            الرجوع للرئيسية
        </a>
    </div>

    <div class="filters">
        <button class="filter-btn active" onclick="filterPosts('all')">الكل</button>
        <button class="filter-btn" onclick="filterPosts('announcement')">إعلانات المؤسسة</button>
        <button class="filter-btn" onclick="filterPosts('post')">منشورات الإدارة</button>
    </div>

    <div class="posts-list">
        
        <!-- إعلان كبير 1 -->
        <div class="post-card announce-card-large" data-type="announcement" style="padding:0; background:transparent;">
            <div class="announce-large-header">
                <div class="announce-large-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <span class="announce-badge">إعلان هام</span>
            </div>
            <div class="announce-large-body" style="background: var(--bg-secondary);">
                <div class="announce-meta">
                    <i class="fa-regular fa-clock"></i>
                    <span>منذ 3 ساعات</span>
                </div>
                <h4 class="announce-title">تحديث مواعيد الاختبارات النهائية للفصل الدراسي الأول</h4>
                <p class="announce-text">
                    نحيطكم علماً بأنه تم إجراء بعض التعديلات على جدول الاختبارات النهائية للفصل الدراسي الأول. يرجى مراجعة الجدول الجديد المتاح في قسم الإعلانات الأكاديمية.
                </p>
            </div>
        </div>

        <!-- إعلان 2 -->
        <div class="post-card announce-card-compact" data-type="announcement" style="padding: 1rem; display: flex;">
            <div class="announce-compact-icon">
                <i class="fa-solid fa-info"></i>
            </div>
            <div class="announce-compact-body">
                <span class="announce-tag">تحديثات النظام</span>
                <h4 class="announce-compact-title">إطلاق النظام الإلكتروني الجديد لتسجيل الحضور والانصراف</h4>
                <div class="announce-meta" style="margin-bottom: 0;">
                    <span>منذ 5 ساعات</span>
                </div>
            </div>
        </div>

        <!-- منشور 1 -->
        <div class="post-card announce-card-compact" data-type="post" style="padding: 1rem; display: flex;">
            <div class="announce-compact-icon">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <div class="announce-compact-body">
                <span class="announce-tag">شؤون الموظفين</span>
                <h4 class="announce-compact-title">اجتماع الكادر الإداري الشهري</h4>
                <div class="announce-meta" style="margin-bottom: 0;">
                    <span>منذ ساعتين</span>
                </div>
            </div>
        </div>

        <!-- منشور 2 -->
        <div class="post-card announce-card-compact" data-type="post" style="padding: 1rem; display: flex;">
            <div class="announce-compact-icon">
                <i class="fa-solid fa-server"></i>
            </div>
            <div class="announce-compact-body">
                <span class="announce-tag">الدعم الفني</span>
                <h4 class="announce-compact-title">صيانة دورية للخوادم</h4>
                <div class="announce-meta" style="margin-bottom: 0;">
                    <span>أمس</span>
                </div>
            </div>
        </div>
        
        <!-- منشور 3 -->
        <div class="post-card announce-card-compact" data-type="post" style="padding: 1rem; display: flex;">
            <div class="announce-compact-icon">
                <i class="fa-solid fa-tree"></i>
            </div>
            <div class="announce-compact-body">
                <span class="announce-tag">النشاطات الطلابية</span>
                <h4 class="announce-compact-title">حملة التشجير السنوية في الحرم الجامعي</h4>
                <div class="announce-meta" style="margin-bottom: 0;">
                    <span>أمس</span>
                </div>
            </div>
        </div>

        <!-- منشور 4 -->
        <div class="post-card announce-card-compact" data-type="post" style="padding: 1rem; display: flex;">
            <div class="announce-compact-icon">
                <i class="fa-solid fa-droplet"></i>
            </div>
            <div class="announce-compact-body">
                <span class="announce-tag">الشؤون الصحية</span>
                <h4 class="announce-compact-title">حملة التبرع بالدم</h4>
                <div class="announce-meta" style="margin-bottom: 0;">
                    <span>منذ يومين</span>
                </div>
            </div>
        </div>
        
        <!-- إعلان 3 -->
        <div class="post-card announce-card-compact" data-type="announcement" style="padding: 1rem; display: flex;">
            <div class="announce-compact-icon">
                <i class="fa-solid fa-gift"></i>
            </div>
            <div class="announce-compact-body">
                <span class="announce-tag">إدارة المؤسسة</span>
                <h4 class="announce-compact-title">تكريم الموظفين المتميزين لشهر أكتوبر</h4>
                <div class="announce-meta" style="margin-bottom: 0;">
                    <span>منذ 3 أيام</span>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    function filterPosts(type) {
        // تحديث حالة الأزرار
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        // فلترة العناصر
        const cards = document.querySelectorAll('.post-card');
        cards.forEach(card => {
            if (type === 'all' || card.getAttribute('data-type') === type) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>
@endpush
