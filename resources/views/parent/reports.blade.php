@extends('layouts.parent')
@section('title', 'تقارير الأداء الدراسي والسلوكي')

@push('styles')
<style>
    .reports-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    @media(min-width: 992px) {
        .reports-container {
            grid-template-columns: 2fr 1fr;
        }
    }
    
    .reports-list {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    
    .report-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }
    
    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .report-type {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    
    .status-badge {
        padding: 0.3rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        font-weight: 700;
    }
    
    .status-completed {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .status-pending {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    
    .report-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .report-stat-item {
        background: var(--bg-primary);
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .report-stat-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }
    
    .report-stat-value {
        font-weight: 800;
        font-size: 1.05rem;
        color: var(--text-primary);
    }
    
    .report-recommendations {
        background: var(--bg-primary);
        padding: 1rem;
        border-radius: 0.75rem;
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.6;
        border: 1px solid var(--border-color);
        position: relative;
    }
    
    .report-recommendations-title {
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
    }
    
    .report-recommendations-title i {
        color: var(--accent-color);
    }
    
    .report-meta {
        margin-top: 1rem;
        font-size: 0.8rem;
        color: var(--text-secondary);
        opacity: 0.7;
        text-align: left;
    }
    
    .request-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        align-self: start;
    }
    
    .request-card h4 {
        font-size: 1.15rem;
        font-weight: 800;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .request-card h4 i {
        color: var(--accent-color);
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }
    
    .form-control {
        width: 100%;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        font-family: inherit;
        font-size: 0.9rem;
        outline: none;
    }
    
    .form-control:focus {
        border-color: var(--accent-color);
    }
    
    .btn-submit {
        background: var(--accent-color);
        color: #1a1a1a;
        font-weight: 700;
        border: none;
        width: 100%;
        padding: 0.75rem;
        border-radius: 0.75rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: background 0.2s ease;
    }
    
    .btn-submit:hover {
        background: #e6b800;
    }
    
    .type-options {
        display: flex;
        gap: 0.75rem;
    }
    
    .type-option {
        flex: 1;
        text-align: center;
        border: 1px solid var(--border-color);
        padding: 0.6rem;
        border-radius: 0.75rem;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 700;
        background: var(--bg-primary);
        transition: all 0.2s ease;
    }
    
    .type-option.active {
        background: var(--accent-color);
        color: #1a1a1a;
        border-color: var(--accent-color);
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
        متابعة التقارير الشاملة للأداء والحالة السلوكية للابن: {{ $selected_child->full_name }}
    @endsection

    <div class="reports-container">
        <!-- List of Reports -->
        <div>
            <h3 class="section-title" style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.25rem;">
                <i class="fa-solid fa-clock-rotate-left"></i> سجل التقارير الصادرة
            </h3>
            
            @if($reports->isNotEmpty())
                <div class="reports-list">
                    @foreach($reports as $rep)
                        <div class="report-card">
                            <div class="report-header">
                                <span class="report-type">
                                    <i class="fa-solid fa-file-chart-line" style="color: var(--accent-color); margin-left: 0.5rem;"></i>
                                    تقرير {{ $rep->report_type === 'academic' ? 'أكاديمي' : 'سلوكي' }}
                                </span>
                                <span class="status-badge status-{{ $rep->status }}">
                                    {{ $rep->status === 'completed' ? 'جاهز' : 'قيد المراجعة والتحضير' }}
                                </span>
                            </div>
                            
                            @if($rep->status === 'completed')
                                <div class="report-stats">
                                    <div class="report-stat-item">
                                        <span class="report-stat-label">نسبة حضور المحاضرات</span>
                                        <span class="report-stat-value" style="color: #10b981;">{{ $rep->attendance_rate }}%</span>
                                    </div>
                                    <div class="report-stat-item">
                                        <span class="report-stat-label">المعدل التراكمي للدرجات</span>
                                        <span class="report-stat-value" style="color: var(--accent-color);">{{ $rep->average_grade }}%</span>
                                    </div>
                                </div>
                                
                                <div class="report-recommendations">
                                    <div class="report-recommendations-title">
                                        <i class="fa-solid fa-lightbulb"></i> توصيات وملاحظات المربي:
                                    </div>
                                    <p style="margin: 0; color: var(--text-secondary);">{{ $rep->recommendations }}</p>
                                </div>
                            @else
                                <div style="color: var(--text-secondary); font-style: italic; font-size: 0.9rem;">
                                    تم إرسال الطلب إلى المرشد الأكاديمي، وسيتم إصدار التقرير السلوكي وتحديث حالة الطلب قريباً.
                                </div>
                            @endif
                            
                            <div class="report-meta">
                                <span>تاريخ الطلب: {{ \Carbon\Carbon::parse($rep->created_at)->format('Y-m-d H:i') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.25rem; border: 1px dashed var(--border-color); color: var(--text-secondary);">
                    <i class="fa-solid fa-file-excel" style="font-size: 2.5rem; opacity: 0.4; margin-bottom: 1rem; display: block;"></i>
                    لا توجد تقارير صادرة للابن حتى الآن.
                </div>
            @endif
        </div>
        
        <!-- Request New Report -->
        <div>
            <div class="request-card">
                <h4><i class="fa-solid fa-file-signature"></i> طلب تقرير جديد</h4>
                <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5; margin-bottom: 1.25rem;">
                    يمكنك طلب تقرير دراسي للابن. التقرير الأكاديمي يتم إنشاؤه مباشرة بشكل تلقائي، بينما التقرير السلوكي يتطلب إرساله إلى المرشد الأكاديمي للابن لتعبئته يدوياً.
                </p>
                <form action="{{ route('parent.reports.request') }}" method="POST">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $selected_child_id }}">
                    
                    <div class="form-group">
                        <label>نوع التقرير</label>
                        <div class="type-options">
                            <div class="type-option active" data-value="academic" onclick="selectReportType('academic')">أكاديمي</div>
                            <div class="type-option" data-value="behavioral" onclick="selectReportType('behavioral')">سلوكي</div>
                        </div>
                        <input type="hidden" name="report_type" id="report-type-input" value="academic">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-arrow-circle-right"></i> إرسال طلب التقرير
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
function selectReportType(val) {
    document.getElementById('report-type-input').value = val;
    document.querySelectorAll('.type-option').forEach(el => {
        if(el.getAttribute('data-value') === val) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}
</script>
@endpush
