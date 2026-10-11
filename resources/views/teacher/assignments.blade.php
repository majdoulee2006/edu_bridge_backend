@extends('layouts.teacher')
@section('title', __('messages.assignments_distributed_by_subject_major_year'))

@push('styles')
<style>
    .tab-bar { display: flex; gap: 0.5rem; background: var(--bg-secondary); border-radius: 1rem; padding: 0.4rem; margin-bottom: 1.5rem; width: fit-content; }
    .tab-btn { padding: 0.5rem 1.5rem; border-radius: 0.75rem; border: none; background: transparent; color: var(--text-secondary); font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s; }
    .tab-btn.active { background: var(--accent-color); color: #1a1a1a; }

    .course-accordion-item {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        margin-bottom: 1.25rem;
        overflow: hidden;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        transition: all 0.2s ease;
    }
    .course-accordion-item:hover {
        border-color: var(--accent-color);
    }
    .course-accordion-header {
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        background: var(--bg-secondary);
        transition: background 0.2s;
    }
    .course-accordion-header:hover {
        background: var(--bg-primary);
    }

    .assignment-card {
        background: var(--bg-primary);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border-right: 4px solid var(--accent-color);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .assignment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 2rem; font-size: 0.78rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.3rem; }

    .badge-pending   { background: hsl(30,70%,90%);   color: hsl(30,50%,30%); }
    .badge-overdue   { background: hsl(0,70%,90%);    color: hsl(0,50%,30%); }
    .badge-submitted { background: hsl(200,70%,90%);  color: hsl(200,50%,30%); }
    .badge-graded    { background: hsl(120,70%,90%);  color: hsl(120,50%,30%); }

    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-card { background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; width: 100%; max-width: 560px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }

    .form-input { width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; }
    .form-input:focus { outline: none; border-color: var(--accent-color); }

    /* File Upload Area */
    .upload-area {
        border: 2px dashed var(--border-color);
        border-radius: 0.875rem;
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
        position: relative;
        background: var(--bg-primary);
    }
    .upload-area:hover, .upload-area.drag-over {
        border-color: var(--accent-color);
        background: color-mix(in srgb, var(--accent-color) 6%, var(--bg-primary));
    }
    .upload-area input[type="file"] {
        position: absolute; inset: 0; width: 100%; height: 100%;
        opacity: 0; cursor: pointer;
    }
    .upload-icon { font-size: 2rem; color: var(--accent-color); margin-bottom: 0.5rem; }
    .upload-text { font-weight: 600; font-size: 0.95rem; margin-bottom: 0.25rem; }
    .upload-hint { font-size: 0.78rem; color: var(--text-secondary); }

    /* File preview */
    .file-preview {
        display: none; align-items: center; gap: 0.75rem;
        background: var(--bg-primary); border-radius: 0.75rem;
        padding: 0.75rem 1rem; margin-top: 0.5rem;
        border: 1px solid var(--border-color);
    }
    .file-preview.visible { display: flex; }
    .file-preview-icon { font-size: 1.4rem; flex-shrink: 0; }
    .file-preview-name { flex: 1; font-size: 0.88rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .file-preview-remove { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1rem; }

    /* Attachment in card */
    .attach-chip {
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.25rem 0.75rem; border-radius: 2rem;
        font-size: 0.78rem; font-weight: 700;
        text-decoration: none;
    }
    .attach-image    { background: #eff6ff; color: #1d4ed8; }
    .attach-video    { background: #fdf4ff; color: #7e22ce; }
    .attach-document { background: #fefce8; color: #854d0e; }

    /* Students status list */
    .students-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.85rem;
        font-size: 0.85rem;
    }
    .students-table th, .students-table td {
        padding: 0.65rem 0.85rem;
        text-align: right;
        border-bottom: 1px solid var(--border-color);
    }
    .students-table th {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        font-weight: 700;
    }
</style>
@endpush

@section('content')
@php
    $pendingSubmissionsCount = isset($allSubmissions) ? $allSubmissions->whereNull('grade')->count() : 0;
    $groupedAssignments = $assignments->groupBy('course_id');
@endphp

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div class="tab-bar">
            <button class="tab-btn active" onclick="switchTab('all', this)">{{ __('messages.subjects_and_majors') }}</button>
            <button class="tab-btn" onclick="switchTab('submissions', this)" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                {{ __('messages.responses_and_submissions') }}
                @if($pendingSubmissionsCount > 0)
                    <span style="background: #ef4444; color: #ffffff; font-size: 0.72rem; font-weight: 800; padding: 0.1rem 0.45rem; border-radius: 1rem; min-width: 18px; text-align: center; line-height: 1.2;">{{ $pendingSubmissionsCount }}</span>
                @endif
            </button>
        </div>
        <button onclick="document.getElementById('add-modal').classList.add('active')"
                style="background: linear-gradient(135deg, #ffe600, #facc15); color: #111827; border: none; border-radius: 0.85rem; padding: 0.65rem 1.35rem; font-weight: 800; cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 15px rgba(250, 204, 21, 0.35);">
            <i class="fa-solid fa-plus"></i> {{ __('messages.create_new_assignment') }}
        </button>
    </div>

    {{-- All Assignments Grouped by Course / Specialization / Year --}}
    <div id="tab-all">
        @forelse($courses as $index => $c)
            @php
                $courseAssigns = $groupedAssignments->get($c->course_id, collect());
                $shouldExpand = ($index === 0) || ($courseAssigns->count() > 0);
            @endphp

            <div class="course-accordion-item">
                <!-- Accordion Header -->
                <div class="course-accordion-header" onclick="toggleTeacherAccordion('teacher-course-body-{{ $c->course_id }}', 'teacher-chevron-{{ $c->course_id }}')">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 52px; height: 52px; border-radius: 1rem; background: rgba(234, 179, 8, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-book-open" style="font-size: 1.4rem; color: #eab308;"></i>
                        </div>
                        <div>
                            <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                                {{ $c->title }}
                                <span style="font-size: 0.78rem; background: rgba(234, 179, 8, 0.18); color: #eab308; padding: 0.2rem 0.75rem; border-radius: 1rem; font-weight: 800;">
                                    {{ $c->year_label }}
                                </span>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.3rem; display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap;">
                                <span><i class="fa-solid fa-building-columns" style="color: var(--accent-color);"></i> <strong>{{ __('messages.department:') }}</strong> {{ $c->department_label }}</span>
                                <span>&bull;</span>
                                <span><i class="fa-solid fa-graduation-cap" style="color: var(--accent-color);"></i> <strong>{{ __('messages.major:') }}</strong> {{ $c->programs_list }}</span>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                        <span style="font-size: 0.8rem; font-weight: 700; background: var(--bg-primary); padding: 0.35rem 0.85rem; border-radius: 2rem; color: var(--text-secondary); border: 1px solid var(--border-color);">
                            {{ $courseAssigns->count() }} {{ $courseAssigns->count() == 1 ? __('messages.assignment') : __('messages.assignments') }}
                        </span>
                        <div style="width: 34px; height: 34px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);">
                            <i id="teacher-chevron-{{ $c->course_id }}" class="fa-solid fa-chevron-down toggle-icon" style="transition: transform 0.3s ease; color: var(--text-secondary); transform: {{ $shouldExpand ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
                        </div>
                    </div>
                </div>

                <!-- Accordion Body -->
                <div id="teacher-course-body-{{ $c->course_id }}" class="course-accordion-body" style="display: {{ $shouldExpand ? 'block' : 'none' }}; padding: 0 1.25rem 1.25rem 1.25rem; border-top: 1px solid var(--border-color);">
                    <div style="padding-top: 1.25rem;">
                        @forelse($courseAssigns as $a)
                            <div class="assignment-card">
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                                    <div style="flex: 1; min-width: 250px;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                                            @if($a->graded_count >= $a->submissions_count && $a->submissions_count > 0)
                                                <span class="status-badge badge-graded"><i class="fa-solid fa-check-double"></i>{{ __('messages.all_graded') }}</span>
                                            @elseif($a->submissions_count > 0)
                                                <span class="status-badge badge-submitted"><i class="fa-solid fa-clock"></i>{{ __('messages.under_grading') }}</span>
                                            @else
                                                <span class="status-badge badge-pending"><i class="fa-solid fa-hourglass-start"></i>{{ __('messages.active_waiting_student_submission') }}</span>
                                            @endif
                                            <span style="font-weight: 800; font-size: 1.05rem; color: var(--text-primary);">{{ $a->title }}</span>
                                        </div>

                                        <div style="color: var(--text-secondary); font-size: 0.86rem; margin-bottom: 0.6rem; line-height: 1.5;">
                                            {{ Str::limit($a->description, 200) }}
                                        </div>

                                        <div style="display: flex; gap: 1.25rem; flex-wrap: wrap; color: var(--text-secondary); font-size: 0.82rem;">
                                            <span><i class="fa-solid fa-calendar" style="color: var(--accent-color);"></i>{{ __('messages.due_date:') }}<strong>{{ \Carbon\Carbon::parse($a->due_date)->format('Y-m-d') }}</strong></span>
                                            <span><i class="fa-solid fa-users" style="color: #3b82f6;"></i> <strong>{{ $a->submissions_count }}</strong>{{ __('messages.submission') }}</span>
                                            <span><i class="fa-solid fa-check-circle" style="color: #22c55e;"></i> <strong>{{ $a->graded_count }}</strong>{{ __('messages.graded') }}</span>
                                            <span><i class="fa-solid fa-star" style="color: #eab308;"></i>{{ __('messages.maximum_mark:') }}<strong>{{ $a->max_points }}</strong></span>
                                        </div>

                                        {{-- Attachment chip --}}
                                        @if($a->file_path)
                                            @php
                                                $chipClass = match($a->file_type) {
                                                    'image'    => 'attach-image',
                                                    'video'    => 'attach-video',
                                                    default    => 'attach-document',
                                                };
                                                $chipIcon = match($a->file_type) {
                                                    'image'    => 'fa-image',
                                                    'video'    => 'fa-video',
                                                    default    => 'fa-file-lines',
                                                };
                                            @endphp
                                            <div style="margin-top: 0.6rem;">
                                                <a href="{{ asset('storage/' . $a->file_path) }}"
                                                   target="_blank"
                                                   class="attach-chip {{ $chipClass }}">
                                                    <i class="fa-solid {{ $chipIcon }}"></i>
                                                    {{ __('messages.assignment_attachment:') }} {{ $a->file_name ?? __('messages.the_file') }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    <div style="display: flex; gap: 0.5rem; flex-shrink: 0; align-items: center;">
                                        <a href="{{ route('teacher.assignments.submissions', $a->assignment_id) }}"
                                           style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 0.65rem; padding: 0.5rem 0.9rem; font-size: 0.85rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 0.4rem; transition: background 0.2s;">
                                            <i class="fa-solid fa-eye" style="color: var(--accent-color);"></i> {{ __('messages.full_responses') }} ({{ $a->submissions_count }})
                                        </a>
                                        <form action="{{ route('teacher.assignments.delete', $a->assignment_id) }}" method="POST"
                                              onsubmit="return confirm("{{ __('messages.confirm_delete_assignment') }}")">
                                            @csrf
                                            <button type="submit"
                                                    style="background: hsl(0,70%,95%); border: none; color: hsl(0,50%,40%); border-radius: 0.65rem; padding: 0.5rem 0.8rem; cursor: pointer; font-size: 0.85rem; font-weight: 700;" title="{{ __('messages.delete_assignment') }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Students Individual Status Section --}}
                                <div style="margin-top: 1rem; border-top: 1px dashed var(--border-color); padding-top: 0.85rem;">
                                    <button onclick="toggleStudentTable('students-table-{{ $a->assignment_id }}', 'students-chevron-{{ $a->assignment_id }}')"
                                        style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); padding: 0.45rem 0.9rem; border-radius: 0.65rem; font-weight: 700; font-size: 0.83rem; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 0.5rem;">
                                        <i class="fa-solid fa-users-gear" style="color: var(--accent-color);"></i>
                                        {{ __('messages.list_of_students_in_this_major_required_assignment') }} ({{ $a->student_statuses->count() }} {{ __('messages.student') }})
                                        <i id="students-chevron-{{ $a->assignment_id }}" class="fa-solid fa-chevron-down" style="transition: transform 0.25s ease; margin-right: 0.3rem;"></i>
                                    </button>

                                    <div id="students-table-{{ $a->assignment_id }}" style="display: none; margin-top: 0.75rem; overflow-x: auto;">
                                        <table class="students-table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>{{ __('messages.student_name') }}</th>
                                                    <th>{{ __('messages.assignment_submission_status') }}</th>
                                                    <th>{{ __('messages.evaluated_mark') }}</th>
                                                    <th>{{ __('messages.actions_details') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($a->student_statuses as $stIndex => $st)
                                                    <tr>
                                                        <td>{{ $stIndex + 1 }}</td>
                                                        <td style="font-weight: 700; color: var(--text-primary);">{{ $st->student_name }}</td>
                                                        <td>
                                                            @if($st->status_key === 'graded')
                                                                <span class="status-badge badge-graded">
                                                                    <i class="fa-solid fa-circle-check"></i> {{ __('messages.graded') }} </span>
                                                            @elseif($st->status_key === 'submitted')
                                                                <span class="status-badge badge-submitted">
                                                                    <i class="fa-solid fa-clock"></i> {{ __('messages.awaiting_grading') }} </span>
                                                            @elseif($st->status_key === 'overdue')
                                                                <span class="status-badge badge-overdue">
                                                                    <i class="fa-solid fa-circle-xmark"></i> {{ __('messages.missed_not_submitted') }}
                                                                </span>
                                                            @else
                                                                <span class="status-badge badge-pending">
                                                                    <i class="fa-solid fa-hourglass-half"></i> {{ __('messages.waiting_for_submission') }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($st->status_key === 'graded' && $st->submission)
                                                                <span style="font-size: 0.95rem; font-weight: 800; color: #22c55e;">
                                                                    {{ $st->submission->grade }} <span style="font-size: 0.78rem; color: var(--text-secondary);">/ {{ $a->max_points }}</span>
                                                                </span>
                                                            @else
                                                                <span style="color: var(--text-secondary); font-size: 0.8rem;">--</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($st->submission)
                                                                <a href="{{ route('teacher.assignments.submissions', $a->assignment_id) }}" 
                                                                   style="color: var(--accent-color); font-weight: 700; text-decoration: none; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                                                                    <i class="fa-solid fa-pen-to-square"></i> 
                                                                    {{ $st->status_key === 'graded' ? __('messages.edit_grade') : __('messages.grade_response') }}
                                                                </a>
                                                            @else
                                                                <span style="color: var(--text-secondary); font-size: 0.8rem;">{{ __('messages.no_response') }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 1rem;">{{ __('messages.no_students_registered_in_major_currently') }}</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 2rem; color: var(--text-secondary); font-size: 0.9rem;">
                                <i class="fa-solid fa-folder-open" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
                                {{ __('messages.no_assignments_added_for_subject_major_yet') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
                <i class="fa-solid fa-file-circle-plus" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; color: var(--accent-color);"></i>{{ __('messages.no_subjects_registered_currently') }}</div>
        @endforelse
    </div>

    {{-- Submissions Tab --}}
    <div id="tab-submissions" style="display: none;">
        @php
            $groupedSubmissions = isset($allSubmissions) ? $allSubmissions->groupBy('assignment_title') : collect();
        @endphp

        @forelse($groupedSubmissions as $assignmentTitle => $subs)
            <div style="background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; box-shadow: var(--shadow); border-right: 4px solid var(--accent-color);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                    <div>
                        <h4 style="font-weight: 800; font-size: 1.05rem; color: var(--text-primary); margin: 0;">{{ $assignmentTitle }}</h4>
                        <span style="color: var(--text-secondary); font-size: 0.82rem;">{{ $subs->first()->course_title ?? '' }} &nbsp;|&nbsp; {{ $subs->count() }} {{ __('messages.submission') }}</span>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @foreach($subs as $s)
                        @php
                            $fileUrl = $s->file_path ? '/storage/' . $s->file_path : '';
                            $ext = $s->file_path ? pathinfo($s->file_path, PATHINFO_EXTENSION) : '';
                            $extStr = $ext ? '.' . $ext : '';
                            $cleanStudent = str_replace(' ', '_', trim($s->student_name));
                            $cleanTitle = str_replace(' ', '_', trim($s->assignment_title));
                            $fileName = $s->file_path ? ("solution_assignment_" . $cleanStudent . "_" . $cleanTitle . $extStr) : '';
                            
                            $isPassTeacher = $s->grade !== null && ($s->grade >= (($s->max_points ?? 100) / 2));
                            $badgeBgTeacher = $isPassTeacher ? 'hsl(120,70%,90%)' : 'hsl(0,70%,90%)';
                            $badgeColorTeacher = $isPassTeacher ? 'hsl(120,50%,30%)' : 'hsl(0,50%,30%)';
                        @endphp
                        <div style="background: var(--bg-primary); border-radius: 0.875rem; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; border: 1px solid var(--border-color); flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 0.85rem; flex: 1; min-width: 240px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--accent-color); color: #1a1a1a; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    {{ mb_substr($s->student_name, 0, 1) }}
                                </div>
                                <div>
                                    <div style="font-weight: 700; font-size: 0.95rem;">{{ $s->student_name }}</div>
                                    <div style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.15rem; display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                                        <span><i class="fa-solid fa-clock"></i> {{ \Carbon\Carbon::parse($s->submitted_at)->format('Y-m-d h:i A') }}</span>
                                        @if($s->file_path)
                                            <a href="{{ $fileUrl }}" target="_blank" download="{{ $fileName }}"
                                               style="color: var(--accent-color); font-weight: 700; text-decoration: none;" onclick="event.stopPropagation();">
                                                <i class="fa-solid fa-paperclip"></i>{{ __('messages.download_solution') }}</a>
                                        @endif
                                    </div>
                                    @if(!empty($s->solution_text))
                                        <div style="font-size: 0.85rem; color: var(--text-primary); margin-top: 0.35rem; background: rgba(255,255,255,0.05); padding: 0.4rem 0.6rem; border-radius: 0.5rem;">
                                            <strong>{{ __('messages.answer_text:') }}</strong> {{ $s->solution_text }}
                                        </div>
                                    @endif
                                    @if(!empty($s->student_notes))
                                        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem; font-style: italic;">
                                            <i class="fa-solid fa-comment-dots"></i> <strong>{{ __('messages.student_notes') }}</strong> {{ $s->student_notes }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                @if($s->grade !== null)
                                    <span style="background: {{ $badgeBgTeacher }}; color: {{ $badgeColorTeacher }}; padding: 0.25rem 0.75rem; border-radius: 2rem; font-weight: 800; font-size: 0.85rem;">
                                        {{ $s->grade }}/{{ $s->max_points }}
                                    </span>
                                @else
                                    <span style="background: hsl(30,70%,90%); color: hsl(30,50%,30%); padding: 0.25rem 0.75rem; border-radius: 2rem; font-weight: 800; font-size: 0.85rem;"> {{ __('messages.awaiting_grading') }} </span>
                                @endif
                                <a href="{{ route('teacher.assignments.submissions', $s->assignment_id) }}" 
                                   style="background: var(--accent-color); color: #1a1a1a; border-radius: 0.5rem; padding: 0.4rem 0.85rem; font-size: 0.82rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;">
                                    <i class="fa-solid fa-pen-to-square"></i>{{ __('messages.grade') }}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 4rem; background: var(--bg-secondary); border-radius: 1.5rem; color: var(--text-secondary);">
                <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--accent-color);"></i>
                <p style="font-size: 1.05rem; font-weight: 600;">{{ __('messages.no_submissions_available_yet') }}</p>
            </div>
        @endforelse
    </div>


    {{-- ===== Add Assignment Modal ===== --}}
    <div id="add-modal" class="modal-overlay">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="font-weight: 800; font-size: 1.1rem;">
                    <i class="fa-solid fa-file-pen" style="color: var(--accent-color);"></i>
                    {{ __('messages.create_new_assignment') }}
                </h3>
                <button onclick="closeAddModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('teacher.assignments.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- المادة والتخصص --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">{{ __('messages.target_subject_branch_major') }}</label>
                    <select name="course_id" class="form-input" required>
                        <option value="">{{ __('messages.select_subject_and_major') }}</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->course_id }}">{{ $c->title }} — ({{ $c->department_label }} | {{ $c->programs_list }}) — {{ $c->year_label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- العنوان --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">{{ __('messages.assignment_title') }}</label>
                    <input type="text" name="title" class="form-input" placeholder="{{ __('messages.enter_') }}{{ __('messages.assignment_title') }}" required>
                </div>

                {{-- الوصف --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">{{ __('messages.assignment_description') }}</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="{{ __('messages.write_assignment_details_here') }}" required style="resize: vertical;"></textarea>
                </div>

                {{-- التاريخ والدرجة --}}
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">{{ __('messages.due_date') }}</label>
                        <input type="date" name="due_date" class="form-input" min="{{ date('Y-m-d') }}" required>
                    </div>
                    <div style="flex: 1;">
                        <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">{{ __('messages.maximum_grade') }}</label>
                        <input type="number" name="max_points" class="form-input" value="100" min="1" required>
                    </div>
                </div>

                {{-- رفع ملف --}}
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">
                        <i class="fa-solid fa-paperclip" style="color: var(--accent-color);"></i>{{ __('messages.attached_file_optional') }}</label>

                    <div class="upload-area" id="upload-area"
                         ondragover="this.classList.add('drag-over')"
                         ondragleave="this.classList.remove('drag-over')"
                         ondrop="this.classList.remove('drag-over')">
                        <input type="file" name="attachment" id="file-input"
                               accept="image/*,video/*,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
                               onchange="previewFile(this)">
                        <div id="upload-placeholder">
                            <div class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                            <div class="upload-text">{{ __('messages.drag_and_drop_file_here_or_click_to_select') }}</div>
                            <div class="upload-hint">{{ __('messages.images_video_pdf_word_excel_zip_up_to_50mb') }}</div>
                        </div>
                    </div>

                    {{-- معاينة {{ __('messages.the_file') }} المختار --}}
                    <div class="file-preview" id="file-preview">
                        <span class="file-preview-icon" id="preview-icon">📎</span>
                        <span class="file-preview-name" id="preview-name"></span>
                        <span class="file-preview-size" id="preview-size" style="color: var(--text-secondary); font-size: 0.78rem; white-space: nowrap;"></span>
                        <button type="button" class="file-preview-remove" onclick="removeFile()" title="{{ __('messages.delete_file') }}">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                {{-- أزرار --}}
                <div style="display: flex; gap: 1rem;">
                    <button type="submit"
                            style="flex: 1; padding: 0.85rem; background: linear-gradient(135deg, #ffe600, #facc15); color: #111827; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-family: inherit; font-size: 1rem; box-shadow: 0 4px 15px rgba(250, 204, 21, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i> {{ __('messages.save_assignment') }}
                    </button>
                    <button type="button" onclick="closeAddModal()"
                            style="flex: 1; padding: 0.85rem; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 1rem;"> {{ __('messages.cancel') }} </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
function toggleTeacherAccordion(bodyId, iconId) {
    const body = document.getElementById(bodyId);
    const icon = document.getElementById(iconId);
    if (!body) return;
    
    if (body.style.display === 'none' || body.style.display === '') {
        body.style.display = 'block';
        if (icon) icon.style.transform = 'rotate(180deg)';
    } else {
        body.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}

function toggleStudentTable(tableId, chevronId) {
    const table = document.getElementById(tableId);
    const chevron = document.getElementById(chevronId);
    if (!table) return;

    if (table.style.display === 'none' || table.style.display === '') {
        table.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(180deg)';
    } else {
        table.style.display = 'none';
        if (chevron) chevron.style.transform = 'rotate(0deg)';
    }
}

/* ===== Tabs ===== */
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-all').style.display         = tab === 'all'         ? 'block' : 'none';
    document.getElementById('tab-submissions').style.display = tab === 'submissions' ? 'block' : 'none';
}

/* ===== Modal ===== */
function closeAddModal() {
    document.getElementById('add-modal').classList.remove('active');
    removeFile();
}
document.getElementById('add-modal').addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});

/* ===== File Upload ===== */
const iconMap = {
    image:    { icon: '🖼️', label: "{{ __('messages.image') }}" },
    video:    { icon: '🎬', label: "{{ __('messages.video') }}" },
    pdf:      { icon: '📄', label: 'PDF' },
    word:     { icon: '📝', label: 'Word' },
    excel:    { icon: '📊', label: 'Excel' },
    zip:      { icon: '📦', label: 'ZIP' },
    default:  { icon: '📎', label: "{{ __('messages.document') }}" },
};

function getFileIcon(mime, name) {
    if (mime.startsWith('image/'))  return iconMap.image;
    if (mime.startsWith('video/'))  return iconMap.video;
    if (mime === 'application/pdf') return iconMap.pdf;
    if (mime.includes('word') || name.endsWith('.doc') || name.endsWith('.docx')) return iconMap.word;
    if (mime.includes('excel') || name.endsWith('.xls') || name.endsWith('.xlsx')) return iconMap.excel;
    if (name.endsWith('.zip'))      return iconMap.zip;
    return iconMap.default;
}

function formatSize(bytes) {
    if (bytes < 1024)       return bytes + ' B';
    if (bytes < 1048576)    return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function previewFile(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const info = getFileIcon(file.type, file.name);

    document.getElementById('preview-icon').textContent = info.icon;
    document.getElementById('preview-name').textContent = file.name;
    document.getElementById('preview-size').textContent = formatSize(file.size);
    document.getElementById('upload-placeholder').style.display = 'none';
    document.getElementById('file-preview').classList.add('visible');
}

function removeFile() {
    const input = document.getElementById('file-input');
    input.value = '';
    document.getElementById('upload-placeholder').style.display = 'block';
    document.getElementById('file-preview').classList.remove('visible');
}
</script>
@endpush
