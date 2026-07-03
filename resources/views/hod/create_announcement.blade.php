@extends('layouts.hod')

@section('title', 'إنشاء إعلان جديد')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; background: #f8fafc; }
    .card { background: #fff; border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 2rem; }
    .form-control { border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.75rem; width: 100%; margin-bottom: 1rem; }
    .btn-primary { background: hsl(220, 90%, 56%); border: none; color: #fff; padding: 0.75rem 1.5rem; border-radius: 0.5rem; transition: background 0.2s; cursor: pointer; }
    .btn-primary:hover { background: hsl(220, 90%, 48%); }
    .alert-success { background: hsl(120, 70%, 95%); color: hsl(120, 50%, 30%); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; }
    .form-group { margin-bottom: 1rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151; }
    .text-red-600 { color: #dc2626; font-size: 0.875rem; }
</style>
@endpush

@section('content')
<div class="container mx-auto py-8" dir="rtl">
    <div class="max-w-2xl mx-auto card" style="max-width: 800px; margin: 2rem auto;">
        <h1 class="text-2xl font-semibold mb-6 text-gray-800" style="margin-bottom: 1.5rem;">إنشاء إعلان جديد</h1>

        @if (session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('hod.announcements.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label">العنوان *</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                @error('title') <p class="text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">المحتوى *</label>
                <textarea name="content" rows="4" class="form-control" required>{{ old('content') }}</textarea>
                @error('content') <p class="text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">الجمهور المستهدف</label>
                <select name="target_audience" class="form-control">
                    <option value="all"      {{ old('target_audience') == 'all'      ? 'selected' : '' }}>الجميع (طلاب + معلمون)</option>
                    <option value="students" {{ old('target_audience') == 'students' ? 'selected' : '' }}>الطلاب فقط</option>
                    <option value="teachers" {{ old('target_audience') == 'teachers' ? 'selected' : '' }}>المعلمون فقط</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">النوع</label>
                <select name="type" id="typeSelect" class="form-control" required>
                    <option value="general"         {{ old('type') == 'general'         ? 'selected' : '' }}>إعلان عام</option>
                    <option value="course_specific" {{ old('type') == 'course_specific' ? 'selected' : '' }}>إعلان خاص بمادة</option>
                </select>
            </div>

            <div class="form-group" id="courseDiv" style="display:none;">
                <label class="form-label">اختر المادة</label>
                <select name="course_id" class="form-control">
                    <option value="">-- اختر --</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->course_id }}" {{ old('course_id') == $course->course_id ? 'selected' : '' }}>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">صورة مرفقة <span style="font-weight:400; color:#9ca3af;">(اختياري)</span></label>
                <div id="hod-upload-zone"
                     onclick="document.getElementById('hod-img-input').click()"
                     style="border:2px dashed #d1d5db; border-radius:0.75rem; padding:1.5rem 1rem; text-align:center; cursor:pointer; background:#f9fafb; transition:border-color .2s, background .2s;"
                     onmouseover="this.style.borderColor='#6366f1'; this.style.background='#f0f0ff';"
                     onmouseout="this.style.borderColor='#d1d5db'; this.style.background='#f9fafb';"
                     ondragover="event.preventDefault(); this.style.borderColor='#6366f1'; this.style.background='#f0f0ff';"
                     ondragleave="this.style.borderColor='#d1d5db'; this.style.background='#f9fafb';">
                    <input type="file" name="image" id="hod-img-input" accept="image/*"
                           data-crop="true" data-preview-img="hod-prev-img" data-preview-wrap="hod-prev-wrap" data-placeholder="hod-prev-placeholder"
                           style="display:none;">
                    <div id="hod-prev-placeholder">
                        <div style="font-size:2rem; margin-bottom:0.4rem;">🖼️</div>
                        <p style="font-weight:600; color:#374151; font-size:0.9rem; margin:0 0 0.25rem;">اسحب الصورة هنا أو اضغط للاختيار</p>
                        <p style="font-size:0.78rem; color:#9ca3af; margin:0;">JPG / PNG / WebP — حتى 5MB</p>
                    </div>
                    <div id="hod-prev-wrap" style="display:none;">
                        <img id="hod-prev-img" src="" alt="" style="max-height:150px; border-radius:0.5rem; object-fit:cover; margin:0 auto; display:block;">
                        <p style="margin-top:0.5rem; font-size:0.78rem; color:#6b7280;">اضغط مجدداً لتغيير الصورة</p>
                    </div>
                </div>
                @error('image') <p class="text-red-600 mt-1" style="font-size:0.875rem;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">رابط خارجي (اختياري)</label>
                <input type="url" name="link_url" class="form-control"
                       placeholder="https://..." value="{{ old('link_url') }}">
                @error('link_url') <p class="text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div style="display:flex; gap:1rem; align-items:center; margin-top:1.5rem;">
                <button type="submit" class="btn-primary">نشر الإعلان</button>
                <a href="{{ url('/hod/dashboard') }}" style="color:#6b7280; text-decoration:none;">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<script>
    const typeSelect = document.getElementById('typeSelect');
    const courseDiv = document.getElementById('courseDiv');
    function toggleCourseDiv() {
        courseDiv.style.display = typeSelect.value === 'course_specific' ? 'block' : 'none';
    }
    typeSelect.addEventListener('change', toggleCourseDiv);
    document.addEventListener('DOMContentLoaded', toggleCourseDiv);
</script>

@include('partials.image_cropper')
@endsection
