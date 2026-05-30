@extends('layouts.admin')
@section('title', 'إنشاء إعلان جديد')

@section('content')
<div style="max-width:700px; margin:2rem auto; padding:0 1rem;" dir="rtl">

    <div style="background:var(--bg-secondary,#fff); border-radius:1rem; padding:2rem; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

        <h2 style="font-size:1.4rem; font-weight:800; margin-bottom:1.5rem;">
            <i class="fa-solid fa-bullhorn" style="color:#f2f20d;"></i>
            إنشاء إعلان جديد
        </h2>

        @if(session('success'))
            <div style="background:#d1fae5; color:#065f46; border-radius:0.5rem; padding:1rem; margin-bottom:1rem; font-weight:600;">
                <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.announcements.store') }}" enctype="multipart/form-data">
            @csrf

            <div style="margin-bottom:1.2rem;">
                <label style="display:block; font-weight:700; margin-bottom:0.4rem;">العنوان *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       style="width:100%; padding:0.75rem 1rem; border:1.5px solid #e5e7eb; border-radius:0.6rem; font-size:0.95rem; font-family:inherit;">
                @error('title') <p style="color:#dc2626; font-size:0.85rem; margin-top:0.3rem;">{{ $message }}</p> @enderror
            </div>

            <div style="margin-bottom:1.2rem;">
                <label style="display:block; font-weight:700; margin-bottom:0.4rem;">المحتوى *</label>
                <textarea name="content" rows="5" required
                          style="width:100%; padding:0.75rem 1rem; border:1.5px solid #e5e7eb; border-radius:0.6rem; font-size:0.95rem; font-family:inherit; resize:vertical;">{{ old('content') }}</textarea>
                @error('content') <p style="color:#dc2626; font-size:0.85rem; margin-top:0.3rem;">{{ $message }}</p> @enderror
            </div>

            <div style="margin-bottom:1.2rem;">
                <label style="display:block; font-weight:700; margin-bottom:0.4rem;">الجمهور المستهدف</label>
                <select name="target_audience" style="width:100%; padding:0.75rem 1rem; border:1.5px solid #e5e7eb; border-radius:0.6rem; font-size:0.95rem;">
                    <option value="all"      {{ old('target_audience')=='all'      ? 'selected' : '' }}>الجميع (طلاب + معلمون)</option>
                    <option value="students" {{ old('target_audience')=='students' ? 'selected' : '' }}>الطلاب فقط</option>
                    <option value="teachers" {{ old('target_audience')=='teachers' ? 'selected' : '' }}>المعلمون فقط</option>
                </select>
            </div>

            <div style="margin-bottom:1.2rem;">
                <label style="display:block; font-weight:700; margin-bottom:0.4rem;">صورة مرفقة (اختياري)</label>
                <input type="file" name="image" accept="image/*"
                       style="width:100%; padding:0.5rem; border:1.5px solid #e5e7eb; border-radius:0.6rem; cursor:pointer;">
                <small style="color:#6b7280;">JPG / PNG / WebP — حتى 5MB</small>
                @error('image') <p style="color:#dc2626; font-size:0.85rem; margin-top:0.3rem;">{{ $message }}</p> @enderror
            </div>

            <div style="margin-bottom:1.5rem;">
                <label style="display:block; font-weight:700; margin-bottom:0.4rem;">رابط خارجي (اختياري)</label>
                <input type="url" name="link_url" value="{{ old('link_url') }}" placeholder="https://..."
                       style="width:100%; padding:0.75rem 1rem; border:1.5px solid #e5e7eb; border-radius:0.6rem; font-size:0.95rem;">
                @error('link_url') <p style="color:#dc2626; font-size:0.85rem; margin-top:0.3rem;">{{ $message }}</p> @enderror
            </div>

            <div style="display:flex; gap:1rem; align-items:center;">
                <button type="submit"
                        style="background:#f2f20d; color:#1a1a1a; border:none; padding:0.8rem 2rem; border-radius:0.6rem; font-weight:800; font-size:1rem; cursor:pointer;">
                    <i class="fa-solid fa-paper-plane"></i> نشر الإعلان
                </button>
                <a href="{{ route('admin.dashboard') }}" style="color:#6b7280; text-decoration:none;">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
