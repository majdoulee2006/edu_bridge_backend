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

        <form method="POST" action="{{ route('hod.announcements.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">العنوان</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                @error('title')
                    <p class="text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">المحتوى</label>
                <textarea name="content" rows="4" class="form-control" required>{{ old('content') }}</textarea>
                @error('content')
                    <p class="text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">النوع</label>
                <select name="type" id="typeSelect" class="form-control" required>
                    <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>إعلان عام</option>
                    <option value="course_specific" {{ old('type') == 'course_specific' ? 'selected' : '' }}>إعلان خاص بدورة</option>
                </select>
                @error('type')
                    <p class="text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group" id="courseDiv" style="display: none;">
                <label class="form-label">اختر الدورة</label>
                <select name="course_id" class="form-control">
                    <option value="">-- لا اختيار --</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->course_id }}" {{ old('course_id') == $course->course_id ? 'selected' : '' }}>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
                @error('course_id')
                    <p class="text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary">حفظ الإعلان</button>
            <a href="{{ url('/hod/dashboard') }}" style="margin-right: 1rem; color: #6b7280; text-decoration: none;">إلغاء</a>
        </form>
    </div>
</div>

<script>
    // إظهار/إخفاء اختيار الدورة حسب نوع الإعلان
    const typeSelect = document.getElementById('typeSelect');
    const courseDiv = document.getElementById('courseDiv');

    function toggleCourseDiv() {
        courseDiv.style.display = typeSelect.value === 'course_specific' ? 'block' : 'none';
    }

    typeSelect.addEventListener('change', toggleCourseDiv);
    document.addEventListener('DOMContentLoaded', toggleCourseDiv);
</script>
@endsection
