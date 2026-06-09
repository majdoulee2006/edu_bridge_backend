@extends('layouts.teacher')

@section('title', 'أدوات المربي')
@section('subtitle', 'إدارة الحضور والتقارير السلوكية الخاصة بالدورات التي تشرف عليها')

@push('styles')
<style>
    .page-subtitle { color: var(--text-secondary); font-size: 1rem; margin-top: -1.5rem; margin-bottom: 2rem; }

    .advisor-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
        border-top: 4px solid var(--accent-color);
    }
    .advisor-card h3 { font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-primary); }
    
    .form-group { margin-bottom: 1rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.9rem; color: var(--text-secondary); }
    .form-input {
        width: 100%; padding: 0.75rem 1rem; border-radius: 0.75rem;
        border: 1px solid var(--border-color); background: var(--bg-primary);
        color: var(--text-primary); font-family: inherit; font-size: 0.95rem;
    }
    .form-input:focus { outline: none; border-color: var(--accent-color); }
    
    .btn-submit {
        background: var(--accent-color); color: #1a1a1a;
        padding: 0.75rem 1.5rem; border-radius: 0.75rem;
        border: none; font-weight: 700; cursor: pointer;
        font-size: 1rem; font-family: inherit; display: inline-flex; align-items: center; gap: 0.5rem;
    }

    .student-list {
        background: var(--bg-primary);
        border-radius: 0.75rem;
        border: 1px solid var(--border-color);
        max-height: 250px; overflow-y: auto; padding: 0.5rem;
    }
    .student-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.75rem; border-bottom: 1px solid var(--border-color);
    }
    .student-item:last-child { border-bottom: none; }
    .student-name { font-weight: 700; font-size: 0.95rem; }
    .student-uid { font-size: 0.8rem; color: var(--text-secondary); }

    .attendance-toggle { display: flex; gap: 0.5rem; }
    .attendance-toggle label {
        padding: 0.3rem 0.7rem; border-radius: 0.5rem; cursor: pointer; font-size: 0.8rem; font-weight: 700; border: 1px solid var(--border-color); transition: all 0.2s;
    }
    .attendance-toggle input[type="radio"] { display: none; }
    .attendance-toggle input[value="present"]:checked + label { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .attendance-toggle input[value="absent"]:checked + label { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .attendance-toggle input[value="late"]:checked + label { background: #fffbeb; color: #d97706; border-color: #fde68a; }

    .empty-state { text-align: center; padding: 3rem; color: var(--text-secondary); background: var(--bg-secondary); border-radius: 1.25rem; }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }
</style>
@endpush

@section('content')

    @if(session('success'))
        <div style="background:#f0fdf4;color:#16a34a;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:#fef2f2;color:#dc2626;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;">{{ session('error') }}</div>
    @endif

    @if($advisorCourses->isEmpty())
        <div class="empty-state">
            <div><i class="fa-solid fa-user-tie"></i></div>
            <p>لست معيناً كمربي لأي دورة حالياً.</p>
        </div>
    @else
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            
            {{-- رفع تقرير سلوكي --}}
            <div class="advisor-card">
                <h3><i class="fa-solid fa-file-contract"></i> رفع تقرير سلوكي</h3>
                <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">قم برفع تقرير سلوكي مباشر عن أي طالب في الدورات التي تشرف عليها ليتم إرساله فوراً لولي الأمر.</p>
                
                <form action="{{ route('teacher.advisor.report') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">الطالب <span style="color:#ef4444">*</span></label>
                        <select name="student_id" required class="form-input">
                            <option value="" disabled selected>اختر الطالب</option>
                            @foreach($students as $student)
                                <option value="{{ $student->student_id }}">{{ $student->full_name }} ({{ $student->university_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">تفاصيل التقرير السلوكي <span style="color:#ef4444">*</span></label>
                        <textarea name="behavioral_notes" required class="form-input" style="min-height: 120px; resize: vertical;" placeholder="اكتب ملاحظاتك حول سلوك الطالب هنا..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i> إرسال لولي الأمر
                    </button>
                </form>
            </div>

            {{-- تسجيل الحضور للمربي --}}
            <div class="advisor-card">
                <h3><i class="fa-solid fa-clipboard-user"></i> سجل تفقد المربي (الحضور اليومي)</h3>
                <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">قم بتسجيل الحضور الخاص بالمربي للدورات التي تشرف عليها.</p>
                
                <form action="{{ route('teacher.advisor.attendance') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">الدورة <span style="color:#ef4444">*</span></label>
                        <select name="course_id" required class="form-input">
                            <option value="" disabled selected>اختر الدورة</option>
                            @foreach($advisorCourses as $course)
                                <option value="{{ $course->course_id }}">{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">تاريخ الجلسة <span style="color:#ef4444">*</span></label>
                        <input type="date" name="date" required value="{{ date('Y-m-d') }}" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">الطلاب <span style="color:#ef4444">*</span></label>
                        <div class="student-list">
                            @foreach($students as $student)
                                <div class="student-item">
                                    <div>
                                        <div class="student-name">{{ $student->full_name }}</div>
                                        <div class="student-uid">{{ $student->university_id }}</div>
                                    </div>
                                    <div class="attendance-toggle">
                                        <input type="radio" name="attendance[{{ $student->student_id }}]" value="present" id="att_present_{{ $student->student_id }}" checked>
                                        <label for="att_present_{{ $student->student_id }}">حاضر</label>
                                        
                                        <input type="radio" name="attendance[{{ $student->student_id }}]" value="late" id="att_late_{{ $student->student_id }}">
                                        <label for="att_late_{{ $student->student_id }}">متأخر</label>
                                        
                                        <input type="radio" name="attendance[{{ $student->student_id }}]" value="absent" id="att_absent_{{ $student->student_id }}">
                                        <label for="att_absent_{{ $student->student_id }}">غائب</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit" style="background:#2563eb; color:#fff;">
                        <i class="fa-solid fa-save"></i> حفظ الحضور
                    </button>
                </form>
            </div>

        </div>
    @endif
@endsection
