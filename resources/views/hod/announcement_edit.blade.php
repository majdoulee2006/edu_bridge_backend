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
            <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-secondary);">الصورة (اترك فارغاً للإبقاء)</label>
            @if($announcement->image)
                <img src="{{ asset('storage/' . $announcement->image) }}" style="height: 80px; border-radius: 0.5rem; object-fit: fill; margin-bottom: 0.5rem; display: block;">
            @endif
            <input type="file" name="image" accept="image/*"
                   data-crop="true" data-simple-preview="hod-edit-img-preview"
                   style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); cursor: pointer;">
            <img id="hod-edit-img-preview" src="" alt="" style="display:none;">
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
