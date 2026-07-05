@extends('layouts.parent')
@section('title', 'الدرجات والأداء')

@push('styles')
<style>
    .average-card {
        background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        border-radius: 1.25rem;
        padding: 1.75rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
    }
    
    .average-info h3 {
        font-size: 1.4rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
    }
    
    .average-info p {
        font-size: 0.95rem;
        color: var(--text-secondary);
    }
    
    .average-number {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--accent-color);
        border: 3px solid var(--accent-color);
        width: 90px;
        height: 90px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(255, 204, 0, 0.15);
    }

    .course-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .course-header {
        background: var(--bg-primary);
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        user-select: none;
        transition: background 0.2s ease;
    }

    .course-header:hover {
        background: rgba(255, 204, 0, 0.02);
    }
    
    .course-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .chevron-icon {
        font-size: 0.95rem;
        color: var(--text-secondary);
        transition: transform 0.3s ease;
    }

    .course-card.expanded .chevron-icon {
        transform: rotate(-180deg);
    }

    .course-grades-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        background: var(--bg-secondary);
    }

    .course-card.expanded .course-grades-content {
        max-height: 500px;
    }
    
    .grades-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .grades-table th, .grades-table td {
        padding: 1rem 1.5rem;
        text-align: right;
        font-size: 0.9rem;
    }
    
    .grades-table th {
        background: rgba(0, 0, 0, 0.02);
        color: var(--text-secondary);
        font-weight: 700;
        border-bottom: 1px solid var(--border-color);
    }
    
    .grades-table td {
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .grades-table tr:last-child td {
        border-bottom: none;
    }
    
    .score-high {
        color: #10b981;
        font-weight: 700;
    }
    
    .score-low {
        color: #ef4444;
        font-weight: 700;
    }

    /* Badges styles */
    .type-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .badge-exam {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .badge-oral {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }
    .badge-quiz {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    .badge-generic {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
        border: 1px solid rgba(107, 114, 128, 0.2);
    }
</style>
@endpush

@section('content')

@if(!$selected_child_id)
    <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color);">
        <i class="fa-solid fa-child" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5; margin-bottom: 1rem; display: block;"></i>
        <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">يرجى اختيار ابن أولاً</h4>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">يرجى اختيار الابن من القائمة في الأعلى أو إضافة ابن من تبويب "أبنائي".</p>
    </div>
@else
    @section('subtitle')
        الدرجات الأكاديمية ونتائج الاختبارات للابن: {{ $selected_child->full_name }}
    @endsection

    <div class="average-card">
        <div class="average-info">
            <h3>المعدل الدراسي التراكمي</h3>
            <p>يمثل هذا المعدل متوسط درجات الابن في كافة الاختبارات والامتحانات التي تم تسجيل نتائجها.</p>
        </div>
        <div class="average-number">
            {{ $overallAverage }}%
        </div>
    </div>

    <h3 class="section-title" style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <i class="fa-solid fa-graduation-cap" style="color: var(--accent-color);"></i> تفصيل نتائج المواد الدراسية
    </h3>

    @if($grades->isNotEmpty())
        @foreach($grades as $courseTitle => $courseGrades)
            <div class="course-card {{ $loop->first ? 'expanded' : '' }}">
                <div class="course-header" onclick="toggleCourseCard(this)">
                    <span class="course-title">
                        <i class="fa-solid fa-chevron-down chevron-icon"></i>
                        {{ $courseTitle }}
                    </span>
                    <span style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">
                        المتوسط للمادة: {{ round($courseGrades->avg('score'), 1) }}%
                    </span>
                </div>
                <div class="course-grades-content">
                    <table class="grades-table">
                        <thead>
                            <tr>
                                <th>نوع التقييم / الاختبار</th>
                                <th>تاريخ التقييم</th>
                                <th style="text-align: center;">الدرجة المحصلة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courseGrades as $g)
                                @php
                                    $badgeClass = 'badge-generic';
                                    $badgeText = 'تقييم';
                                    $icon = 'fa-chart-simple';
                                    if (mb_strpos($g->exam_name, 'نهائي') !== false || mb_strpos($g->exam_name, 'النهائي') !== false || mb_strpos($g->exam_name, 'امتحان') !== false) {
                                        $badgeClass = 'badge-exam';
                                        $badgeText = 'علامة الامتحان';
                                        $icon = 'fa-file-invoice';
                                    } elseif (mb_strpos($g->exam_name, 'شفهي') !== false) {
                                        $badgeClass = 'badge-oral';
                                        $badgeText = 'علامة الشفهي';
                                        $icon = 'fa-comments';
                                    } elseif (mb_strpos($g->exam_name, 'مذاكرة') !== false || mb_strpos($g->exam_name, 'اختبار') !== false) {
                                        $badgeClass = 'badge-quiz';
                                        $badgeText = 'علامة المذاكرة';
                                        $icon = 'fa-pen-to-square';
                                    }
                                @endphp
                                <tr>
                                    <td style="font-weight: 700;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <span class="type-badge {{ $badgeClass }}">
                                                <i class="fa-solid {{ $icon }}"></i> {{ $badgeText }}
                                            </span>
                                            <span>{{ $g->exam_name }}</span>
                                        </div>
                                    </td>
                                    <td style="color: var(--text-secondary);">
                                        {{ $g->exam_date ? \Carbon\Carbon::parse($g->exam_date)->format('Y-m-d') : 'غير محدد' }}
                                    </td>
                                    <td style="text-align: center; font-size: 1rem;">
                                        @php
                                            $percentage = ($g->score / $g->max_score) * 100;
                                            $scoreClass = $percentage >= 50 ? 'score-high' : 'score-low';
                                        @endphp
                                        <span class="{{ $scoreClass }}">{{ $g->score }}</span>
                                        <span style="color: var(--text-secondary); font-size: 0.8rem; margin-right: 0.15rem;">/ {{ $g->max_score }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @else
        <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color);">
            <i class="fa-solid fa-chart-line" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.4; margin-bottom: 1rem; display: block;"></i>
            <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">لا توجد درجات مسجلة</h4>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">لم يتم تسجيل أو رصد أي درجات امتحانات للابن {{ $selected_child->full_name }} في النظام بعد.</p>
        </div>
    @endif
@endif

@endsection

@push('scripts')
<script>
function toggleCourseCard(header) {
    const card = header.closest('.course-card');
    card.classList.toggle('expanded');
}
</script>
@endpush
