@extends('layouts.parent')
@section('title', 'أبنائي')
@section('subtitle', 'إدارة الأبناء المرتبطين بحسابك وربط أبناء جدد')

@push('styles')
<style>
    .children-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }
    
    .child-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.75rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 1.25rem;
        position: relative;
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    
    .child-card:hover {
        transform: translateY(-3px);
        border-color: var(--accent-color);
    }
    
    .child-avatar {
        width: 65px;
        height: 65px;
        border-radius: 50%;
        background: var(--bg-primary);
        color: var(--accent-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 800;
        border: 2px solid var(--border-color);
        flex-shrink: 0;
    }
    
    .child-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .child-details {
        flex-grow: 1;
    }
    
    .child-name {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }
    
    .child-meta {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }
    
    .child-code {
        font-size: 0.8rem;
        background: var(--bg-primary);
        padding: 0.2rem 0.6rem;
        border-radius: 0.5rem;
        border: 1px solid var(--border-color);
        display: inline-block;
        font-weight: 700;
        color: var(--text-secondary);
    }
    
    .link-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 2rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        max-width: 600px;
    }
    
    .link-card h3 {
        font-size: 1.25rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .link-card h3 i {
        color: var(--accent-color);
    }
    
    .link-card p {
        color: var(--text-secondary);
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-group label {
        display: block;
        font-size: 0.9rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }
    
    .form-control {
        width: 100%;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s ease;
    }
    
    .form-control:focus {
        border-color: var(--accent-color);
    }
    
    .btn-submit {
        background: var(--accent-color);
        color: #1a1a1a;
        font-weight: 700;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: background 0.2s ease;
    }
    
    .btn-submit:hover {
        background: #e6b800;
    }
</style>
@endpush

@section('content')

<h3 class="section-title" style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
    <i class="fa-solid fa-child" style="color: var(--accent-color);"></i> الأبناء المرتبطون حالياً
</h3>

@if($parent_children->isNotEmpty())
    <div class="children-grid">
        @foreach($parent_children as $child)
            <div class="child-card">
                <div class="child-avatar">
                    @if($child->avatar)
                        <img src="{{ asset('storage/' . $child->avatar) }}" alt="Avatar">
                    @else
                        {{ mb_substr($child->full_name, 0, 1) }}
                    @endif
                </div>
                <div class="child-details">
                    <div class="child-name">{{ $child->full_name }}</div>
                    <div class="child-meta">{{ $child->department ?? 'تخصص عام' }} • {{ $child->level ?? 'غير محدد' }}</div>
                    <div class="child-code">الرقم الجامعي: {{ $child->student_code }}</div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div style="text-align: center; padding: 3rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color); margin-bottom: 3rem; color: var(--text-secondary);">
        <i class="fa-solid fa-users-slash" style="font-size: 2.5rem; opacity: 0.5; margin-bottom: 1rem; display: block;"></i>
        لا يوجد أي أبناء مرتبطين بحسابك حالياً.
    </div>
@endif

<div class="link-card">
    <h3><i class="fa-solid fa-link"></i> ربط ابن جديد</h3>
    <p>يرجى إدخال الرقم الجامعي للابن لتتمكن من ربطه بحسابك. يرجى الملاحظة أن نظام الحماية يتطلب تطابق اسم العائلة (الاسم الأخير) بين حسابك وحساب الابن لإتمام عملية الربط بنجاح.</p>
    
    <form action="{{ route('parent.children.link') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="student_code">الرقم الجامعي للابن</label>
            <input type="text" name="student_code" id="student_code" class="form-control" placeholder="مثال: 20230012" required>
        </div>
        <button type="submit" class="btn-submit">
            <i class="fa-solid fa-plus"></i> ربط الابن بالحساب
        </button>
    </form>
</div>

@endsection
