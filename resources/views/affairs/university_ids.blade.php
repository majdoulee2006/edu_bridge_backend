@extends('layouts.affairs')
@section('title', 'الأرقام الجامعية')

@push('styles')
<style>
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; }
.page-header h2 { font-size:1.8rem; font-weight:800; color:var(--text-primary); }
.add-btn { background:var(--accent-color); color:var(--primary-dark); border:none; padding:0.8rem 1.5rem; border-radius:0.8rem; font-weight:800; cursor:pointer; display:flex; align-items:center; gap:0.5rem; }
.add-btn:hover { opacity:0.9; }
table { width:100%; border-collapse:collapse; background:var(--surface); border-radius:1rem; overflow:hidden; }
th { background:var(--surface-2); padding:1rem 1.2rem; text-align:right; font-size:0.85rem; color:var(--text-secondary); font-weight:700; }
td { padding:1rem 1.2rem; border-bottom:1px solid var(--border); color:var(--text-primary); font-size:0.95rem; }
tr:last-child td { border-bottom:none; }
.badge-used { background:#fee2e2; color:#dc2626; padding:0.25rem 0.8rem; border-radius:2rem; font-size:0.8rem; font-weight:700; }
.badge-free { background:#d1fae5; color:#059669; padding:0.25rem 0.8rem; border-radius:2rem; font-size:0.8rem; font-weight:700; }
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; }
.modal-overlay.active { display:flex; }
.modal-content { background:#ffffff; border-radius:1.5rem; padding:2rem; width:420px; max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
.modal-content h3, .modal-content label { color:#1a1a1a !important; }
.modal-content .form-control { background:#f5f5f5; color:#1a1a1a; border-color:#e0e0e0; }
.modal-content h3 { font-size:1.3rem; font-weight:800; margin-bottom:1.5rem; }
.form-group { margin-bottom:1.2rem; }
.form-group label { display:block; font-weight:700; margin-bottom:0.5rem; font-size:0.9rem; }
.form-control { width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.75rem; background:var(--surface-2); color:var(--text-primary); font-size:1rem; font-family:inherit; }
.btn-save { width:100%; padding:0.9rem; background:var(--accent-color); color:var(--primary-dark); border:none; border-radius:0.8rem; font-weight:800; font-size:1rem; cursor:pointer; margin-top:0.5rem; }
.btn-delete { background:none; border:none; color:#ef4444; cursor:pointer; padding:0.4rem 0.8rem; border-radius:0.5rem; font-size:0.85rem; }
.btn-delete:hover { background:#fee2e2; }
</style>
@endpush

@section('content')
<div style="max-width:1100px; margin:2rem auto; padding:0 1rem;">
    <div class="page-header">
        <h2><i class="fa-solid fa-id-card" style="color:var(--accent-color); margin-left:0.5rem;"></i> الأرقام الجامعية</h2>
        <button class="add-btn" onclick="document.getElementById('addModal').classList.add('active')">
            <i class="fa-solid fa-plus"></i> إضافة رقم جامعي
        </button>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5; color:#065f46; padding:1rem 1.5rem; border-radius:1rem; margin-bottom:1.5rem; font-weight:700;">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#fee2e2; color:#991b1b; padding:1rem 1.5rem; border-radius:1rem; margin-bottom:1.5rem; font-weight:700;">
        <i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div style="background:#fee2e2; color:#991b1b; padding:1rem 1.5rem; border-radius:1rem; margin-bottom:1.5rem; font-weight:700;">
        <i class="fa-solid fa-circle-xmark"></i> {{ $errors->first() }}
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>الرقم الجامعي</th>
                <th>الاسم</th>
                <th>الحالة</th>
                <th>تاريخ الإضافة</th>
                <th>إجراء</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ids as $uid)
            <tr>
                <td><strong>{{ $uid->university_id }}</strong></td>
                <td>{{ $uid->first_name }} {{ $uid->last_name }}</td>
                <td>
                    @if($uid->is_used)
                        <span class="badge-used">مستخدم</span>
                    @else
                        <span class="badge-free">متاح</span>
                    @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($uid->created_at)->format('Y-m-d') }}</td>
                <td>
                    @if(!$uid->is_used)
                    <form method="POST" action="{{ route('affairs.university_ids.delete', $uid->id) }}"
                          onsubmit="return confirm('حذف هذا الرقم؟')">
                        @csrf
                        <button type="submit" class="btn-delete"><i class="fa-solid fa-trash"></i> حذف</button>
                    </form>
                    @else
                        <span style="color:var(--text-secondary); font-size:0.85rem;">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                    <i class="fa-solid fa-inbox" style="font-size:2rem; opacity:0.4; display:block; margin-bottom:0.75rem;"></i>
                    لا توجد أرقام جامعية مضافة بعد
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3><i class="fa-solid fa-plus-circle" style="color:var(--accent-color); margin-left:0.5rem;"></i> إضافة رقم جامعي</h3>
            <button onclick="document.getElementById('addModal').classList.remove('active')"
                    style="background:none; border:none; color:var(--text-secondary); font-size:1.3rem; cursor:pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('affairs.university_ids.store') }}">
            @csrf
            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label>الاسم الأول</label>
                    <input type="text" name="first_name" class="form-control" placeholder="الاسم الأول" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>الاسم الثاني (الكنية)</label>
                    <input type="text" name="last_name" class="form-control" placeholder="الكنية" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>الرقم الشخصي (الوطني)</label>
                <input type="text" name="national_id" class="form-control" placeholder="يجب أن يتكون من 10 أرقام" required pattern="\d{10}" title="يجب أن يتكون من 10 أرقام">
            </div>

            <div class="form-group">
                <label>تاريخ الميلاد</label>
                <input type="date" name="birth_date" class="form-control" required max="{{ now()->subYears(18)->format('Y-m-d') }}" title="يجب أن يكون العمر 18 سنة على الأقل">
            </div>

            <div class="form-group">
                <label>كلمة المرور المبدئية</label>
                <input type="password" name="default_password" class="form-control" placeholder="كلمة المرور للطالب" required minlength="6">
            </div>

            <button type="submit" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> إنشاء الحساب
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});
</script>
@endpush
