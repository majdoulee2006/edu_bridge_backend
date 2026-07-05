@extends('layouts.parent')
@section('title', 'الواجبات والمهام')

@push('styles')
<style>
    .assignments-list {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    
    .assignment-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    
    .assignment-card:hover {
        transform: translateY(-2px);
        border-color: var(--accent-color);
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        gap: 1rem;
    }
    
    .assignment-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }
    
    .course-badge {
        background: rgba(255, 204, 0, 0.1);
        color: var(--accent-color);
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        font-weight: 700;
        display: inline-block;
        border: 1px solid rgba(255, 204, 0, 0.2);
    }
    
    .status-badge {
        padding: 0.35rem 0.85rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        font-weight: 700;
    }
    
    .status-completed {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .status-missed {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .status-pending {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    
    .card-body {
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 1.25rem;
    }
    
    .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--text-secondary);
    }
    
    .meta-item i {
        color: var(--accent-color);
    }
    
    .feedback-box {
        background: var(--bg-primary);
        border-radius: 0.75rem;
        padding: 1rem;
        border: 1px solid var(--border-color);
        margin-top: 1rem;
        font-size: 0.85rem;
        line-height: 1.5;
    }
    
    .feedback-title {
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    
    .feedback-title i {
        color: #10b981;
    }
    
    .btn-download {
        background: var(--bg-primary);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        padding: 0.4rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s ease;
    }
    
    .btn-download:hover {
        background: var(--accent-color);
        color: #1a1a1a;
        border-color: var(--accent-color);
    }

    /* Tabs styling */
    .tabs-nav {
        display: flex;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 2rem;
        gap: 0.5rem;
        padding-bottom: 2px;
    }
    
    .tab-btn {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-family: inherit;
        font-size: 1.05rem;
        font-weight: 800;
        padding: 0.75rem 1.25rem;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-radius: 0;
    }
    
    .tab-btn:hover {
        color: var(--text-primary);
    }
    
    .tab-btn.active {
        color: var(--text-primary);
        border-bottom-color: var(--accent-color);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        border: 1px dashed var(--border-color);
        margin-bottom: 2rem;
    }

    .empty-state i {
        font-size: 3rem;
        color: var(--text-secondary);
        opacity: 0.4;
        margin-bottom: 1rem;
        display: block;
    }

    .empty-state h4 {
        font-size: 1.2rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .empty-state p {
        color: var(--text-secondary);
        font-size: 0.95rem;
        margin: 0;
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
        متابعة الواجبات وتواريخ تسليمها للابن: {{ $selected_child->full_name }}
    @endsection

    @php
        $pending = $assignments->where('status', 'pending');
        $completed = $assignments->where('status', 'completed');
        $missed = $assignments->where('status', 'missed');
    @endphp

    @if($assignments->isNotEmpty())
        <div class="tabs-nav">
            <button class="tab-btn active" onclick="switchTab('pending', event)">
                <i class="fa-solid fa-clock" style="color: #f59e0b;"></i> غير مكتملة ({{ $pending->count() }})
            </button>
            <button class="tab-btn" onclick="switchTab('completed', event)">
                <i class="fa-solid fa-circle-check" style="color: #10b981;"></i> مكتملة ({{ $completed->count() }})
            </button>
            <button class="tab-btn" onclick="switchTab('missed', event)">
                <i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i> فائتة ({{ $missed->count() }})
            </button>
        </div>

        <!-- Pending (Incomplete) Tab -->
        <div id="tab-pending" class="tab-content active">
            @if($pending->isNotEmpty())
                <div class="assignments-list">
                    @foreach($pending as $asn)
                        <div class="assignment-card">
                            <div class="card-header">
                                <div>
                                    <span class="course-badge">{{ $asn->course_name }}</span>
                                    <h4 class="assignment-title" style="margin-top: 0.5rem;">{{ $asn->title }}</h4>
                                </div>
                                <div>
                                    <span class="status-badge status-pending">
                                        <i class="fa-solid fa-clock" style="margin-left: 0.25rem;"></i> غير مكتمل
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                @if($asn->file_path)
                                    <a href="{{ asset('storage/' . $asn->file_path) }}" class="btn-download" target="_blank">
                                        <i class="fa-solid fa-download"></i> تحميل ملف الواجب ({{ $asn->file_name ?? 'تحميل' }})
                                    </a>
                                @else
                                    <span style="font-style: italic; opacity: 0.6;">لا يوجد ملف مرفق للواجب.</span>
                                @endif
                            </div>
                            
                            <div class="card-footer">
                                <div class="meta-item">
                                    <i class="fa-regular fa-calendar"></i>
                                    <span>تاريخ الاستحقاق: {{ \Carbon\Carbon::parse($asn->due_date)->format('Y-m-d H:i') }}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-star"></i>
                                    <span>الدرجة المستحقة: <strong>-- / {{ $asn->max_points }}</strong></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="fa-solid fa-check-double" style="color: #10b981;"></i>
                    <h4>لا يوجد واجبات غير مكتملة</h4>
                    <p>عمل ممتاز! تم إنجاز جميع الواجبات المطلوبة حتى الآن.</p>
                </div>
            @endif
        </div>

        <!-- Completed Tab -->
        <div id="tab-completed" class="tab-content">
            @if($completed->isNotEmpty())
                <div class="assignments-list">
                    @foreach($completed as $asn)
                        <div class="assignment-card">
                            <div class="card-header">
                                <div>
                                    <span class="course-badge">{{ $asn->course_name }}</span>
                                    <h4 class="assignment-title" style="margin-top: 0.5rem;">{{ $asn->title }}</h4>
                                </div>
                                <div>
                                    <span class="status-badge status-completed">
                                        <i class="fa-solid fa-circle-check" style="margin-left: 0.25rem;"></i> تم التسليم
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                @if($asn->file_path)
                                    <a href="{{ asset('storage/' . $asn->file_path) }}" class="btn-download" target="_blank">
                                        <i class="fa-solid fa-download"></i> تحميل ملف الواجب ({{ $asn->file_name ?? 'تحميل' }})
                                    </a>
                                @else
                                    <span style="font-style: italic; opacity: 0.6;">لا يوجد ملف مرفق للواجب.</span>
                                @endif
                            </div>
                            
                            <div class="card-footer">
                                <div class="meta-item">
                                    <i class="fa-regular fa-calendar"></i>
                                    <span>تاريخ الاستحقاق: {{ \Carbon\Carbon::parse($asn->due_date)->format('Y-m-d H:i') }}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-star"></i>
                                    <span>الدرجة المستحقة: 
                                        @if($asn->grade !== null)
                                            <strong style="color: #10b981;">{{ $asn->grade }} / {{ $asn->max_points }}</strong>
                                        @else
                                            <strong>-- / {{ $asn->max_points }}</strong>
                                        @endif
                                    </span>
                                </div>
                                @if($asn->submitted_at)
                                    <div class="meta-item">
                                        <i class="fa-solid fa-check"></i>
                                        <span>تم التسليم في: {{ \Carbon\Carbon::parse($asn->submitted_at)->format('Y-m-d H:i') }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            @if($asn->feedback)
                                <div class="feedback-box">
                                    <div class="feedback-title">
                                        <i class="fa-solid fa-comment-dots"></i> ملاحظات المعلم:
                                    </div>
                                    <p style="margin: 0; color: var(--text-secondary);">{{ $asn->feedback }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="fa-solid fa-circle-xmark" style="color: var(--text-secondary);"></i>
                    <h4>لا توجد واجبات مكتملة</h4>
                    <p>لم يتم تسليم أي واجبات بعد من قِبل الابن.</p>
                </div>
            @endif
        </div>

        <!-- Missed Tab -->
        <div id="tab-missed" class="tab-content">
            @if($missed->isNotEmpty())
                <div class="assignments-list">
                    @foreach($missed as $asn)
                        <div class="assignment-card">
                            <div class="card-header">
                                <div>
                                    <span class="course-badge">{{ $asn->course_name }}</span>
                                    <h4 class="assignment-title" style="margin-top: 0.5rem;">{{ $asn->title }}</h4>
                                </div>
                                <div>
                                    <span class="status-badge status-missed">
                                        <i class="fa-solid fa-triangle-exclamation" style="margin-left: 0.25rem;"></i> فائت
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                @if($asn->file_path)
                                    <a href="{{ asset('storage/' . $asn->file_path) }}" class="btn-download" target="_blank">
                                        <i class="fa-solid fa-download"></i> تحميل ملف الواجب ({{ $asn->file_name ?? 'تحميل' }})
                                    </a>
                                @else
                                    <span style="font-style: italic; opacity: 0.6;">لا يوجد ملف مرفق للواجب.</span>
                                @endif
                            </div>
                            
                            <div class="card-footer">
                                <div class="meta-item">
                                    <i class="fa-regular fa-calendar"></i>
                                    <span>تاريخ الاستحقاق: {{ \Carbon\Carbon::parse($asn->due_date)->format('Y-m-d H:i') }}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-star"></i>
                                    <span>الدرجة المستحقة: <strong>-- / {{ $asn->max_points }}</strong></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="fa-solid fa-thumbs-up" style="color: #10b981;"></i>
                    <h4>لا توجد واجبات فائتة</h4>
                    <p>رائع جداً! تم تسليم كافة الواجبات قبل انتهاء مهلة التسليم.</p>
                </div>
            @endif
        </div>
    @else
        <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color);">
            <i class="fa-solid fa-book-open" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.4; margin-bottom: 1rem; display: block;"></i>
            <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">لا يوجد واجبات مسجلة</h4>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">لم يتم تكليف الابن {{ $selected_child->full_name }} بأي واجبات دراسية في المواد المسجلة حتى الآن.</p>
        </div>
    @endif
@endif

@endsection

@push('scripts')
<script>
function switchTab(tabId, event) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.remove('active');
    });
    // Show selected tab content
    document.getElementById('tab-' + tabId).classList.add('active');
    
    // Deactivate all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    // Activate clicked button
    event.currentTarget.classList.add('active');
}
</script>
@endpush
