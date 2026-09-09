@extends('layouts.admin')
@section('title', 'إنشاء إعلان جديد')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}"
               class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft">
                <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">إنشاء إعلان جديد</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">نشر إعلان لجميع المستخدمين أو قسم محدد</span>
            </div>
        </div>
    </div>

    {{-- ===== Form Card ===== --}}
    <div class="w-full">
        <form method="POST" action="{{ route('admin.announcements.store') }}" enctype="multipart/form-data"
              class="bg-white dark:bg-surface-dark rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft p-8 flex flex-col gap-6">
            @csrf

            {{-- صف 1: العنوان + الجمهور --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">العنوان *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           placeholder="عنوان الإعلان..."
                           class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/20 py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white outline-none transition-all">
                    @error('title')<p class="text-xs text-red-500 px-1 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">الجمهور المستهدف</label>
                    <select name="target_audience" id="targetAudience" onchange="toggleDept()"
                            class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white outline-none appearance-none transition-all">
                        <option value="all" {{ old('target_audience','all')=='all' ? 'selected' : '' }}>الجميع — كل المعهد</option>
                        <option value="department" {{ old('target_audience')=='department' ? 'selected' : '' }}>قسم معين</option>
                    </select>
                </div>
            </div>

            {{-- القسم (يظهر عند اختيار "قسم معين") --}}
            <div id="deptDiv" class="hidden flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">القسم</label>
                <select name="department_id"
                        class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white outline-none appearance-none transition-all">
                    <option value="">-- اختر القسم --</option>
                    @foreach(\App\Models\Department::all() as $dept)
                        <option value="{{ $dept->department_id }}" {{ old('department_id')==$dept->department_id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- المحتوى --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">المحتوى *</label>
                <textarea name="content" rows="6" required
                          placeholder="اكتب نص الإعلان هنا..."
                          class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/20 py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white outline-none transition-all resize-none">{{ old('content') }}</textarea>
                @error('content')<p class="text-xs text-red-500 px-1 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- صف 3: صورة + رابط --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- رفع صورة / صور --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">صور مرفقة <span class="normal-case">(اختياري)</span></label>
                    
                    {{-- Hidden file input for form submit --}}
                    <input type="file" name="images[]" id="finalImagesInput" multiple class="hidden">

                    <div id="upload-zone"
                         class="flex-1 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-5 text-center bg-slate-50 dark:bg-slate-900/30 min-h-[140px] flex flex-col items-center justify-center relative transition-all"
                         ondragover="event.preventDefault(); this.classList.add('border-primary')"
                         ondragleave="this.classList.remove('border-primary')"
                         ondrop="handleAdminDrop(event)">
                        
                        <input type="file" id="imgSelectorInput" accept="image/*" multiple class="hidden"
                               onchange="handleAdminFilesSelected(this.files)">
                        
                        <div id="upload-placeholder" class="flex flex-col items-center cursor-pointer" onclick="document.getElementById('imgSelectorInput').click()">
                            <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600 mb-2">add_photo_alternate</span>
                            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">اسحب الصور هنا أو اضغط لاختيار صورة/عدة صور</p>
                            <p class="text-xs text-slate-400 mt-1">يمكنك قص وتعديل أو حذف أي صورة بعد اختيارها</p>
                        </div>

                        <div id="img-preview-container" class="hidden w-full flex flex-col items-center gap-3">
                            <div id="preview-grid" class="flex flex-wrap gap-3 justify-center w-full max-h-56 overflow-y-auto p-2"></div>
                            
                            <div class="flex items-center gap-3">
                                <span id="preview-count" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-3 py-1 rounded-full border border-emerald-200 dark:border-emerald-800"></span>
                                <button type="button" onclick="document.getElementById('imgSelectorInput').click()"
                                        class="flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                    <span class="material-symbols-outlined text-sm">add_circle</span> إضافة المزيد
                                </button>
                            </div>
                        </div>
                    </div>
                    @error('images')<p class="text-xs text-red-500 px-1 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- رابط خارجي --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">رابط خارجي <span class="normal-case">(اختياري)</span></label>
                    <input type="url" name="link_url" value="{{ old('link_url') }}" placeholder="https://..."
                           class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/20 py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white outline-none transition-all" dir="ltr">
                    @error('link_url')<p class="text-xs text-red-500 px-1 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- أزرار --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="flex items-center gap-2 px-6 py-3 rounded-2xl font-bold text-sm bg-primary text-primary-content shadow-glow hover:scale-105 active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                    نشر الإعلان
                </button>
                <a href="{{ route('admin.dashboard') }}"
                   class="px-5 py-3 rounded-2xl text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                    إلغاء
                </a>
            </div>

        </form>
    </div>

@endsection

@push('scripts')
<script>
function toggleDept() {
    const val = document.getElementById('targetAudience').value;
    document.getElementById('deptDiv').classList.toggle('hidden', val !== 'department');
}
document.addEventListener('DOMContentLoaded', toggleDept);

let adminImagesStore = [];

function handleAdminFilesSelected(files) {
    if (!files || files.length === 0) return;
    Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        adminImagesStore.push({
            file: file,
            url: URL.createObjectURL(file)
        });
    });
    syncAdminImagesUI();
    document.getElementById('imgSelectorInput').value = '';
}

function handleAdminDrop(e) {
    e.preventDefault();
    document.getElementById('upload-zone').classList.remove('border-primary');
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        handleAdminFilesSelected(e.dataTransfer.files);
    }
}

function syncAdminImagesUI() {
    const container = document.getElementById('img-preview-container');
    const placeholder = document.getElementById('upload-placeholder');
    const grid = document.getElementById('preview-grid');
    const countSpan = document.getElementById('preview-count');

    grid.innerHTML = '';
    if (adminImagesStore.length > 0) {
        placeholder.classList.add('hidden');
        container.classList.remove('hidden');
        countSpan.textContent = 'تم اختيار ' + adminImagesStore.length + ' صور';

        adminImagesStore.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'relative w-24 h-24 rounded-2xl overflow-hidden border-2 border-slate-200 dark:border-slate-700 group shadow-soft flex-shrink-0 bg-slate-800';
            card.innerHTML = `
                <img src="${item.url}" class="w-full h-full object-cover"/>
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 p-1">
                    <button type="button" onclick="cropAdminImageIndex(${index})" title="قص وتعديل الصورة"
                            class="w-8 h-8 rounded-xl bg-yellow-400 text-black flex items-center justify-center hover:scale-110 transition-transform shadow">
                        <span class="material-symbols-outlined text-[18px] font-bold">crop</span>
                    </button>
                    <button type="button" onclick="removeAdminImageIndex(${index})" title="حذف"
                            class="w-8 h-8 rounded-xl bg-red-600 text-white flex items-center justify-center hover:scale-110 transition-transform shadow">
                        <span class="material-symbols-outlined text-[18px] font-bold">delete</span>
                    </button>
                </div>
            `;
            grid.appendChild(card);
        });
    } else {
        placeholder.classList.remove('hidden');
        container.classList.add('hidden');
    }

    const dt = new DataTransfer();
    adminImagesStore.forEach(item => dt.items.add(item.file));
    document.getElementById('finalImagesInput').files = dt.files;
}

function cropAdminImageIndex(index) {
    if (!adminImagesStore[index]) return;
    window.triggerCropper(adminImagesStore[index].file, function(croppedFile) {
        adminImagesStore[index] = {
            file: croppedFile,
            url: URL.createObjectURL(croppedFile)
        };
        syncAdminImagesUI();
    });
}

function removeAdminImageIndex(index) {
    adminImagesStore.splice(index, 1);
    syncAdminImagesUI();
}
</script>
@include('partials.image_cropper')
@endpush
