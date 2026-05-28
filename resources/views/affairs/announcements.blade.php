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
        
        <!-- إعلان 1 -->
        <div class="post-card" data-type="announcement">
            <div class="post-header">
                <div class="post-avatar" style="background: var(--accent-color); color: #111827;"><i class="fa-solid fa-bullhorn"></i></div>
                <div>
                    <div class="post-author">إعلان هام</div>
                    <div class="post-time">منذ 3 ساعات</div>
                </div>
            </div>
            <h3 class="post-title">تحديث مواعيد الاختبارات النهائية للفصل الدراسي الأول</h3>
            <p class="post-content">
                نحيطكم علماً بأنه تم إجراء بعض التعديلات على جدول الاختبارات النهائية للفصل الدراسي الأول. يرجى مراجعة الجدول الجديد المتاح في قسم الإعلانات الأكاديمية.
            </p>
        </div>

        <!-- إعلان 2 -->
        <div class="post-card" data-type="announcement">
            <div class="post-header">
                <div class="post-avatar" style="background: #374151;"><i class="fa-solid fa-info"></i></div>
                <div>
                    <div class="post-author">تحديثات النظام</div>
                    <div class="post-time">منذ 5 ساعات</div>
                </div>
            </div>
            <h3 class="post-title">إطلاق النظام الإلكتروني الجديد لتسجيل الحضور والانصراف</h3>
            <p class="post-content">
                تم تفعيل النظام الجديد لتسجيل الحضور عبر البصمة الإلكترونية لجميع الموظفين. يرجى التأكد من تسجيل بصمتكم في قسم الموارد البشرية خلال هذا الأسبوع.
            </p>
        </div>

        <!-- منشور 1 -->
        <div class="post-card" data-type="post">
            <div class="post-header">
                <div class="post-avatar" style="background: #111827;"><i class="fa-solid fa-users-gear"></i></div>
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
            <div style="width: 100%; height: 180px; border-radius: 1rem; background: url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80') center/cover; margin-bottom: 1rem;"></div>
            <!-- Action buttons removed per user request -->
        </div>

        <!-- منشور 2 -->
        <div class="post-card" data-type="post">
            <div class="post-header">
                <div class="post-avatar" style="background: #0f172a;"><i class="fa-solid fa-server"></i></div>
                <div>
                    <div class="post-author">الدعم الفني</div>
                    <div class="post-time">أمس</div>
                </div>
            </div>
            <h3 class="post-title">صيانة دورية للخوادم</h3>
            <p class="post-content">
                نحيطكم علماً بأنه سيتم إجراء صيانة دورية للخوادم يوم الجمعة القادم، مما قد يؤدي إلى انقطاع مؤقت في الخدمة. نعتذر عن أي إزعاج.
            </p>
            <!-- Action buttons removed per user request -->
        </div>
        
        <!-- منشور 3 -->
        <div class="post-card" data-type="post">
            <div class="post-header">
                <div class="post-avatar" style="background: #10b981;"><i class="fa-solid fa-tree"></i></div>
                <div>
                    <div class="post-author">النشاطات الطلابية</div>
                    <div class="post-time">أمس</div>
                </div>
            </div>
            <h3 class="post-title">حملة التشجير السنوية في الحرم الجامعي</h3>
            <p class="post-content">
                ندعو جميع الموظفين والطلاب للمشاركة في حملة التشجير السنوية التي ستقام يوم السبت القادم في ساحة الحرم الجامعي الرئيسية. سيتم توفير كافة الأدوات الزراعية والشتلات.
            </p>
            <!-- Action buttons removed per user request -->
        </div>

        <!-- منشور 4 -->
        <div class="post-card" data-type="post">
            <div class="post-header">
                <div class="post-avatar" style="background: #ef4444;"><i class="fa-solid fa-droplet"></i></div>
                <div>
                    <div class="post-author">الشؤون الصحية</div>
                    <div class="post-time">منذ يومين</div>
                </div>
            </div>
            <h3 class="post-title">حملة التبرع بالدم</h3>
            <p class="post-content">
                بالتعاون مع المستشفى الوطني، نقيم حملة للتبرع بالدم في العيادة المركزية للمؤسسة. من يرغب بالتبرع يرجى التوجه للعيادة خلال أوقات الدوام الرسمي هذا الأسبوع.
            </p>
            <!-- Action buttons removed per user request -->
        </div>
        
        <!-- إعلان 3 -->
        <div class="post-card" data-type="announcement">
            <div class="post-header">
                <div class="post-avatar" style="background: var(--accent-color); color: #111827;"><i class="fa-solid fa-gift"></i></div>
                <div>
                    <div class="post-author">إدارة المؤسسة</div>
                    <div class="post-time">منذ 3 أيام</div>
                </div>
            </div>
            <h3 class="post-title">تكريم الموظفين المتميزين لشهر أكتوبر</h3>
            <p class="post-content">
                نبارك للزملاء الذين تم اختيارهم كموظفين متميزين لهذا الشهر تقديراً لجهودهم وتفانيهم في العمل. سيقام حفل التكريم يوم الخميس القادم.
            </p>
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
