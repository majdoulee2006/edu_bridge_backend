@extends('layouts.parent')
@section('title', 'الرئيسية')
@section('subtitle', 'أهلاً بك في لوحة تحكم ولي الأمر')

@push('styles')
<style>
    .welcome-card {
        background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        border-radius: 1.5rem;
        padding: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
    }
    .welcome-card::after {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100px; height: 100%;
        background: linear-gradient(to right, var(--accent-color) 0%, transparent 100%);
        opacity: 0.1;
    }
    .welcome-content h2 {
        font-size: 1.8rem;
        font-weight: 900;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }
    .welcome-content p {
        color: var(--text-secondary);
        font-size: 1.05rem;
        line-height: 1.6;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        gap: 1.25rem;
        transition: transform 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 1rem;
        background: var(--bg-primary);
        color: var(--accent-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        border: 1px solid var(--border-color);
    }
    
    .stat-number {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.2;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        font-weight: 600;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .section-title i {
        color: var(--accent-color);
    }

    .children-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .child-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        border: 1px solid transparent;
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }
    .child-card:hover {
        border-color: var(--accent-color);
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .child-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 4px;
        background: var(--accent-color);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .child-card:hover::before {
        opacity: 1;
    }
    
    .child-card.selected {
        border-color: var(--accent-color);
    }
    .child-card.selected::before {
        opacity: 1;
    }
    
    .child-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--bg-primary);
        color: var(--accent-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 900;
        margin-bottom: 1rem;
        border: 2px solid var(--border-color);
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .child-name {
        font-size: 1.15rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
        color: var(--text-primary);
    }
    .child-info {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 1.25rem;
    }
    .child-btn {
        background: var(--bg-primary);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        padding: 0.6rem 1.25rem;
        border-radius: 2rem;
        font-size: 0.85rem;
        font-weight: 700;
        transition: all 0.2s ease;
        width: 100%;
    }
    .child-card:hover .child-btn, .child-card.selected .child-btn {
        background: var(--accent-color);
        color: #1a1a1a;
        border-color: var(--accent-color);
    }
    
    .announcements-list {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }
    
    .announcement-item {
        display: flex;
        gap: 1.25rem;
        padding-bottom: 1.25rem;
        margin-bottom: 1.25rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    .announcement-item:last-child {
        padding-bottom: 0;
        margin-bottom: 0;
        border-bottom: none;
    }
    
    .announcement-icon {
        width: 45px;
        height: 45px;
        border-radius: 0.75rem;
        background: rgba(255, 204, 0, 0.1);
        color: var(--accent-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .announcement-details h4 {
        font-size: 1.05rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
        color: var(--text-primary);
    }
    
    .announcement-details p {
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.5;
        margin-bottom: 0.5rem;
    }
    
    .announcement-date {
        font-size: 0.8rem;
        color: var(--text-secondary);
        opacity: 0.7;
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .modal.show {
        display: flex;
        opacity: 1;
    }
    
    .modal-content {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 1.5rem;
        width: 90%;
        max-width: 500px;
        padding: 2.5rem 2rem 2rem;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        position: relative;
        transform: translateY(-20px);
        transition: transform 0.3s ease;
    }
    
    .modal.show .modal-content {
        transform: translateY(0);
    }
    
    .close-btn {
        position: absolute;
        top: 1.25rem;
        left: 1.25rem; /* Left since we are RTL */
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--text-secondary);
        cursor: pointer;
        transition: color 0.2s ease;
        line-height: 1;
    }
    
    .close-btn:hover {
        color: #ef4444;
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
        font-family: inherit;
    }
    
    .btn-submit:hover {
        background: #e6b800;
    }
</style>
@endpush

@section('content')

<div class="welcome-card">
    <div class="welcome-content">
        <h2>مرحباً بك، {{ $user->full_name }}</h2>
        <p>يمكنك من خلال هذه اللوحة متابعة الأداء الأكاديمي، جدول المحاضرات، ونتائج أبنائك بكل سهولة وشفافية.</p>
    </div>
    <div style="font-size: 5rem; color: var(--accent-color); opacity: 0.8;">
        <i class="fa-solid fa-users"></i>
    </div>
</div>


<h3 class="section-title">
    <i class="fa-solid fa-children"></i> أبنائي المسجلين ({{ $children->count() }})
</h3>

@if($children->isNotEmpty())
    <div class="children-grid">
        @foreach($children as $child)
        <div onclick="selectChildAndRedirect({{ $child->student_id }})" class="child-card {{ $selected_child_id == $child->student_id ? 'selected' : '' }}">
            <div class="child-avatar">
                @if($child->avatar)
                    <img src="{{ asset('storage/' . $child->avatar) }}" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                @else
                    {{ mb_substr($child->full_name, 0, 1) }}
                @endif
            </div>
            <div class="child-name">{{ $child->full_name }}</div>
            <div class="child-info">
                {{ $child->department ?? 'تخصص عام' }} • {{ $child->level ?? 'السنة الأولى' }}
            </div>
            <div class="child-btn">
                {{ $selected_child_id == $child->student_id ? 'عرض التفاصيل والجدول' : 'متابعة الأداء' }} <i class="fa-solid fa-arrow-left" style="margin-right: 0.4rem; font-size: 0.8rem;"></i>
            </div>
        </div>
        @endforeach

        <!-- Add Child Card -->
        <div onclick="openLinkChildModal()" class="child-card" style="border: 2px dashed var(--accent-color); background: rgba(255, 204, 0, 0.03); display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; min-height: 180px; transition: all 0.3s ease; text-align: center; border-radius: 1.5rem; padding: 1.5rem;" onmouseover="this.style.background='rgba(255, 204, 0, 0.08)'; this.style.borderColor='#e6b800';" onmouseout="this.style.background='rgba(255, 204, 0, 0.03)'; this.style.borderColor='var(--accent-color)';">
            <div class="child-avatar" style="background: rgba(255, 204, 0, 0.1); border: 2px solid var(--accent-color); color: var(--accent-color); width: 60px; height: 60px; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                <i class="fa-solid fa-plus" style="font-size: 1.5rem; color: var(--accent-color) !important;"></i>
            </div>
            <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-primary); margin-bottom: 0.25rem;">ربط ابن جديد</div>
            <div style="font-size: 0.85rem; color: var(--text-secondary);">إضافة طالب إلى حسابك</div>
        </div>
    </div>
@else
    <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color); margin-bottom: 2.5rem;">
        <i class="fa-solid fa-user-xmark" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5; margin-bottom: 1rem;"></i>
        <h4 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 0.5rem;">لا يوجد أبناء مرتبطين بحسابك</h4>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">يرجى ربط حساب ابنك للبدء بمتابعة أدائه الدراسي.</p>
        <button onclick="openLinkChildModal()" class="btn btn-primary" style="background: var(--accent-color); color: #1a1a1a; padding: 0.75rem 2rem; border-radius: 2rem; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-family: inherit;">
            <i class="fa-solid fa-plus" style="margin-left: 0.5rem;"></i> ربط ابن جديد
        </button>
    </div>
@endif

<h3 class="section-title">
    <i class="fa-solid fa-bullhorn"></i> آخر أخبار المعهد والفعاليات
</h3>

<div class="announcements-list">
    @forelse($announcements as $ann)
        <div class="announcement-item">
            <div class="announcement-icon">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div class="announcement-details">
                <h4>{{ $ann->title }}</h4>
                <p>{{ $ann->content ?? $ann->body }}</p>
                <div class="announcement-date">
                    <i class="fa-regular fa-clock" style="margin-left: 0.25rem;"></i>
                    {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}
                </div>
            </div>
        </div>
    @empty
        <div style="text-align: center; color: var(--text-secondary); padding: 1.5rem 0;">
            لا توجد إعلانات أو أخبار حالياً.
        </div>
    @endforelse
</div>

<!-- Link Child Modal -->
<div id="linkChildModal" class="modal" onclick="if(event.target == this) closeLinkChildModal()">
    <div class="modal-content">
        <span class="close-btn" onclick="closeLinkChildModal()">&times;</span>
        <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; color: var(--text-primary);">
            <i class="fa-solid fa-link" style="color: var(--accent-color);"></i> ربط ابن جديد
        </h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem;">
            يرجى إدخال الرقم الجامعي للابن لتتمكن من ربطه بحسابك.
            <br>
            <strong style="color: var(--accent-color);">تنبيه:</strong> يتطلب نظام الحماية تطابق اسم العائلة (الاسم الأخير) بين حسابك وحساب الابن لإتمام عملية الربط بنجاح.
        </p>
        
        <form action="{{ route('parent.children.link') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1.25rem;">
                <label for="student_code" style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-primary);">الرقم الجامعي للابن</label>
                <input type="text" name="student_code" id="student_code" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); padding: 0.75rem 1rem; border-radius: 0.75rem; font-family: inherit; font-size: 0.95rem; outline: none;" placeholder="مثال: 2026100" required>
            </div>
            <button type="submit" class="btn-submit" style="width: 100%; justify-content: center;">
                <i class="fa-solid fa-plus"></i> ربط الابن بالحساب
            </button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function selectChildAndRedirect(studentId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = "{{ route('parent.select_child') }}";
    form.innerHTML = `
        @csrf
        <input type="hidden" name="student_id" value="${studentId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function openLinkChildModal() {
    const modal = document.getElementById('linkChildModal');
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
}

function closeLinkChildModal() {
    const modal = document.getElementById('linkChildModal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
}
</script>
@endpush
