@extends('layouts.affairs')
@section('title', 'طلبات التسجيل')

@push('styles')
<style>
.pending-container {
    max-width: 1100px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}
.page-header h2 {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.badge-count {
    background: #ffcc00;
    color: #1a1a1a;
    padding: 0.2rem 0.8rem;
    border-radius: 2rem;
    font-size: 0.85rem;
    font-weight: 800;
}

/* Card Grid */
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.student-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 1.25rem;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    position: relative;
}
.student-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.15);
    border-color: #ffcc00;
}

.avatar-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    background: #ffcc00;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    border: 3px solid var(--bg-primary);
    box-shadow: 0 4px 12px rgba(255, 204, 0, 0.3);
}
.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.avatar-initial {
    font-size: 2rem;
    font-weight: 800;
    color: #1a1a1a;
}

.card-name {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--text-primary);
    margin-bottom: 0.3rem;
}
.card-role {
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-weight: 700;
    margin-bottom: 0.8rem;
    background: var(--bg-primary);
    padding: 0.25rem 0.85rem;
    border-radius: 1rem;
}
.card-dept {
    font-size: 0.82rem;
    color: var(--text-secondary);
    margin-bottom: 1.2rem;
}
.view-details-btn {
    width: 100%;
    padding: 0.65rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    color: var(--text-primary);
    font-weight: 700;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}
.student-card:hover .view-details-btn {
    background: #ffcc00;
    color: #1a1a1a;
    border-color: #ffcc00;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}
.modal-overlay.active {
    opacity: 1;
    pointer-events: auto;
}
.modal-card {
    background: var(--bg-secondary);
    border-radius: 1.5rem;
    width: 90%;
    max-width: 480px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    padding: 1.75rem;
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
    border: 1px solid var(--border-color);
    transform: translateY(20px);
    transition: transform 0.25s ease;
}
.modal-overlay.active .modal-card {
    transform: translateY(0);
}
.modal-header-close {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 0.25rem;
}
.close-btn {
    background: var(--bg-primary);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    color: var(--text-secondary);
    font-size: 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.close-btn:hover {
    background: #ef4444;
    color: #fff;
}

.modal-body {
    overflow-y: auto;
    padding-right: 0.25rem;
    flex: 1;
}

.modal-profile-header {
    text-align: center;
    margin-bottom: 1.5rem;
}
.modal-avatar-wrapper {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    background: #ffcc00;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    box-shadow: 0 6px 18px rgba(255, 204, 0, 0.4);
    border: 4px solid var(--bg-primary);
}
.modal-name {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--text-primary);
    margin-bottom: 0.2rem;
}

.detail-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0.9rem;
    background: var(--bg-primary);
    border-radius: 0.85rem;
    margin-bottom: 0.75rem;
}
.detail-icon {
    width: 38px;
    height: 38px;
    border-radius: 0.6rem;
    background: rgba(255, 204, 0, 0.15);
    color: #ffcc00;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.detail-content {
    display: flex;
    flex-direction: column;
}
.detail-label {
    font-size: 0.72rem;
    color: var(--text-secondary);
    font-weight: 700;
}
.detail-value {
    font-size: 0.92rem;
    color: var(--text-primary);
    font-weight: 700;
    word-break: break-all;
}

.modal-actions {
    display: flex;
    gap: 0.8rem;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}
.btn-approve-act {
    flex: 1;
    padding: 0.85rem;
    background: #ffcc00;
    color: #1a1a1a;
    border: none;
    border-radius: 0.85rem;
    font-weight: 800;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: opacity 0.2s;
}
.btn-approve-act:hover {
    opacity: 0.9;
}
.btn-reject-act {
    flex: 1;
    padding: 0.85rem;
    background: transparent;
    color: #ef4444;
    border: 2px solid #ef4444;
    border-radius: 0.85rem;
    font-weight: 800;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}
.btn-reject-act:hover {
    background: #ef4444;
    color: #fff;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}
.empty-state i {
    font-size: 3.5rem;
    opacity: 0.3;
    margin-bottom: 1rem;
    display: block;
}
</style>
@endpush

@section('content')
<div class="pending-container">
    <div class="page-header">
        <h2>
            <i class="fa-solid fa-user-clock" style="color:#ffcc00;"></i>
            طلبات التسجيل المعلّقة
            @if($pending->count() > 0)
                <span class="badge-count">{{ $pending->count() }}</span>
            @endif
        </h2>
    </div>

    @if($pending->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p style="font-size:1.2rem; font-weight:800; color:var(--text-primary);">لا توجد طلبات تسجيل معلّقة حالياً</p>
            <p style="font-size:0.9rem; margin-top:0.5rem;">جميع طلبات الطلاب وأولياء الأمور تمت مراجعتها بنجاح.</p>
        </div>
    @else
        <div class="card-grid">
            @foreach($pending as $user)
            @php
                $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : null;
                $telegramVal = $user->telegram_chat_id ?? $user->telegram_id ?? '7821980919';
                $roleLabel = $user->role_id == 3 ? '🎓 طالب' : '👨‍👩‍👧 ولي أمر';
            @endphp
            <div class="student-card" onclick="openStudentModal({{ json_encode([
                'user_id'          => $user->user_id,
                'full_name'        => $user->full_name,
                'avatar'           => $avatarUrl,
                'role'             => $user->role_id == 3 ? 'student' : 'parent',
                'role_label'       => $roleLabel,
                'email'            => $user->email ?? 'غير متوفر',
                'phone'            => $user->phone ?? 'غير متوفر',
                'birth_date'       => $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->format('Y-m-d') : 'غير متوفر',
                'gender'           => $user->gender ?? 'غير متوفر',
                'department'       => $user->department ?? 'غير متوفر',
                'branch'           => $user->branch ?? 'غير متوفر',
                'academic_year'    => $user->academic_year ?? 'غير متوفر',
                'telegram'         => $telegramVal,
                'university_id'    => $user->university_id,
                'approve_url'      => route('affairs.pending_accounts.approve', $user->user_id),
                'reject_url'       => route('affairs.pending_accounts.reject', $user->user_id),
            ]) }})">
                <div class="avatar-wrapper">
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $user->full_name }}" class="avatar-img">
                    @else
                        <span class="avatar-initial">{{ mb_substr($user->full_name, 0, 1) }}</span>
                    @endif
                </div>

                <div class="card-name">{{ $user->full_name }}</div>
                <div class="card-role">{{ $roleLabel }}</div>

                @if($user->role_id == 3)
                    <div class="card-dept">
                        <i class="fa-solid fa-graduation-cap" style="color:#ffcc00;"></i>
                        {{ $user->department ?? 'غير محدد' }} {{ $user->branch ? '- ' . $user->branch : '' }}
                    </div>
                @else
                    <div class="card-dept">
                        <i class="fa-solid fa-envelope" style="color:#ffcc00;"></i>
                        {{ $user->email }}
                    </div>
                @endif

                <button class="view-details-btn">
                    <i class="fa-solid fa-eye"></i> عرض كافة البيانات
                </button>
            </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Details Modal -->
<div class="modal-overlay" id="studentDetailModal" onclick="closeModalOnBackdrop(event)">
    <div class="modal-card">
        <div class="modal-header-close">
            <button class="close-btn" onclick="closeStudentModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">
            <div class="modal-profile-header">
                <div class="modal-avatar-wrapper" id="modalAvatarContainer">
                    <!-- Dynamic Avatar -->
                </div>
                <div class="modal-name" id="modalFullName">---</div>
                <div style="font-size:0.85rem; color:var(--text-secondary); font-weight:700;" id="modalRoleLabel">---</div>
            </div>

            <div id="modalDetailsContainer">
                <!-- Dynamic Detail Rows -->
            </div>
        </div>

        <div class="modal-actions">
            <form id="approveForm" method="POST" action="" style="flex:1;">
                @csrf
                <button type="submit" class="btn-approve-act">
                    <i class="fa-solid fa-circle-check"></i> موافقة وتفعيل
                </button>
            </form>

            <form id="rejectForm" method="POST" action="" style="flex:1;" onsubmit="return confirm('هل أنت متأكد من رفض هذا الطلب وحذفه؟')">
                @csrf
                <button type="submit" class="btn-reject-act">
                    <i class="fa-solid fa-circle-xmark"></i> رفض الطلب
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openStudentModal(data) {
    // 1. Avatar setup
    const avatarContainer = document.getElementById('modalAvatarContainer');
    if (data.avatar) {
        avatarContainer.innerHTML = `<img src="${data.avatar}" alt="${data.full_name}" class="avatar-img">`;
    } else {
        const initial = data.full_name ? data.full_name.charAt(0) : '🎓';
        avatarContainer.innerHTML = `<span class="avatar-initial">${initial}</span>`;
    }

    // 2. Name & Role
    document.getElementById('modalFullName').innerText = data.full_name;
    document.getElementById('modalRoleLabel').innerText = data.role_label;

    // 3. Populate Details
    let detailsHtml = '';
    
    detailsHtml += buildDetailRow('البريد الإلكتروني', data.email, 'fa-envelope');
    detailsHtml += buildDetailRow('رقم الهاتف', data.phone, 'fa-phone');
    detailsHtml += buildDetailRow('تاريخ الميلاد', data.birth_date, 'fa-calendar-days');
    detailsHtml += buildDetailRow('الجنس', data.gender, 'fa-user');

    if (data.role === 'student') {
        detailsHtml += buildDetailRow('القسم', data.department, 'fa-graduation-cap');
        detailsHtml += buildDetailRow('الفرع', data.branch, 'fa-diagram-project');
        detailsHtml += buildDetailRow('السنة الدراسية', data.academic_year, 'fa-chart-line');
        detailsHtml += buildDetailRow('معرّف تليجرام', data.telegram, 'fa-paper-plane');
        
        const uniIdText = (data.university_id && data.university_id.toString().trim() !== '')
            ? data.university_id
            : 'سيتم توليده تلقائياً عند الموافقة ✨';
        detailsHtml += buildDetailRow('الرقم الجامعي', uniIdText, 'fa-id-card');
    } else {
        detailsHtml += buildDetailRow('معرّف تليجرام', data.telegram, 'fa-paper-plane');
    }

    document.getElementById('modalDetailsContainer').innerHTML = detailsHtml;

    // 4. Form Actions
    document.getElementById('approveForm').action = data.approve_url;
    document.getElementById('rejectForm').action = data.reject_url;

    // 5. Open Modal
    document.getElementById('studentDetailModal').classList.add('active');
}

function buildDetailRow(label, value, iconClass) {
    return `
        <div class="detail-row">
            <div class="detail-icon"><i class="fa-solid ${iconClass}"></i></div>
            <div class="detail-content">
                <span class="detail-label">${label}</span>
                <span class="detail-value">${value}</span>
            </div>
        </div>
    `;
}

function closeStudentModal() {
    document.getElementById('studentDetailModal').classList.remove('active');
}

function closeModalOnBackdrop(e) {
    if (e.target.id === 'studentDetailModal') {
        closeStudentModal();
    }
}
</script>
@endpush
