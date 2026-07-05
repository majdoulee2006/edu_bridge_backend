@extends('layouts.admin')
@section('title', 'تعديل الإعلان')

@section('content')

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}"
               class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft">
                <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">تعديل الإعلان</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">تعديل بيانات الإعلان المنشور</span>
            </div>
        </div>
    </div>

    <div class="w-full">
        <form method="POST" action="{{ route('admin.announcements.update', $announcement->announcement_id) }}" enctype="multipart/form-data"
              class="bg-white dark:bg-surface-dark rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft p-8 flex flex-col gap-6">
            @csrf

            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">العنوان *</label>
                <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required
                       class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/20 py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white outline-none transition-all">
                @error('title')<p class="text-xs text-red-500 px-1 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">المحتوى *</label>
                <textarea name="content" rows="6" required
                          class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/20 py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white outline-none transition-all resize-none">{{ old('content', $announcement->content) }}</textarea>
                @error('content')<p class="text-xs text-red-500 px-1 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- الصورة الحالية + رفع جديدة --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">الصورة <span class="normal-case">(اترك فارغاً للإبقاء على الحالية)</span></label>
                @if($announcement->image)
                    <div class="mb-2 flex items-center gap-3">
                        <img src="{{ Storage::url($announcement->image) }}" class="h-20 rounded-xl object-cover shadow-soft" alt="">
                        <span class="text-xs text-slate-400">الصورة الحالية</span>
                    </div>
                @endif
                <div id="upload-zone"
                     class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-5 text-center cursor-pointer hover:border-primary/50 transition-all bg-slate-50 dark:bg-slate-900/30 min-h-[100px] flex flex-col items-center justify-center"
                     onclick="document.getElementById('imgInput').click()">
                    <input type="file" name="image" id="imgInput" accept="image/*" class="hidden"
                           data-crop="true"
                           data-preview-img="preview-img"
                           data-preview-wrap="img-preview"
                           data-placeholder="upload-placeholder"
                           data-preview-name="preview-name">
                    <div id="upload-placeholder" class="flex flex-col items-center">
                        <span class="material-symbols-outlined text-3xl text-slate-300 dark:text-slate-600 mb-1">add_photo_alternate</span>
                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">اضغط لاستبدال الصورة</p>
                    </div>
                    <div id="img-preview" class="hidden w-full">
                        <img id="preview-img" src="" alt="" class="max-h-32 mx-auto rounded-xl object-cover">
                        <p id="preview-name" class="text-xs text-slate-400 mt-1 truncate"></p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="flex items-center gap-2 px-6 py-3 rounded-2xl font-bold text-sm shadow-glow hover:scale-105 active:scale-95 transition-all"
                        style="background:#f2f20d;color:#101924;">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    حفظ التعديلات
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
function previewImage(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('preview-img').src = e.target.result;
        document.getElementById('preview-name').textContent = input.files[0].name;
        document.getElementById('upload-placeholder').classList.add('hidden');
        document.getElementById('img-preview').classList.remove('hidden');
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
@include('partials.image_cropper')
@endpush
