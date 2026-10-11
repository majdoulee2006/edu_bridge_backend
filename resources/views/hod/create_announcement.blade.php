@extends('layouts.hod')

@section('title', __('messages.create_announcement_title'))

@push('styles')
<style>
    body { font-family: 'Cairo', sans-serif; background: var(--bg-primary); color: var(--text-primary); }
    .card { background: var(--bg-secondary); border-radius: 0.75rem; box-shadow: var(--shadow); padding: 2rem; border: 1px solid var(--border-color); }
    .form-control { border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 0.75rem; width: 100%; margin-bottom: 1rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; }
    .form-control:focus { outline: none; border-color: var(--accent-color); }
    .btn-primary { background: var(--accent-color); border: none; color: #1a1a1a; padding: 0.75rem 1.5rem; border-radius: 0.5rem; transition: opacity .2s; cursor: pointer; font-weight: 700; font-family: inherit; }
    .btn-primary:hover { opacity: .85; }
    .alert-success { background: hsl(120, 70%, 95%); color: hsl(120, 50%, 30%); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; }
    .form-group { margin-bottom: 1rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary); }
    .text-red-600 { color: #dc2626; font-size: 0.875rem; }
</style>
@endpush

@section('content')
<div class="container mx-auto py-8">
    <div class="max-w-2xl mx-auto card" style="max-width: 800px; margin: 2rem auto;">
        <h1 class="text-2xl font-semibold mb-6 text-gray-800" style="margin-bottom: 1.5rem;">{{ __('messages.create_announcement_title') }}</h1>

        <form method="POST" action="{{ route('hod.announcements.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label">{{ __('messages.announcement_title_label') }} *</label>
                <input type="text" name="title" class="form-control" placeholder="{{ __('messages.announcement_title_ph') }}" value="{{ old('title') }}" required>
                @error('title') <p class="text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('messages.announcement_content_label') }} *</label>
                <textarea name="content" rows="4" class="form-control" placeholder="{{ __('messages.announcement_content_ph') }}" required>{{ old('content') }}</textarea>
                @error('content') <p class="text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('messages.target_audience_label') }}</label>
                <select name="target_audience" class="form-control">
                    <option value="all"      {{ old('target_audience') == 'all'      ? 'selected' : '' }}>{{ __('messages.audience_all_desc') }}</option>
                    <option value="students" {{ old('target_audience') == 'students' ? 'selected' : '' }}>{{ __('messages.audience_students_only') }}</option>
                    <option value="teachers" {{ old('target_audience') == 'teachers' ? 'selected' : '' }}>{{ __('messages.audience_teachers_only') }}</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('messages.announcement_type_label') }}</label>
                <select name="type" id="typeSelect" class="form-control" required>
                    <option value="general"         {{ old('type') == 'general'         ? 'selected' : '' }}>{{ __('messages.type_general') }}</option>
                    <option value="course_specific" {{ old('type') == 'course_specific' ? 'selected' : '' }}>{{ __('messages.type_course_specific') }}</option>
                </select>
            </div>

            <div class="form-group" id="courseDiv" style="display:none;">
                <label class="form-label">{{ __('messages.select_course_label') }}</label>
                <select name="course_id" class="form-control">
                    <option value="">{{ __('messages.select_placeholder') }}</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->course_id }}" {{ old('course_id') == $course->course_id ? 'selected' : '' }}>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('messages.attached_images_optional') }}</label>
                <div id="hod-upload-zone"
                     onclick="document.getElementById('hod-img-input').click()"
                     style="border:2px dashed var(--border-color); border-radius:0.75rem; padding:1.5rem 1rem; text-align:center; cursor:pointer; background:var(--bg-primary); transition:border-color .2s;"
                     onmouseover="this.style.borderColor='var(--accent-color)'"
                     onmouseout="this.style.borderColor='var(--border-color)'"
                     ondragover="event.preventDefault(); this.style.borderColor='var(--accent-color)'"
                     ondragleave="this.style.borderColor='var(--border-color)'">
                    <input type="file" name="images[]" id="hod-img-input" accept="image/*" multiple
                           style="display:none;" onchange="previewMultipleImages(this, 'hod-prev-wrap', 'hod-prev-placeholder')">
                    <div id="hod-prev-placeholder">
                        <div style="font-size:2rem; margin-bottom:0.4rem;">🖼️</div>
                        <p style="font-weight:600; color:var(--text-primary); font-size:0.9rem; margin:0 0 0.25rem;">{{ __('messages.drag_images_hint') }}</p>
                        <p style="font-size:0.78rem; color:var(--text-secondary); margin:0;">{{ __('messages.image_format_limit_hint') }}</p>
                    </div>
                    <div id="hod-prev-wrap" style="display:none; flex-wrap:wrap; gap:0.5rem; justify-content:center;">
                    </div>
                </div>
                @error('images') <p class="text-red-600 mt-1" style="font-size:0.875rem;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('messages.external_link_optional') }}</label>
                <input type="url" name="link_url" class="form-control"
                       placeholder="https://..." value="{{ old('link_url') }}">
                @error('link_url') <p class="text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div style="display:flex; gap:1rem; align-items:center; margin-top:1.5rem;">
                <button type="submit" class="btn-primary">{{ __('messages.publish_announcement_btn') }}</button>
                <a href="{{ route('hod.dashboard') }}" style="color:var(--text-secondary); text-decoration:none;">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</div>

<script>
    const typeSelect = document.getElementById('typeSelect');
    const courseDiv = document.getElementById('courseDiv');
    function toggleCourseDiv() {
        if(courseDiv && typeSelect) {
            courseDiv.style.display = typeSelect.value === 'course_specific' ? 'block' : 'none';
        }
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', toggleCourseDiv);
    }
    document.addEventListener('DOMContentLoaded', toggleCourseDiv);

    function previewMultipleImages(input, wrapId, placeholderId) {
        const wrap = document.getElementById(wrapId);
        const placeholder = document.getElementById(placeholderId);
        wrap.innerHTML = '';
        if (input.files && input.files.length > 0) {
            placeholder.style.display = 'none';
            wrap.style.display = 'flex';
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '100px';
                    img.style.height = '100px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '0.5rem';
                    img.style.border = '1px solid var(--border-color)';
                    wrap.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        } else {
            placeholder.style.display = 'block';
            wrap.style.display = 'none';
        }
    }
</script>

@include('partials.image_cropper')
@endsection
