@extends('layouts.affairs')
@section('title', 'إدارة الحسابات')

@push('styles')
<style>
    .accounts-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    
    /* Header & Actions */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .page-header h2 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    .add-btn {
        background: var(--accent-color);
        color: var(--primary-dark);
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 0.8rem;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: transform 0.2s, opacity 0.2s;
    }
    .add-btn:hover {
        transform: translateY(-2px);
        opacity: 0.95;
    }

    /* Controls (Search & Filters) */
    .controls-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .search-box {
        position: relative;
        width: 100%;
        max-width: 400px;
    }
    .search-box i {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
    }
    .search-box input {
        width: 100%;
        padding: 0.8rem 2.5rem 0.8rem 1rem;
        border-radius: 2rem;
        border: 1px solid var(--border-color);
        background: var(--bg-secondary);
        color: var(--text-primary);
        font-family: inherit;
    }
    .search-box input:focus {
        outline: none;
        border-color: var(--accent-color);
    }

    .filters {
        display: flex;
        gap: 0.8rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }
    .filter-chip {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        border: 2px solid transparent;
        padding: 0.5rem 1.2rem;
        border-radius: 2rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .filter-chip.active, .filter-chip:hover {
        background: var(--bg-primary);
        border-color: var(--accent-color);
        color: var(--text-primary);
    }

    /* Accounts Grid */
    .accounts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    /* Account Card */
    .account-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        transition: transform 0.3s;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
    }
    .account-card:hover {
        transform: translateY(-5px);
    }
    
    .status-dot {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    .status-active { background: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.4); }
    .status-inactive { background: #ef4444; }

    .account-avatar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-color), #e5ce00);
        color: var(--primary-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 1rem;
        box-shadow: 0 4px 10px rgba(252, 227, 0, 0.3);
    }
    
    .account-name {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.3rem;
    }
    .account-role {
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 700;
        margin-bottom: 1rem;
        background: var(--bg-primary);
        padding: 0.2rem 0.8rem;
        border-radius: 1rem;
    }
    .account-email {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
    }
    
    .card-actions {
        display: flex;
        gap: 0.5rem;
        width: 100%;
        margin-top: auto;
    }
    .btn-icon {
        flex: 1;
        padding: 0.6rem;
        border: none;
        border-radius: 0.5rem;
        background: var(--bg-primary);
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-icon:hover { background: var(--accent-color); color: var(--primary-dark); }
    .btn-delete:hover { background: #ef4444; color: white; }

    /* Modal Styles */
    .modal-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex; justify-content: center; align-items: center;
        z-index: 1000;
        opacity: 0; pointer-events: none;
        transition: opacity 0.3s;
    }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }
    .modal-content {
        background: var(--bg-secondary);
        width: 90%; max-width: 500px;
        border-radius: 1.5rem;
        padding: 2rem;
        transform: translateY(-20px);
        transition: transform 0.3s;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .modal-overlay.active .modal-content { transform: translateY(0); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .modal-header h3 { margin: 0; font-size: 1.4rem; color: var(--text-primary); }
    .close-modal { background: none; border: none; font-size: 1.5rem; color: var(--text-secondary); cursor: pointer; }
    
    .form-group { margin-bottom: 1.2rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 700; color: var(--text-primary); }
    .form-control { width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit;}
    .form-control:focus { outline: none; border-color: var(--accent-color); }
    
    .btn-save { width: 100%; padding: 1rem; background: var(--accent-color); color: var(--primary-dark); border: none; border-radius: 0.5rem; font-weight: 800; font-size: 1.1rem; cursor: pointer; margin-top: 1rem; }
    .btn-save:hover { opacity: 0.9; }

</style>
@endpush

@section('content')
<div class="accounts-container">
    <!-- Header -->
    <div class="page-header">
        <h2>إدارة الحسابات (المستخدمين)</h2>
        <button class="add-btn" id="btnCreateAccount">
            <i class="fa-solid fa-user-plus"></i>
            إنشاء حساب
        </button>
    </div>

    <!-- Controls -->
    <div class="controls-wrapper">
        <div class="search-box">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchInput" placeholder="ابحث بالاسم أو البريد الإلكتروني...">
        </div>
        <div class="filters">
            <button class="filter-chip active" data-role="all">الكل</button>
            <button class="filter-chip" data-role="student">طلاب</button>
            <button class="filter-chip" data-role="teacher">معلمون</button>
            <button class="filter-chip" data-role="parent">أولياء أمور</button>
            <button class="filter-chip" data-role="head">رؤساء أقسام</button>
            <button class="filter-chip" data-role="affairs">موظفو الشؤون</button>
        </div>
    </div>

    <!-- Grid -->
    <div class="accounts-grid" id="accountsGrid">
        @forelse($users as $user)
            @php
                $roleName = $user->role->name ?? 'user';
                $roleLabel = match($roleName) {
                    'student'      => 'طالب',
                    'teacher'      => 'معلم',
                    'hod'          => 'رئيس قسم',
                    'parent'       => 'ولي أمر',
                    'affairs'      => 'موظف شؤون',
                    'admin'        => 'مدير',
                    default        => $roleName,
                };
                $roleIcon = match($roleName) {
                    'student'  => 'fa-user-graduate',
                    'teacher'  => 'fa-chalkboard-user',
                    'hod'      => 'fa-user-tie',
                    'parent'   => 'fa-users',
                    'admin'    => 'fa-shield-halved',
                    default    => 'fa-user',
                };
                $isActive = ($user->status ?? 'active') === 'active';
            @endphp
            <div class="account-card" data-role="{{ $roleName }}" data-name="{{ strtolower($user->full_name) }}" data-email="{{ strtolower($user->email) }}">
                <div class="status-dot {{ $isActive ? 'status-active' : 'status-inactive' }}" title="{{ $isActive ? 'نشط' : 'موقوف' }}"></div>
                <div class="account-avatar"><i class="fa-solid {{ $roleIcon }}"></i></div>
                <div class="account-name">{{ $user->full_name }}</div>
                <div class="account-role">{{ $roleLabel }}</div>
                <div class="account-email">{{ $user->email }}</div>
                <div class="card-actions">
                    <button class="btn-icon" title="تعديل" onclick="openModal('تعديل الحساب')">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <form method="POST" action="{{ route('affairs.accounts.toggle', $user->user_id) }}" style="flex:1;">
                        @csrf
                        <button type="submit" class="btn-icon" title="{{ $isActive ? 'إيقاف' : 'تفعيل' }}" style="width:100%;">
                            <i class="fa-solid {{ $isActive ? 'fa-ban' : 'fa-check' }}"></i>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('affairs.accounts.delete', $user->user_id) }}" style="flex:1;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الحساب؟')">
                        @csrf
                        <button type="submit" class="btn-icon btn-delete" title="حذف" style="width:100%;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-secondary);">
                <i class="fa-solid fa-users-slash" style="font-size: 3rem; opacity: 0.4; margin-bottom: 1rem;"></i>
                <p>لا يوجد مستخدمون حتى الآن.</p>
            </div>
        @endforelse
    </div>
</div>

{{-- Flash Message --}}
@if(session('success'))
<div id="flash-msg" style="position:fixed; top:1.5rem; left:50%; transform:translateX(-50%); z-index:9999;
    background:#10b981; color:white; padding:0.9rem 2rem; border-radius:1rem;
    font-weight:700; box-shadow:0 8px 24px rgba(0,0,0,0.15);
    display:flex; align-items:center; gap:0.75rem;">
    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
</div>
@endif
@if(session('error') || $errors->any())
<div id="flash-err" style="position:fixed; top:1.5rem; left:50%; transform:translateX(-50%); z-index:9999;
    background:#ef4444; color:white; padding:0.9rem 2rem; border-radius:1rem;
    font-weight:700; box-shadow:0 8px 24px rgba(0,0,0,0.15);
    display:flex; align-items:center; gap:0.75rem;">
    <i class="fa-solid fa-circle-xmark"></i> {{ session('error') ?? $errors->first() }}
</div>
@endif

<!-- Modal for Create Account -->
<div class="modal-overlay" id="accountModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">إنشاء حساب جديد</h3>
            <button class="close-modal" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('affairs.accounts.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>الاسم الكامل</label>
                    <input type="text" name="full_name" class="form-control" placeholder="أدخل اسم المستخدم" required>
                </div>
                <div class="form-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" placeholder="example@edu-bridge.com" required>
                </div>
                <div class="form-group">
                    <label>نوع الحساب (الدور)</label>
                    <select name="role_id" class="form-control" required>
                        <option value="">-- اختر الدور --</option>
                        <option value="3">طالب</option>
                        <option value="2">معلم</option>
                        <option value="5">رئيس قسم</option>
                        <option value="4">ولي أمر</option>
                        <option value="6">موظف شؤون</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>كلمة المرور المؤقتة</label>
                    <input type="password" name="password" class="form-control" placeholder="على الأقل 6 أحرف" required>
                </div>
                <button type="submit" class="btn-save">حفظ الحساب</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Modal Logic
    const modal = document.getElementById('accountModal');
    const modalTitle = document.getElementById('modalTitle');

    document.getElementById('btnCreateAccount').addEventListener('click', () => {
        openModal('إنشاء حساب جديد');
    });

    function openModal(title) {
        modalTitle.innerText = title;
        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    modal.addEventListener('click', (e) => {
        if(e.target === modal) closeModal();
    });

    // Search + Filter Logic
    const filterChips = document.querySelectorAll('.filter-chip');
    const searchInput = document.getElementById('searchInput');

    function filterAccounts() {
        const activeChip = document.querySelector('.filter-chip.active');
        const role = activeChip.getAttribute('data-role');
        const searchTerm = searchInput.value.toLowerCase();
        const cards = document.querySelectorAll('.account-card');

        cards.forEach(card => {
            const cardRole  = card.getAttribute('data-role');
            const cardName  = card.getAttribute('data-name') || '';
            const cardEmail = card.getAttribute('data-email') || '';

            const matchesRole   = (role === 'all' || cardRole === role);
            const matchesSearch = (cardName.includes(searchTerm) || cardEmail.includes(searchTerm));

            card.style.display = (matchesRole && matchesSearch) ? 'flex' : 'none';
        });
    }

    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            filterChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            filterAccounts();
        });
    });

    searchInput.addEventListener('input', filterAccounts);

    // Flash message auto-hide
    @if(session('success'))
        setTimeout(() => {
            const flash = document.getElementById('flash-msg');
            if(flash) flash.style.display = 'none';
        }, 4000);
    @endif
</script>
@endpush
