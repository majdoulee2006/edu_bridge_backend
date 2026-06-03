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

    @if(session('success'))
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/30 text-emerald-700 dark:text-emerald-400 mb-4">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="text-sm font-semibold">{{ session('success') }}</span>
        </div>
    @endif

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
                {{-- رفع صورة --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">صورة مرفقة <span class="normal-case">(اختياري)</span></label>
                    <div id="upload-zone"
                         class="flex-1 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-5 text-center cursor-pointer hover:border-primary/50 transition-all bg-slate-50 dark:bg-slate-900/30 min-h-[130px] flex flex-col items-center justify-center"
                         onclick="document.getElementById('imgInput').click()"
                         ondragover="event.preventDefault(); this.classList.add('border-primary')"
                         ondragleave="this.classList.remove('border-primary')">
                        <input type="file" name="image" id="imgInput" accept="image/*" class="hidden" onchange="previewImage(this)">
                        <div id="upload-placeholder" class="flex flex-col items-center">
                            <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600 mb-2">add_photo_alternate</span>
                            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">اسحب أو اضغط للاختيار</p>
                            <p class="text-xs text-slate-400 mt-1">JPG / PNG / WebP — حتى 5MB</p>
                        </div>
                        <div id="img-preview" class="hidden w-full">
                            <img id="preview-img" src="" alt="" class="max-h-36 mx-auto rounded-xl object-cover shadow-soft">
                            <p id="preview-name" class="text-xs text-slate-400 mt-2 truncate"></p>
                        </div>
                    </div>
                    @error('image')<p class="text-xs text-red-500 px-1 mt-1">{{ $message }}</p>@enderror
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
                        class="flex items-center gap-2 px-6 py-3 rounded-2xl font-bold text-sm shadow-glow hover:scale-105 active:scale-95 transition-all"
                        style="background:#f2f20d;color:#101924;">
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

function previewImage(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('preview-img').src = e.target.result;
        document.getElementById('preview-name').textContent = file.name;
        document.getElementById('upload-placeholder').classList.add('hidden');
        document.getElementById('img-preview').classList.remove('hidden');
    };
    reader.readAsDataURL(file);
}
</script>
@endpush
