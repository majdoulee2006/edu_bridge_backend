@extends('layouts.teacher')
@section('title', 'المحاضرات')

@push('styles')
<style>
    .lecture-card { background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.25rem 1.5rem; box-shadow: var(--shadow); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 1.25rem; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-card { background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
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

    /* Attachment chip in cards */
    .attach-chip {
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.2rem 0.65rem; border-radius: 2rem;
        font-size: 0.78rem; font-weight: 700;
        text-decoration: none; transition: opacity 0.2s;
    }
    .attach-chip:hover { opacity: 0.8; }
    .attach-image    { background: #eff6ff; color: #1d4ed8; }
    .attach-video    { background: #fdf4ff; color: #7e22ce; }
    .attach-document { background: #fefce8; color: #854d0e; }
</style>
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <p style="color: var(--text-secondary);">{{ $lectures->count() }} محاضرة مضافة</p>
        <button onclick="document.getElementById('add-lecture-modal').classList.add('active')" style="background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; padding: 0.6rem 1.25rem; font-weight: 700; cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-plus"></i> إضافة محاضرة
        </button>
    </div>

    @forelse($lectures as $l)
        <div class="lecture-card">
            <div style="width: 52px; height: 52px; border-radius: 1rem; background: var(--accent-color); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #1a1a1a; flex-shrink: 0;">
                <i class="fa-solid fa-chalkboard"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 700; margin-bottom: 0.25rem;">{{ $l->title }}</div>
                <div style="color: var(--text-secondary); font-size: 0.85rem;">
                    <span>{{ $l->course_title }}</span>
                    &nbsp;|&nbsp;
                    <i class="fa-regular fa-calendar"></i> {{ \Carbon\Carbon::parse($l->created_at)->format('Y-m-d') }}
                </div>
                @if($l->description)
                    <div style="color: var(--text-secondary); font-size: 0.82rem; margin-top: 0.25rem;">{{ Str::limit($l->description, 80) }}</div>
                @endif

                {{-- Attachment chip --}}
                @if($l->file_path)
                    @php
                        $chipClass = match($l->file_type) {
                            'image'    => 'attach-image',
                            'video'    => 'attach-video',
                            default    => 'attach-document',
                        };
                        $chipIcon = match($l->file_type) {
                            'image'    => 'fa-image',
                            'video'    => 'fa-video',
                            default    => 'fa-file-lines',
                        };
                        $chipLabel = match($l->file_type) {
                            'image'    => 'صورة',
                            'video'    => 'فيديو',
                            default    => 'مستند',
                        };
                    @endphp
                    <div style="margin-top: 0.5rem;">
                        <a href="{{ asset('storage/' . $l->file_path) }}"
                           target="_blank"
                           class="attach-chip {{ $chipClass }}">
                            <i class="fa-solid {{ $chipIcon }}"></i>
                            {{ $l->file_name ?? $chipLabel }}
                        </a>
                    </div>
                @endif
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="openEditModal({{ $l->lesson_id }}, '{{ addslashes($l->title) }}', '{{ $l->course_id }}', '{{ addslashes($l->description ?? '') }}')"
                    style="background: hsl(220,70%,95%); border: none; color: hsl(220,50%,40%); border-radius: 0.5rem; padding: 0.5rem 0.75rem; cursor: pointer;">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <a href="{{ route('teacher.lectures.delete', $l->lesson_id) }}" 
                   onclick="return confirm('هل أنت متأكد من حذف هذه المحاضرة؟')"
                   style="background: hsl(0,70%,95%); border: none; color: hsl(0,50%,40%); border-radius: 0.5rem; padding: 0.5rem 0.75rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;">
                    <i class="fa-solid fa-trash"></i>
                </a>
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 4rem; background: var(--bg-secondary); border-radius: 1.5rem; color: var(--text-secondary);">
            <i class="fa-solid fa-book-open" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--accent-color);"></i>
            <p style="font-size: 1.1rem; font-weight: 600;">لا توجد محاضرات مضافة بعد</p>
        </div>
    @endforelse

    <!-- Edit Lecture Modal -->
    <div id="edit-lecture-modal" class="modal-overlay">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="font-weight: 800;">
                    <i class="fa-solid fa-pen" style="color: var(--accent-color);"></i>
                    تعديل المحاضرة
                </h3>
                <button onclick="document.getElementById('edit-lecture-modal').classList.remove('active')" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="edit-lecture-form" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="POST">

                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">عنوان المحاضرة</label>
                    <input type="text" name="title" id="edit-title" class="form-input" required>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">المادة</label>
                    <select name="course_id" id="edit-course_id" class="form-input" required>
                        @foreach($courses as $c)
                            <option value="{{ $c->course_id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">وصف المحاضرة (اختياري)</label>
                    <textarea name="description" id="edit-description" class="form-input" rows="3" style="resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="flex: 1; padding: 0.85rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-family: inherit; font-size: 1rem;">
                        <i class="fa-solid fa-save"></i> حفظ التعديلات
                    </button>
                    <button type="button" onclick="document.getElementById('edit-lecture-modal').classList.remove('active')" style="flex: 1; padding: 0.85rem; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 1rem;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Lecture Modal -->
    <div id="add-lecture-modal" class="modal-overlay">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="font-weight: 800;">
                    <i class="fa-solid fa-chalkboard" style="color: var(--accent-color);"></i>
                    إضافة محاضرة
                </h3>
                <button onclick="closeLectureModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('teacher.lectures.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- عنوان المحاضرة --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">عنوان المحاضرة</label>
                    <input type="text" name="title" class="form-input" placeholder="أدخل عنوان المحاضرة هنا" required>
                </div>

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

                {{-- وصف المحاضرة --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">وصف المحاضرة (اختياري)</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="ملاحظات أو وصف مختصر..." style="resize: vertical;"></textarea>
                </div>

                {{-- رفع ملف --}}
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">
                        <i class="fa-solid fa-paperclip" style="color: var(--accent-color);"></i>
                        ملف مرفق (اختياري)
                    </label>

                    <div class="upload-area" id="upload-area"
                         ondragover="event.preventDefault(); this.classList.add('drag-over')"
                         ondragleave="this.classList.remove('drag-over')"
                         ondrop="this.classList.remove('drag-over')">
                        <input type="file" name="attachment" id="file-input"
                               accept="image/*,video/*,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
                               onchange="previewFile(this)">
                        <div id="upload-placeholder">
                            <div class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                            <div class="upload-text">اسحب وأفلت الملف هنا، أو اضغط للاختيار</div>
                            <div class="upload-hint">صور · فيديو · PDF · Word · PowerPoint · Excel · ZIP — حتى 50 ميجابايت</div>
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
                    <button type="submit" style="flex: 1; padding: 0.85rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-family: inherit; font-size: 1rem;">
                        <i class="fa-solid fa-plus"></i> إضافة المحاضرة
                    </button>
                    <button type="button" onclick="closeLectureModal()" style="flex: 1; padding: 0.85rem; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 1rem;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
/* ===== Edit Modal ===== */
function openEditModal(id, title, courseId, description) {
    document.getElementById('edit-title').value = title;
    document.getElementById('edit-description').value = description;
    const select = document.getElementById('edit-course_id');
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value == courseId) {
            select.selectedIndex = i;
            break;
        }
    }
    document.getElementById('edit-lecture-form').action = '/teacher/lectures/update/' + id;
    document.getElementById('edit-lecture-modal').classList.add('active');
}
document.getElementById('edit-lecture-modal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});

/* ===== Modal ===== */
function closeLectureModal() {
    document.getElementById('add-lecture-modal').classList.remove('active');
    removeFile();
}
document.getElementById('add-lecture-modal').addEventListener('click', function(e) {
    if (e.target === this) closeLectureModal();
});

/* ===== File Upload ===== */
const iconMap = {
    image:    { icon: '🖼️', label: 'صورة' },
    video:    { icon: '🎬', label: 'فيديو' },
    pdf:      { icon: '📄', label: 'PDF' },
    word:     { icon: '📝', label: 'Word' },
    ppt:      { icon: '📊', label: 'PowerPoint' },
    excel:    { icon: '📊', label: 'Excel' },
    zip:      { icon: '📦', label: 'ZIP' },
    default:  { icon: '📎', label: 'ملف' },
};

function getFileIcon(mime, name) {
    if (mime.startsWith('image/'))  return iconMap.image;
    if (mime.startsWith('video/'))  return iconMap.video;
    if (mime === 'application/pdf') return iconMap.pdf;
    if (mime.includes('word') || name.endsWith('.doc') || name.endsWith('.docx')) return iconMap.word;
    if (mime.includes('presentation') || name.endsWith('.ppt') || name.endsWith('.pptx')) return iconMap.ppt;
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
