@extends('layouts.teacher')
@section('title', 'تعديل الإعلان')

@section('content')
<div style="max-width: 700px; margin: 0 auto;">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
        <a href="{{ route('teacher.dashboard') }}"
           style="width: 40px; height: 40px; border-radius: 0.75rem; background: var(--bg-secondary); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-secondary); text-decoration: none;">
            <i class="fa-solid fa-arrow-right"></i>
        </a>
        <h2 style="font-weight: 800; font-size: 1.2rem;">تعديل الإعلان</h2>
    </div>

    <form action="{{ route('teacher.announcements.update', $announcement->announcement_id) }}" method="POST" enctype="multipart/form-data"
          style="background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; box-shadow: var(--shadow); display: flex; flex-direction: column; gap: 1.25rem;">
        @csrf

        <div>
            <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-secondary);">العنوان *</label>
            <input type="text" name="title" required value="{{ old('title', $announcement->title) }}"
                   style="width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; outline: none; box-sizing: border-box;">
        </div>

        <div>
            <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-secondary);">المحتوى *</label>
            <textarea name="content" rows="5" required
                      style="width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; outline: none; resize: vertical; box-sizing: border-box;">{{ old('content', $announcement->content) }}</textarea>
        </div>

        <div>
            <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-secondary);">الصورة (اترك فارغاً للإبقاء على الحالية)</label>
            @if($announcement->image)
                <img src="{{ asset('storage/' . $announcement->image) }}" style="height: 80px; border-radius: 0.5rem; object-fit: fill; margin-bottom: 0.5rem;">
            @endif
            <div onclick="document.getElementById('imgInput').click()"
                 style="border: 2px dashed var(--border-color); border-radius: 0.75rem; padding: 1.25rem; text-align: center; cursor: pointer; background: var(--bg-primary);">
                <input type="file" name="image" id="imgInput" accept="image/*" style="display:none;"
                       data-crop="true" data-preview-img="preview-img" data-preview-wrap="img-preview" data-placeholder="upload-placeholder" data-preview-name="preview-name">
                <div id="upload-placeholder">
                    <i class="fa-solid fa-image" style="color: var(--text-secondary);"></i>
                    <p style="color: var(--text-secondary); font-size: 0.82rem; margin-top: 0.25rem;">اضغط لاستبدال الصورة</p>
                </div>
                <div id="img-preview" style="display:none;">
                    <img id="preview-img" src="" style="max-height: 120px; border-radius: 0.5rem;">
                    <p id="preview-name" style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.5rem;"></p>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
            <button type="submit"
                    style="flex: 1; padding: 0.85rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-family: inherit; font-size: 1rem;">
                <i class="fa-solid fa-save"></i> حفظ التعديلات
            </button>
            <a href="{{ route('teacher.dashboard') }}"
               style="flex: 1; padding: 0.85rem; background: transparent; border: 1px solid var(--border-color); border-radius: 0.75rem; font-weight: 700; text-align: center; color: var(--text-primary); text-decoration: none;">
                إلغاء
            </a>
        </div>
    </form>
</div>

@include('partials.image_cropper')
@endsection
