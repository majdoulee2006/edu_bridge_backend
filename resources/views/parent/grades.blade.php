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

{{-- Academic Card Section for Child --}}
@if(!empty($academicCardData) && isset($academicCardData['academic_card']))
<div style="background: var(--bg-secondary); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--border-color); box-shadow: var(--shadow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-id-card" style="color: var(--accent-color);"></i> كشف العلامات الأكاديمي للابن (البطاقة الأكاديمية)
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.25rem;">سجل أكاديمي موحد لجميع المواد المسجلة والحالة الإجمالية</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('parent.academic_card.pdf', ['student_id' => $selected_child_id]) }}" target="_blank" style="background: #ef4444; color: white; border: none; padding: 0.65rem 1.1rem; border-radius: 10px; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
                <i class="fa-solid fa-file-pdf"></i> تصدير PDF
            </a>
            <a href="{{ route('parent.academic_card.excel', ['student_id' => $selected_child_id]) }}" target="_blank" style="background: #10b981; color: white; border: none; padding: 0.65rem 1.1rem; border-radius: 10px; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
                <i class="fa-solid fa-file-excel"></i> تصدير Excel
            </a>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
        <div style="background: var(--bg-primary); border-radius: 1rem; padding: 1rem; text-align: center; border: 1px solid var(--border-color);">
            <div style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 700;">المعدل التراكمي</div>
            <div style="font-size: 1.6rem; font-weight: 900; color: var(--accent-color); margin-top: 0.2rem;">{{ $academicCardData['summary']['average'] ?? 0 }}%</div>
        </div>
        <div style="background: var(--bg-primary); border-radius: 1rem; padding: 1rem; text-align: center; border: 1px solid var(--border-color);">
            <div style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 700;">المواد المجتازة</div>
            <div style="font-size: 1.6rem; font-weight: 900; color: #10b981; margin-top: 0.2rem;">{{ $academicCardData['summary']['passed_courses'] ?? 0 }}</div>
        </div>
        <div style="background: var(--bg-primary); border-radius: 1rem; padding: 1rem; text-align: center; border: 1px solid var(--border-color);">
            <div style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 700;">المواد المتبقية / راسب</div>
            <div style="font-size: 1.6rem; font-weight: 900; color: #ef4444; margin-top: 0.2rem;">{{ $academicCardData['summary']['failed_courses'] ?? 0 }}</div>
        </div>
        <div style="background: var(--bg-primary); border-radius: 1rem; padding: 1rem; text-align: center; border: 1px solid var(--border-color);">
            <div style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 700;">لم يتم التقدم لها</div>
            <div style="font-size: 1.6rem; font-weight: 900; color: #6b7280; margin-top: 0.2rem;">{{ $academicCardData['summary']['not_attended'] ?? 0 }}</div>
        </div>
    </div>

    {{-- Academic Card Table --}}
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; min-width: 750px;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 0.75rem; text-align: right; color: var(--text-secondary); font-size: 0.85rem;">#</th>
                    <th style="padding: 0.75rem; text-align: right; color: var(--text-secondary); font-size: 0.85rem;">المادة الدراسية</th>
                    <th style="padding: 0.75rem; text-align: center; color: var(--text-secondary); font-size: 0.85rem;">المذاكرة (20)</th>
                    <th style="padding: 0.75rem; text-align: center; color: var(--text-secondary); font-size: 0.85rem;">الشفهي/العملي (20)</th>
                    <th style="padding: 0.75rem; text-align: center; color: var(--text-secondary); font-size: 0.85rem;">الامتحان (60)</th>
                    <th style="padding: 0.75rem; text-align: center; color: var(--text-secondary); font-size: 0.85rem;">المجموع (100)</th>
                    <th style="padding: 0.75rem; text-align: center; color: var(--text-secondary); font-size: 0.85rem;">الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($academicCardData['academic_card'] as $idx => $row)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 0.75rem;">{{ $idx + 1 }}</td>
                    <td style="padding: 0.75rem; font-weight: 700;">{{ $row['title'] }}</td>
                    <td style="padding: 0.75rem; text-align: center;">{{ $row['quiz_score'] ?? '-' }}</td>
                    <td style="padding: 0.75rem; text-align: center;">{{ $row['oral_score'] ?? '-' }}</td>
                    <td style="padding: 0.75rem; text-align: center;">{{ $row['final_score'] ?? '-' }}</td>
                    <td style="padding: 0.75rem; text-align: center; font-weight: 800;">{{ $row['total_score'] ?? '-' }}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        @if($row['status'] === 'ناجح')
                            <span style="background: #bbf7d0; color: #166534; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 700;">ناجح</span>
                        @elseif($row['status'] === 'راسب')
                            <span style="background: #fecaca; color: #991b1b; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 700;">راسب</span>
                        @else
                            <span style="background: #fef08a; color: #854d0e; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 700;">لم يتم التقدم</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

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
