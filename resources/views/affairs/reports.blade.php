@extends('layouts.affairs')

@section('title', 'التقارير')

@push('styles')
<style>
    .page-subtitle { color: var(--text-secondary); font-size: 1rem; margin-top: -1.5rem; margin-bottom: 2rem; }

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
        border: none;
        font-size: 1.3rem;
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
    .icon-academic  { background-color: #eff6ff; color: #2563eb; }
    .icon-behavioral { background-color: #fdf4ff; color: #9333ea; }

    .report-info h5 { font-size: 1rem; font-weight: 700; margin-bottom: 0.2rem; }
    .report-info p  { font-size: 0.83rem; color: var(--text-secondary); }

    .badge-type {
        padding: 0.2rem 0.7rem; border-radius: 1rem; font-size: 0.78rem; font-weight: 700;
    }
    .badge-academic  { background:#eff6ff; color:#2563eb; }
    .badge-behavioral { background:#fdf4ff; color:#9333ea; }

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
        width:92%; max-width:500px; box-shadow:var(--shadow);
        transform:translateY(20px); transition:transform 0.3s;
    }
    .modal-overlay.active .modal-card { transform:translateY(0); }
    .form-label { display:block; margin-bottom:0.4rem; font-weight:700; font-size:0.88rem; color:var(--text-secondary); }
    .form-input {
        width:100%; padding:0.7rem 0.9rem; border-radius:0.75rem;
        border:1px solid var(--border-color); background:var(--bg-primary);
        color:var(--text-primary); font-family:inherit; font-size:0.95rem; box-sizing:border-box;
    }
    .form-input:focus { outline:none; border-color:var(--accent-color); }
    .form-group { margin-bottom:0.9rem; }
    .btn-save   { background:var(--accent-color); color:#1a1a1a; flex:1; padding:0.75rem; border-radius:0.75rem; border:none; font-weight:700; cursor:pointer; font-size:1rem; font-family:inherit; }
    .btn-cancel { background:transparent; border:1px solid var(--border-color); color:var(--text-primary); flex:1; padding:0.75rem; border-radius:0.75rem; font-weight:700; cursor:pointer; font-size:1rem; font-family:inherit; }

    .empty-state { text-align:center; padding:3rem; color:var(--text-secondary); }
    .empty-state i { font-size:3rem; margin-bottom:1rem; opacity:0.3; }
</style>
@endpush

@section('content')
    <p class="page-subtitle">إدارة ومتابعة تقارير الأداء</p>

    @if(session('success'))
        <div style="background:#f0fdf4;color:#16a34a;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;">{{ session('success') }}</div>
    @endif

    {{-- Header مع زر + --}}
    <div class="section-header">
        <h4 class="section-title"><i class="fa-solid fa-file-lines" style="color:var(--accent-color);margin-left:0.5rem;"></i> التقارير الصادرة</h4>
        <button class="add-circle-btn" onclick="openModal('report-modal')" title="طلب تقرير جديد">
            <i class="fa-solid fa-plus"></i>
        </button>
    </div>

    {{-- قائمة التقارير --}}
    @forelse($reports as $report)
    @php
        $isAcademic = $report->report_type === 'academic';
    @endphp
    <div class="report-card">
        <div style="display:flex;align-items:center;gap:1rem;flex:1;min-width:0;">
            <div class="report-icon {{ $isAcademic ? 'icon-academic' : 'icon-behavioral' }}">
                <i class="fa-solid {{ $isAcademic ? 'fa-chart-line' : 'fa-comment-dots' }}"></i>
            </div>
            <div class="report-info" style="min-width:0;">
                <h5>{{ $report->student_name }}</h5>
                <p>
                    @if($report->teacher_name) المدرب: {{ $report->teacher_name }} &bull; @endif
                    {{ \Carbon\Carbon::parse($report->created_at)->format('d/m/Y') }}
                </p>
                @if($isAcademic && $report->attendance_rate)
                    <p style="margin-top:0.2rem;">
                        <span style="color:#2563eb;">نسبة الحضور: {{ $report->attendance_rate }}%</span>
                        &bull;
                        <span style="color:#16a34a;">المعدل: {{ $report->average_grade }}</span>
                    </p>
                @endif
                @if(!$isAcademic && $report->recommendations)
                    <p style="margin-top:0.2rem;color:var(--text-secondary);font-size:0.82rem;">{{ Str::limit($report->recommendations, 80) }}</p>
                @endif
            </div>
        </div>
        <span class="badge-type {{ $isAcademic ? 'badge-academic' : 'badge-behavioral' }}">
            {{ $isAcademic ? 'أكاديمي' : 'سلوكي' }}
        </span>
    </div>
    @empty
    <div class="empty-state">
        <div><i class="fa-solid fa-file-circle-question"></i></div>
        <p>لا توجد تقارير صادرة حتى الآن.</p>
        <p style="font-size:0.88rem;">اضغط <strong>+</strong> لإرسال طلب تقرير لمدرب.</p>
    </div>
    @endforelse


    {{-- Modal: طلب تقرير جديد --}}
    <div id="report-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size:1.3rem;font-weight:800;margin-bottom:1.25rem;text-align:center;">
                <i class="fa-solid fa-plus-circle" style="color:var(--accent-color);margin-left:0.4rem;"></i>
                طلب تقرير جديد
            </h4>
            <form action="{{ route('affairs.reports.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label">الطالب المعني <span style="color:#ef4444">*</span></label>
                    <select name="student_id" required class="form-input">
                        <option value="" disabled selected>اختر الطالب</option>
                        @foreach($students as $s)
                            <option value="{{ $s->student_id }}">{{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">المدرب المعني <span style="color:#ef4444">*</span></label>
                    <select name="teacher_id" required class="form-input">
                        <option value="" disabled selected>اختر المدرب</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->teacher_id }}">{{ $t->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">نوع التقرير <span style="color:#ef4444">*</span></label>
                    <select name="report_type" required class="form-input">
                        <option value="" disabled selected>اختر نوع التقرير</option>
                        <option value="academic">أكاديمي (يولّده النظام تلقائياً)</option>
                        <option value="behavioral">سلوكي (يكتبه المدرب)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">ملاحظات إضافية</label>
                    <textarea name="notes" rows="3" class="form-input" placeholder="أي تعليمات أو ملاحظات للمدرب..."></textarea>
                </div>

                <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                    <button type="submit" class="btn-save">إرسال الطلب</button>
                    <button type="button" onclick="closeModal('report-modal')" class="btn-cancel">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function openModal(id)  { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
</script>
@endpush
