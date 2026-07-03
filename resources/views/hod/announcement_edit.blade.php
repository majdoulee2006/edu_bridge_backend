@extends('layouts.hod')
@section('title', 'تعديل الإعلان')

@section('content')
<div style="max-width: 700px; margin: 0 auto;">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
        <a href="{{ route('hod.dashboard') }}"
           style="width: 40px; height: 40px; border-radius: 0.75rem; background: var(--bg-secondary); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-secondary); text-decoration: none;">
            <i class="fa-solid fa-arrow-right"></i>
        </a>
        <h2 style="font-weight: 800; font-size: 1.2rem;">تعديل الإعلان</h2>
    </div>

    <form action="{{ route('hod.announcements.update', $announcement->announcement_id) }}" method="POST" enctype="multipart/form-data"
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
            <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:0.5rem; color:var(--text-secondary);">
                الصورة <span style="font-weight:400;">(اترك فارغاً للإبقاء على الحالية)</span>
            </label>
            <div onclick="document.getElementById('hod-edit-img-input').click()"
                 style="border:2px dashed var(--border-color); border-radius:0.75rem; padding:1.25rem 1rem; text-align:center; cursor:pointer; background:var(--bg-primary); transition:border-color .2s;"
                 onmouseover="this.style.borderColor='var(--accent-color)'"
                 onmouseout="this.style.borderColor='var(--border-color)'">
                <input type="file" name="image" id="hod-edit-img-input" accept="image/*"
                       data-crop="true" data-preview-img="hod-edit-prev-img" data-preview-wrap="hod-edit-prev-wrap" data-placeholder="hod-edit-placeholder"
                       style="display:none;">
                <div id="hod-edit-placeholder">
                    @if($announcement->image)
                        <img src="{{ asset('storage/' . $announcement->image) }}"
                             style="max-height:130px; border-radius:0.5rem; object-fit:cover; margin:0 auto; display:block; margin-bottom:0.5rem;">
                        <p style="font-size:0.78rem; color:var(--text-secondary); margin:0;">اضغط لتغيير الصورة</p>
                    @else
                        <div style="font-size:1.75rem; margin-bottom:0.35rem;">🖼️</div>
                        <p style="font-weight:600; color:var(--text-secondary); font-size:0.88rem; margin:0 0 0.2rem;">اضغط لإضافة صورة</p>
                        <p style="font-size:0.75rem; color:var(--text-secondary); opacity:.7; margin:0;">JPG / PNG / WebP</p>
                    @endif
                </div>
                <div id="hod-edit-prev-wrap" style="display:none;">
                    <img id="hod-edit-prev-img" src="" alt="" style="max-height:140px; border-radius:0.5rem; object-fit:cover; margin:0 auto; display:block;">
                    <p style="margin-top:0.4rem; font-size:0.75rem; color:var(--text-secondary);">اضغط مجدداً لتغيير الصورة</p>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
            <button type="submit"
                    style="flex: 1; padding: 0.85rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-family: inherit; font-size: 1rem;">
                <i class="fa-solid fa-save"></i> حفظ التعديلات
            </button>
            <a href="{{ route('hod.dashboard') }}"
               style="flex: 1; padding: 0.85rem; background: transparent; border: 1px solid var(--border-color); border-radius: 0.75rem; font-weight: 700; text-align: center; color: var(--text-primary); text-decoration: none;">
                إلغاء
            </a>
        </div>
    </form>
</div>

@include('partials.image_cropper')
@endsection
