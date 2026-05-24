@extends('layouts.teacher')
@section('title', 'الواجبات والمشاريع')

@push('styles')
<style>
    .tab-bar { display: flex; gap: 0.5rem; background: var(--bg-secondary); border-radius: 1rem; padding: 0.4rem; margin-bottom: 1.5rem; width: fit-content; }
    .tab-btn { padding: 0.5rem 1.5rem; border-radius: 0.75rem; border: none; background: transparent; color: var(--text-secondary); font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s; }
    .tab-btn.active { background: var(--accent-color); color: #1a1a1a; }

    .assignment-card { background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.25rem 1.5rem; box-shadow: var(--shadow); margin-bottom: 0.75rem; border-right: 4px solid var(--accent-color); }
    .status-badge { padding: 0.2rem 0.75rem; border-radius: 2rem; font-size: 0.8rem; font-weight: 700; }

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
        padding: 0.2rem 0.65rem; border-radius: 2rem;
        font-size: 0.78rem; font-weight: 700;
        text-decoration: none;
    }
    .attach-image    { background: #eff6ff; color: #1d4ed8; }
    .attach-video    { background: #fdf4ff; color: #7e22ce; }
    .attach-document { background: #fefce8; color: #854d0e; }
</style>
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div class="tab-bar">
            <button class="tab-btn active" onclick="switchTab('all', this)">الكل</button>
            <button class="tab-btn" onclick="switchTab('submissions', this)">الردود</button>
        </div>
        <button onclick="document.getElementById('add-modal').classList.add('active')"
                style="background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; padding: 0.6rem 1.25rem; font-weight: 700; cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-plus"></i> واجب جديد
        </button>
    </div>

    {{-- All Assignments --}}
    <div id="tab-all">
        @forelse($assignments as $a)
            <div class="assignment-card">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                            @if($a->graded_count >= $a->submissions_count && $a->submissions_count > 0)
                                <span class="status-badge" style="background: hsl(120,70%,90%); color: hsl(120,50%,30%);">تم التصحيح</span>
                            @elseif($a->submissions_count > 0)
                                <span class="status-badge" style="background: hsl(30,70%,90%); color: hsl(30,50%,30%);">قيد التصحيح</span>
                            @else
                                <span class="status-badge" style="background: var(--accent-color); color: #1a1a1a;">نشط</span>
                            @endif
                            <span style="font-weight: 800; font-size: 1rem;">{{ $a->title }}</span>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.4rem;">
                            {{ $a->course_title }}
                        </div>
                        <div style="display: flex; gap: 1.25rem; flex-wrap: wrap; color: var(--text-secondary); font-size: 0.82rem;">
                            <span><i class="fa-solid fa-calendar"></i> {{ \Carbon\Carbon::parse($a->due_date)->format('Y-m-d') }}</span>
                            <span><i class="fa-solid fa-users"></i> {{ $a->submissions_count }} تسليم</span>
                            <span><i class="fa-solid fa-check-circle"></i> {{ $a->graded_count }} مصحح</span>
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
                            <div style="margin-top: 0.5rem;">
                                <a href="{{ asset('storage/' . $a->file_path) }}"
                                   target="_blank"
                                   class="attach-chip {{ $chipClass }}">
                                    <i class="fa-solid {{ $chipIcon }}"></i>
                                    {{ $a->file_name ?? 'المرفق' }}
                                </a>
                            </div>
                        @endif
                    </div>

                    <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                        <a href="{{ route('teacher.assignments.submissions', $a->assignment_id) }}"
                           style="background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 0.5rem; padding: 0.4rem 0.75rem; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="fa-solid fa-eye"></i> الردود
                        </a>
                        <form action="{{ route('teacher.assignments.delete', $a->assignment_id) }}" method="POST"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا الواجب؟')">
                            @csrf
                            <button type="submit"
                                    style="background: hsl(0,70%,95%); border: none; color: hsl(0,50%,40%); border-radius: 0.5rem; padding: 0.4rem 0.75rem; cursor: pointer; font-size: 0.85rem;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
                <i class="fa-solid fa-file-circle-plus" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; color: var(--accent-color);"></i>
                لا توجد واجبات حتى الآن
            </div>
        @endforelse
    </div>

    {{-- Submissions Tab --}}
    <div id="tab-submissions" style="display: none;">
        <div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
            <i class="fa-solid fa-inbox" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; color: var(--accent-color);"></i>
            اضغط على "الردود" في أي واجب لعرض تسليمات الطلاب
        </div>
    </div>


    {{-- ===== Add Assignment Modal ===== --}}
    <div id="add-modal" class="modal-overlay">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="font-weight: 800; font-size: 1.1rem;">
                    <i class="fa-solid fa-file-pen" style="color: var(--accent-color);"></i>
                    إضافة واجب جديد
                </h3>
                <button onclick="closeAddModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('teacher.assignments.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- المادة --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">المادة</label>
                    <select name="course_id" class="form-input" required>
                        <option value="">← اختر المادة</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->course_id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- العنوان --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">عنوان الواجب</label>
                    <input type="text" name="title" class="form-input" placeholder="أدخل عنوان الواجب" required>
                </div>

                {{-- الوصف --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">وصف الواجب</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="اكتب تفاصيل الواجب هنا..." required style="resize: vertical;"></textarea>
                </div>

                {{-- التاريخ والدرجة --}}
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">تاريخ التسليم</label>
                        <input type="date" name="due_date" class="form-input" required>
                    </div>
                    <div style="flex: 1;">
                        <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">الدرجة الكبرى</label>
                        <input type="number" name="max_points" class="form-input" value="100" min="1" required>
                    </div>
                </div>

                {{-- رفع ملف --}}
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">
                        <i class="fa-solid fa-paperclip" style="color: var(--accent-color);"></i>
                        ملف مرفق (اختياري)
                    </label>

                    <div class="upload-area" id="upload-area"
                         ondragover="this.classList.add('drag-over')"
                         ondragleave="this.classList.remove('drag-over')"
                         ondrop="this.classList.remove('drag-over')">
                        <input type="file" name="attachment" id="file-input"
                               accept="image/*,video/*,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
                               onchange="previewFile(this)">
                        <div id="upload-placeholder">
                            <div class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                            <div class="upload-text">اسحب وأفلت الملف هنا، أو اضغط للاختيار</div>
                            <div class="upload-hint">صور · فيديو · PDF · Word · Excel · ZIP — حتى 50 ميجابايت</div>
                        </div>
                    </div>

                    {{-- معاينة الملف المختار --}}
                    <div class="file-preview" id="file-preview">
                        <span class="file-preview-icon" id="preview-icon">📎</span>
                        <span class="file-preview-name" id="preview-name"></span>
                        <span class="file-preview-size" id="preview-size" style="color: var(--text-secondary); font-size: 0.78rem; white-space: nowrap;"></span>
                        <button type="button" class="file-preview-remove" onclick="removeFile()" title="حذف الملف">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                {{-- أزرار --}}
                <div style="display: flex; gap: 1rem;">
                    <button type="submit"
                            style="flex: 1; padding: 0.85rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-family: inherit; font-size: 1rem;">
                        <i class="fa-solid fa-floppy-disk"></i> حفظ الواجب
                    </button>
                    <button type="button" onclick="closeAddModal()"
                            style="flex: 1; padding: 0.85rem; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 1rem;">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
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
    image:    { icon: '🖼️', label: 'صورة' },
    video:    { icon: '🎬', label: 'فيديو' },
    pdf:      { icon: '📄', label: 'PDF' },
    word:     { icon: '📝', label: 'Word' },
    excel:    { icon: '📊', label: 'Excel' },
    zip:      { icon: '📦', label: 'ZIP' },
    default:  { icon: '📎', label: 'ملف' },
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
