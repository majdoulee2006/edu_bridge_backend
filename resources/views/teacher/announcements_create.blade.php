@extends('layouts.teacher')
@section('title', 'إنشاء إعلان جديد')

@section('content')
<div style="max-width: 700px; margin: 0 auto;">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
        <a href="{{ route('teacher.dashboard') }}"
           style="width: 40px; height: 40px; border-radius: 0.75rem; background: var(--bg-secondary); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-secondary); text-decoration: none;">
            <i class="fa-solid fa-arrow-right"></i>
        </a>
        <div>
            <h2 style="font-weight: 800; font-size: 1.2rem;">إنشاء إعلان جديد</h2>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.1rem;">نشر إعلان للطلاب والمعلمين</p>
        </div>
    </div>

    <form action="{{ route('teacher.announcements.store') }}" method="POST" enctype="multipart/form-data"
          style="background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; box-shadow: var(--shadow); display: flex; flex-direction: column; gap: 1.25rem;">
        @csrf

        <div>
            <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-secondary);">العنوان *</label>
            <input type="text" name="title" required value="{{ old('title') }}" placeholder="عنوان الإعلان..."
                   style="width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; outline: none; box-sizing: border-box;">
        </div>

        <div>
            <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-secondary);">المحتوى *</label>
            <textarea name="content" rows="5" required placeholder="اكتب نص الإعلان هنا..."
                      style="width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; outline: none; resize: vertical; box-sizing: border-box;">{{ old('content') }}</textarea>
        </div>

        <div>
            <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-secondary);">صورة (اختياري)</label>
            <div id="upload-zone" onclick="document.getElementById('imgInput').click()"
                 style="border: 2px dashed var(--border-color); border-radius: 0.75rem; padding: 1.5rem; text-align: center; cursor: pointer; background: var(--bg-primary);">
                <input type="file" name="image" id="imgInput" accept="image/*" class="hidden" style="display:none;"
                       data-crop="true" data-preview-img="preview-img" data-preview-wrap="img-preview" data-placeholder="upload-placeholder" data-preview-name="preview-name">
                <div id="upload-placeholder">
                    <i class="fa-solid fa-image" style="font-size: 2rem; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;"></i>
                    <p style="color: var(--text-secondary); font-size: 0.85rem;">اسحب أو اضغط لاختيار صورة</p>
                </div>
                <div id="img-preview" style="display: none;">
                    <img id="preview-img" src="" style="max-height: 150px; border-radius: 0.5rem; object-fit: fill;">
                    <p id="preview-name" style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.5rem;"></p>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
            <button type="submit"
                    style="flex: 1; padding: 0.85rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-family: inherit; font-size: 1rem;">
                <i class="fa-solid fa-paper-plane"></i> نشر الإعلان
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
