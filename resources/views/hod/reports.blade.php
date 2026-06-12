@extends('layouts.hod')

@section('title', 'التقارير')

@push('styles')
<style>
    .page-subtitle { color: var(--text-secondary); font-size: 1rem; margin-top: -1.5rem; margin-bottom: 2rem; }

    /* Tabs Navigation */
    .tabs-nav {
        display: flex;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 1.5rem;
        gap: 2rem;
    }
    .tab-btn {
        background: transparent;
        border: none;
        padding: 0.75rem 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-secondary);
        cursor: pointer;
        position: relative;
        transition: color 0.2s;
    }
    .tab-btn:hover {
        color: var(--accent-color);
    }
    .tab-btn.active {
        color: var(--accent-color);
    }
    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: var(--accent-color);
    }
    .tab-panel {
        display: none;
    }
    .tab-panel.active {
        display: block;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
    }
    .section-title { font-size: 1.2rem; font-weight: 800; }

    .add-circle-btn {
        width: 38px; height: 38px;
        border-radius: 50%;
        background-color: var(--accent-color);
        color: #1a1a1a;
        border: none; font-size: 1.3rem;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        transition: transform 0.2s;
        flex-shrink: 0;
    }
    .add-circle-btn:hover { transform: scale(1.1); }

    .report-card {
        background-color: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .report-icon {
        width: 46px; height: 46px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .icon-academic   { background: #eff6ff; color: #2563eb; }
    .icon-behavioral { background: #fdf4ff; color: #9333ea; }

    .report-info h5 { font-size: 1rem; font-weight: 700; margin-bottom: 0.2rem; }
    .report-info p  { font-size: 0.83rem; color: var(--text-secondary); }

    .badge-type { padding: 0.2rem 0.7rem; border-radius: 1rem; font-size: 0.78rem; font-weight: 700; white-space: nowrap; }
    .badge-academic   { background: #eff6ff; color: #2563eb; }
    .badge-behavioral { background: #fdf4ff; color: #9333ea; }

    .empty-state { text-align: center; padding: 3rem; color: var(--text-secondary); }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; display: block; }

    /* Modal */
    .modal-overlay {
        position: fixed; top:0; left:0; width:100%; height:100%;
        background:rgba(0,0,0,0.5); display:flex; align-items:center;
        justify-content:center; z-index:1000; opacity:0; pointer-events:none;
        transition:opacity 0.3s;
    }
    .modal-overlay.active { opacity:1; pointer-events:auto; }
    .modal-card {
        background-color:var(--bg-secondary); border-radius:1.5rem; padding:2rem;
        width:92%; max-width:560px; box-shadow:var(--shadow);
        transform:translateY(20px); transition:transform 0.3s;
        max-height:90vh; overflow-y:auto;
    }
    .modal-overlay.active .modal-card { transform:translateY(0); }

    .form-label { display:block; margin-bottom:0.4rem; font-weight:700; font-size:0.88rem; color:var(--text-secondary); }
    .form-input {
        width:100%; padding:0.75rem 1rem; border-radius:0.75rem;
        border:1px solid var(--border-color); background:var(--bg-primary);
        color:var(--text-primary); font-family:inherit; font-size:0.95rem; box-sizing:border-box;
    }
    .form-input:focus { outline:none; border-color:var(--accent-color); }
    .form-group { margin-bottom:0.9rem; }

    .type-cards { display:flex; gap:0.75rem; margin-bottom:0.9rem; }
    .type-card {
        flex:1; border:1px solid var(--border-color); border-radius:0.75rem;
        padding:0.9rem; text-align:center; cursor:pointer; transition:all 0.2s;
    }
    .type-card.active { border-color:var(--accent-color); background:#fefce8; }
    .type-card i { font-size:1.3rem; color:var(--text-secondary); display:block; margin-bottom:0.4rem; }
    .type-card.active i { color:#ca8a04; }
    .type-card span { font-size:0.88rem; font-weight:700; color:var(--text-primary); }

    .btn-save   { background:var(--accent-color); color:#1a1a1a; flex:1; padding:0.75rem; border-radius:0.75rem; border:none; font-weight:700; cursor:pointer; font-size:1rem; font-family:inherit; }
    .btn-cancel { background:transparent; border:1px solid var(--border-color); color:var(--text-primary); flex:1; padding:0.75rem; border-radius:0.75rem; font-weight:700; cursor:pointer; font-size:1rem; font-family:inherit; }
</style>
@endpush

@section('content')
    <p class="page-subtitle">متابعة تقارير أداء الطلاب</p>

    @if(session('success'))
        <div style="background:#f0fdf4;color:#16a34a;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div style="background:#fef2f2;color:#dc2626;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Header + زر + --}}
    <div class="section-header">
        <h4 class="section-title">
            <i class="fa-solid fa-file-lines" style="color:var(--accent-color);margin-left:0.5rem;"></i>
            التقارير الصادرة
        </h4>
        <a href="{{ route('hod.reports.create') }}" class="add-circle-btn" title="طلب تقرير جديد">
            <i class="fa-solid fa-plus"></i>
        </a>
    </div>

    {{-- Tabs Navigation --}}
    <div class="tabs-nav">
        <button class="tab-btn active" onclick="switchTab('my-requests')">
            <i class="fa-solid fa-reply-all" style="margin-left:0.25rem;"></i>
            ردود طلباتي
        </button>
        <button class="tab-btn" onclick="switchTab('advisor-requests')">
            <i class="fa-solid fa-user-tie" style="margin-left:0.25rem;"></i>
            طلبات المربي
        </button>
    </div>

    {{-- Tab 1: ردود طلباتي --}}
    <div id="my-requests" class="tab-panel active">
        @forelse($myRequestsReports as $report)
            @php $isAcademic = $report->report_type === 'academic'; @endphp
            <div class="report-card">
                <div style="display:flex;align-items:center;gap:1rem;flex:1;min-width:0;">
                    <div class="report-icon {{ $isAcademic ? 'icon-academic' : 'icon-behavioral' }}">
                        <i class="fa-solid {{ $isAcademic ? 'fa-chart-line' : 'fa-comment-dots' }}"></i>
                    </div>
                    <div class="report-info" style="min-width:0;">
                        <h5>{{ $report->student_name }}</h5>
                        <p>
                            {{ \Carbon\Carbon::parse($report->generated_at ?? $report->created_at)->diffForHumans() }}
                            @if($isAcademic && !is_null($report->attendance_rate))
                                &bull; حضور: <strong>{{ number_format($report->attendance_rate, 0) }}%</strong>
                                @if(!is_null($report->average_grade))
                                    &bull; معدل: <strong>{{ number_format($report->average_grade, 1) }}</strong>
                                @endif
                            @endif
                            @if(!$isAcademic && $report->recommendations)
                                &bull; {{ Str::limit($report->recommendations, 60) }}
                            @endif
                        </p>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
                    <span class="badge-type {{ $isAcademic ? 'badge-academic' : 'badge-behavioral' }}">
                        {{ $isAcademic ? 'أكاديمي' : 'سلوكي' }}
                    </span>

                    {{-- زر قراءة --}}
                    <button type="button" 
                            onclick="openReadModal('{{ addslashes($report->student_name) }}', '{{ $isAcademic ? 'أكاديمي' : 'سلوكي' }}', '{{ \Carbon\Carbon::parse($report->generated_at ?? $report->created_at)->format('Y/m/d H:i') }}', '{{ $isAcademic ? number_format($report->attendance_rate, 0) : '' }}', '{{ $isAcademic ? number_format($report->average_grade, 1) : '' }}', '{{ addslashes(str_replace(["\r", "\n"], ["", '\n'], $report->recommendations)) }}')"
                            style="background:#eff6ff; border:1px solid #3b82f6; color:#1d4ed8; cursor:pointer; font-size:0.83rem; font-weight:700; padding:0.4rem 0.8rem; border-radius:0.5rem; display:flex; align-items:center; gap:0.25rem;" 
                            title="قراءة التقرير">
                        <i class="fa-solid fa-eye"></i> قراءة
                    </button>

                    {{-- زر تنزيل --}}
                    <a href="{{ route('hod.reports.download', $report->report_id) }}" 
                       style="background:#f0fdf4; border:1px solid #22c55e; color:#15803d; text-decoration:none; cursor:pointer; font-size:0.83rem; font-weight:700; padding:0.4rem 0.8rem; border-radius:0.5rem; display:flex; align-items:center; gap:0.25rem;" 
                       title="تنزيل التقرير">
                        <i class="fa-solid fa-download"></i> تنزيل
                    </a>

                    {{-- زر إرسال للأهل --}}
                    @if(!$report->sent_to_parent)
                    <form action="{{ route('hod.reports.send_to_parent', $report->report_id) }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" 
                                style="background:#fffbeb; border:1px solid #f59e0b; color:#d97706; cursor:pointer; font-size:0.83rem; font-weight:700; padding:0.4rem 0.8rem; border-radius:0.5rem; display:flex; align-items:center; gap:0.25rem;" 
                                title="إرسال لولي الأمر">
                            <i class="fa-solid fa-share-nodes"></i> إرسال للأهل
                        </button>
                    </form>
                    @else
                    <span style="background:#f0fdf4; border:1px solid #16a34a; color:#16a34a; font-size:0.83rem; font-weight:700; padding:0.4rem 0.8rem; border-radius:0.5rem; display:flex; align-items:center; gap:0.25rem;">
                        <i class="fa-solid fa-circle-check"></i> تم الإرسال
                    </span>
                    @endif

                    {{-- زر حذف --}}
                    <form action="{{ route('hod.reports.delete', $report->report_id) }}" method="POST"
                          onsubmit="return confirm('حذف هذا التقرير؟')" style="margin:0;">
                        @csrf
                        <button type="submit" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:0.25rem;" title="حذف">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="fa-solid fa-file-circle-question"></i>
                <p>لا توجد تقارير صادرة لطلباتك حتى الآن.</p>
                <p style="font-size:0.88rem;">اضغط <strong>+</strong> لطلب تقرير من مدرب.</p>
            </div>
        @endforelse
    </div>

    {{-- Tab 2: طلبات المربي --}}
    <div id="advisor-requests" class="tab-panel">
        @forelse($advisorReports as $report)
            @php $isAcademic = $report->report_type === 'academic'; @endphp
            <div class="report-card">
                <div style="display:flex;align-items:center;gap:1rem;flex:1;min-width:0;">
                    <div class="report-icon {{ $isAcademic ? 'icon-academic' : 'icon-behavioral' }}">
                        <i class="fa-solid {{ $isAcademic ? 'fa-chart-line' : 'fa-comment-dots' }}"></i>
                    </div>
                    <div class="report-info" style="min-width:0;">
                        <h5>{{ $report->student_name }}</h5>
                        <p>
                            {{ \Carbon\Carbon::parse($report->generated_at ?? $report->created_at)->diffForHumans() }}
                            @if($isAcademic && !is_null($report->attendance_rate))
                                &bull; حضور: <strong>{{ number_format($report->attendance_rate, 0) }}%</strong>
                                @if(!is_null($report->average_grade))
                                    &bull; معدل: <strong>{{ number_format($report->average_grade, 1) }}</strong>
                                @endif
                            @endif
                            @if(!$isAcademic && $report->recommendations)
                                &bull; {{ Str::limit($report->recommendations, 60) }}
                            @endif
                        </p>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
                    <span class="badge-type {{ $isAcademic ? 'badge-academic' : 'badge-behavioral' }}">
                        {{ $isAcademic ? 'أكاديمي' : 'سلوكي' }}
                    </span>

                    {{-- زر قراءة --}}
                    <button type="button" 
                            onclick="openReadModal('{{ addslashes($report->student_name) }}', '{{ $isAcademic ? 'أكاديمي' : 'سلوكي' }}', '{{ \Carbon\Carbon::parse($report->generated_at ?? $report->created_at)->format('Y/m/d H:i') }}', '{{ $isAcademic ? number_format($report->attendance_rate, 0) : '' }}', '{{ $isAcademic ? number_format($report->average_grade, 1) : '' }}', '{{ addslashes(str_replace(["\r", "\n"], ["", '\n'], $report->recommendations)) }}')"
                            style="background:#eff6ff; border:1px solid #3b82f6; color:#1d4ed8; cursor:pointer; font-size:0.83rem; font-weight:700; padding:0.4rem 0.8rem; border-radius:0.5rem; display:flex; align-items:center; gap:0.25rem;" 
                            title="قراءة التقرير">
                        <i class="fa-solid fa-eye"></i> قراءة
                    </button>

                    {{-- زر تنزيل --}}
                    <a href="{{ route('hod.reports.download', $report->report_id) }}" 
                       style="background:#f0fdf4; border:1px solid #22c55e; color:#15803d; text-decoration:none; cursor:pointer; font-size:0.83rem; font-weight:700; padding:0.4rem 0.8rem; border-radius:0.5rem; display:flex; align-items:center; gap:0.25rem;" 
                       title="تنزيل التقرير">
                        <i class="fa-solid fa-download"></i> تنزيل
                    </a>

                    {{-- زر إرسال للأهل --}}
                    @if(!$report->sent_to_parent)
                    <form action="{{ route('hod.reports.send_to_parent', $report->report_id) }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" 
                                style="background:#fffbeb; border:1px solid #f59e0b; color:#d97706; cursor:pointer; font-size:0.83rem; font-weight:700; padding:0.4rem 0.8rem; border-radius:0.5rem; display:flex; align-items:center; gap:0.25rem;" 
                                title="إرسال لولي الأمر">
                            <i class="fa-solid fa-share-nodes"></i> إرسال للأهل
                        </button>
                    </form>
                    @else
                    <span style="background:#f0fdf4; border:1px solid #16a34a; color:#16a34a; font-size:0.83rem; font-weight:700; padding:0.4rem 0.8rem; border-radius:0.5rem; display:flex; align-items:center; gap:0.25rem;">
                        <i class="fa-solid fa-circle-check"></i> تم الإرسال
                    </span>
                    @endif

                    {{-- زر حذف --}}
                    <form action="{{ route('hod.reports.delete', $report->report_id) }}" method="POST"
                          onsubmit="return confirm('حذف هذا التقرير؟')" style="margin:0;">
                        @csrf
                        <button type="submit" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:0.25rem;" title="حذف">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="fa-solid fa-file-circle-question"></i>
                <p>لا توجد تقارير مربي واردة حتى الآن.</p>
            </div>
        @endforelse
    </div>

    {{-- Modal: Read Report --}}
    <div class="modal-overlay" id="read-report-modal">
        <div class="modal-card">
            <h4 style="font-size:1.2rem; font-weight:800; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="fa-solid fa-file-invoice" style="color:var(--accent-color);"></i>
                تفاصيل التقرير
            </h4>
            <div id="report-detail-content" style="font-size:0.95rem; line-height:1.6; color:var(--text-primary);">
                <!-- Dynamic Content -->
            </div>
            <div style="margin-top:1.5rem; display:flex; justify-content:flex-end;">
                <button class="btn-cancel" onclick="closeReadModal()">إغلاق</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));

        const clickedBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.getAttribute('onclick').includes(tabId));
        if (clickedBtn) clickedBtn.classList.add('active');

        const targetPanel = document.getElementById(tabId);
        if (targetPanel) targetPanel.classList.add('active');
    }

    function openReadModal(studentName, typeLabel, dateText, attendance, average, recommendations) {
        let contentHtml = `
            <div style="margin-bottom:0.75rem;"><strong>اسم الطالب:</strong> ${studentName}</div>
            <div style="margin-bottom:0.75rem;"><strong>نوع التقرير:</strong> ${typeLabel}</div>
            <div style="margin-bottom:0.75rem;"><strong>تاريخ التوليد:</strong> ${dateText}</div>
        `;
        if (attendance !== '') {
            contentHtml += `<div style="margin-bottom:0.75rem;"><strong>نسبة الحضور:</strong> ${attendance}%</div>`;
        }
        if (average !== '') {
            contentHtml += `<div style="margin-bottom:0.75rem;"><strong>المعدل:</strong> ${average}</div>`;
        }
        contentHtml += `
            <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid var(--border-color);">
                <strong>المحتوى والتوصيات:</strong>
                <p style="background:var(--bg-primary); padding:0.75rem 1rem; border-radius:0.75rem; margin-top:0.5rem; border-right:3px solid var(--accent-color); white-space:pre-wrap;">${recommendations}</p>
            </div>
        `;
        document.getElementById('report-detail-content').innerHTML = contentHtml;
        document.getElementById('read-report-modal').classList.add('active');
    }

    function closeReadModal() {
        document.getElementById('read-report-modal').classList.remove('active');
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeReadModal();
        }
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('read-report-modal');
        if (modal) {
            modal.addEventListener('click', function(event) {
                if (event.target === this) {
                    closeReadModal();
                }
            });
        }
    });
</script>
@endpush
