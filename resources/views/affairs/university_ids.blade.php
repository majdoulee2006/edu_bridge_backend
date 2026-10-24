@extends('layouts.affairs')
@section('title', __('messages.university_ids'))

@push('styles')
<style>
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
.page-header h2 { font-size:1.8rem; font-weight:800; color:var(--text-primary); }
.add-btn { background:var(--accent-color); color:#1a1a1a; border:none; padding:0.8rem 1.5rem; border-radius:0.8rem; font-weight:800; cursor:pointer; display:flex; align-items:center; gap:0.5rem; transition:opacity 0.2s; }
.add-btn:hover { opacity:0.9; }

/* Filter Bar */
.filters-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 1rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}
.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 0.85rem;
    align-items: flex-end;
}
.filter-item {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.filter-item label {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.filter-select, .filter-search-input {
    width: 100%;
    padding: 0.65rem 0.85rem;
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    background: var(--bg-primary);
    color: var(--text-primary);
    font-size: 0.9rem;
    font-family: inherit;
    box-sizing: border-box;
    transition: all 0.2s;
    outline: none;
}
.filter-select:focus, .filter-search-input:focus {
    border-color: var(--accent-color);
}
.btn-reset-filter {
    padding: 0.65rem 1rem;
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    border-radius: 0.75rem;
    cursor: pointer;
    font-weight: 700;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    transition: all 0.2s;
    height: 40px;
    width: 100%;
}
.btn-reset-filter:hover {
    color: #ef4444;
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.05);
}

table { width:100%; border-collapse:collapse; background:var(--bg-secondary); border-radius:1rem; overflow:hidden; }
th { background:var(--bg-primary); padding:1rem 1rem; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; font-size:0.85rem; color:var(--text-secondary); font-weight:700; white-space:nowrap; }
td { padding:0.9rem 1rem; border-bottom:1px solid var(--border-color); color:var(--text-primary); font-size:0.92rem; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; }
tr:last-child td { border-bottom:none; }
.badge-used { background:#fee2e2; color:#dc2626; padding:0.25rem 0.7rem; border-radius:2rem; font-size:0.75rem; font-weight:700; white-space:nowrap; }
.badge-free { background:#d1fae5; color:#059669; padding:0.25rem 0.7rem; border-radius:2rem; font-size:0.75rem; font-weight:700; white-space:nowrap; }
.academic-chip { display:inline-block; font-size:0.78rem; background:rgba(255,255,255,0.06); border:1px solid var(--border-color); padding:0.2rem 0.55rem; border-radius:0.45rem; color:var(--text-secondary); }

.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; }
.modal-overlay.active { display:flex; }
.modal-content { background:var(--bg-secondary); border-radius:1.5rem; padding:2rem; width:560px; max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; }
.modal-content h3, .modal-content label { color:var(--text-primary) !important; }
.modal-content .form-control { background:var(--bg-primary); color:var(--text-primary); border-color:var(--border-color); }
.modal-content h3 { font-size:1.3rem; font-weight:800; margin-bottom:1.5rem; }
.form-group { margin-bottom:1.1rem; }
.form-group label { display:block; font-weight:700; margin-bottom:0.45rem; font-size:0.88rem; }
.form-control { width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border-color); border-radius:0.75rem; background:var(--bg-primary); color:var(--text-primary); font-size:0.95rem; font-family:inherit; box-sizing:border-box; }
.form-row { display:flex; gap:0.85rem; }
.form-row .form-group { flex:1; }
.btn-save { width:100%; padding:0.9rem; background:var(--accent-color); color:#1a1a1a; border:none; border-radius:0.8rem; font-weight:800; font-size:1rem; cursor:pointer; margin-top:0.5rem; }
.btn-delete { background:none; border:none; color:#ef4444; cursor:pointer; padding:0.35rem 0.75rem; border-radius:0.5rem; font-size:0.85rem; }
.btn-delete:hover { background:#fee2e2; }
.student-photo { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--accent-color); }
.student-photo-placeholder { width:40px; height:40px; border-radius:50%; background:var(--bg-primary); display:flex; align-items:center; justify-content:center; color:var(--text-secondary); font-size:1.1rem; }
.photo-upload-area { border:2px dashed var(--border-color); border-radius:1rem; padding:1.2rem; text-align:center; cursor:pointer; transition:border-color 0.3s; position:relative; }
.photo-upload-area:hover { border-color:var(--accent-color); }
.photo-upload-area input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; }
.photo-preview { width:70px; height:70px; border-radius:50%; object-fit:cover; margin:0 auto 0.5rem; display:block; border:2px solid var(--accent-color); }
.optional-label { font-weight:400; font-size:0.75rem; color:#999; margin-inline-start:0.3rem; }
</style>
@endpush

@section('content')
<div style="max-width:1200px; margin:2rem auto; padding:0 1rem;">
    <div class="page-header">
        <h2><i class="fa-solid fa-id-card" style="color:var(--accent-color); margin-inline-end:0.5rem;"></i> {{ __('messages.university_ids') }}</h2>
        <button class="add-btn" onclick="document.getElementById('addModal').classList.add('active')">
            <i class="fa-solid fa-plus"></i> {{ __('messages.add_university_id') }}
        </button>
    </div>

    @if(isset($errors) && $errors->any())
        <div style="background:#fee2e2; border:1px solid #f87171; color:#b91c1c; padding:0.85rem 1.25rem; border-radius:0.75rem; margin-bottom:1.5rem; font-size:0.9rem;">
            <ul style="margin:0; padding-inline-start:1.25rem;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #86efac; color:#15803d; padding:0.85rem 1.25rem; border-radius:0.75rem; margin-bottom:1.5rem; font-size:0.9rem;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #f87171; color:#b91c1c; padding:0.85rem 1.25rem; border-radius:0.75rem; margin-bottom:1.5rem; font-size:0.9rem;">
            {{ session('error') }}
        </div>
    @endif

    {{-- ===== بطاقة الفلترة المتقدمة (القسم - التخصص - السنة) ===== --}}
    <div class="filters-card">
        <form method="GET" action="{{ route('affairs.university_ids') }}" id="filterForm">
            <div class="filters-grid">
                {{-- فلترة القسم --}}
                <div class="filter-item">
                    <label><i class="fa-solid fa-building-columns" style="color:var(--accent-color);"></i> القسم</label>
                    <select name="department_id" id="filter_department" class="filter-select" onchange="onFilterDeptChange()">
                        <option value="">جميع الأقسام</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}" {{ request('department_id') == $dept->department_id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- فلترة التخصص --}}
                <div class="filter-item">
                    <label><i class="fa-solid fa-graduation-cap" style="color:var(--accent-color);"></i> التخصص</label>
                    <select name="program_id" id="filter_program" class="filter-select" onchange="applyInstantFilter()">
                        <option value="">جميع التخصصات</option>
                        @foreach($programs as $prog)
                            <option value="{{ $prog->id }}" data-dept="{{ $prog->department_id }}" {{ request('program_id') == $prog->id ? 'selected' : '' }}>
                                {{ $prog->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- فلترة السنة الدراسية --}}
                <div class="filter-item">
                    <label><i class="fa-solid fa-calendar-days" style="color:var(--accent-color);"></i> السنة الدراسية</label>
                    <select name="academic_year" id="filter_year" class="filter-select" onchange="applyInstantFilter()">
                        <option value="">جميع السنوات</option>
                        <option value="السنة الأولى" {{ request('academic_year') == 'السنة الأولى' ? 'selected' : '' }}>السنة الأولى</option>
                        <option value="السنة الثانية" {{ request('academic_year') == 'السنة الثانية' ? 'selected' : '' }}>السنة الثانية</option>
                    </select>
                </div>

                {{-- فلترة الحالة --}}
                <div class="filter-item">
                    <label><i class="fa-solid fa-circle-check" style="color:var(--accent-color);"></i> حالة الرقم</label>
                    <select name="status" id="filter_status" class="filter-select" onchange="applyInstantFilter()">
                        <option value="">جميع الحالات</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>متاح (غير مستخدم)</option>
                        <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>مستخدم (لديه حساب)</option>
                    </select>
                </div>

                {{-- بحث سريع --}}
                <div class="filter-item" style="grid-column: span 1;">
                    <label><i class="fa-solid fa-magnifying-glass" style="color:var(--accent-color);"></i> بحث سريع</label>
                    <input type="text" name="search" id="filter_search" class="filter-search-input"
                           placeholder="الرقم أو الاسم أو الهاتف..."
                           value="{{ request('search') }}"
                           oninput="applyInstantFilter()">
                </div>

                {{-- زر تفريغ الفلاتر --}}
                <div class="filter-item">
                    <label>&nbsp;</label>
                    <button type="button" onclick="resetAllFilters()" class="btn-reset-filter">
                        <i class="fa-solid fa-arrow-rotate-right"></i> إعادة ضبط
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- عداد النتائج --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; font-size:0.85rem; color:var(--text-secondary);">
        <span>عرض <strong id="visible-count" style="color:var(--text-primary);">{{ count($ids) }}</strong> من إجمالي <strong>{{ count($ids) }}</strong> رقم جامعي</span>
    </div>

    <div style="overflow-x:auto; border-radius:1rem;">
        <table>
            <thead>
                <tr>
                    <th>{{ __('messages.photo') }}</th>
                    <th>{{ __('messages.university_id') }}</th>
                    <th>{{ __('messages.full_name') }}</th>
                    <th>القسم</th>
                    <th>التخصص</th>
                    <th>السنة الدراسية</th>
                    <th>{{ __('messages.phone') }}</th>
                    <th>{{ __('messages.telegram') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.date_added') }}</th>
                    <th style="text-align: center;">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody id="uids-table-body">
                @forelse($ids as $uid)
                <tr class="uid-row"
                    data-dept="{{ $uid->department_id ?? '' }}"
                    data-prog="{{ $uid->program_id ?? '' }}"
                    data-year="{{ $uid->academic_year ?? '' }}"
                    data-status="{{ $uid->is_used ? 'used' : 'available' }}"
                    data-search="{{ mb_strtolower(($uid->university_id ?? '') . ' ' . ($uid->full_name ?? '') . ' ' . ($uid->phone ?? '') . ' ' . ($uid->department_name ?? '') . ' ' . ($uid->program_name ?? '')) }}">
                    <td>
                        @if(isset($uid->photo) && $uid->photo)
                            <img src="{{ asset('storage/' . $uid->photo) }}" alt="Photo" class="student-photo">
                        @else
                            <div class="student-photo-placeholder">
                                <i class="fa-solid fa-user"></i>
                            </div>
                        @endif
                    </td>
                    <td><strong>{{ $uid->university_id }}</strong></td>
                    <td>{{ $uid->full_name }}</td>
                    <td>
                        @if($uid->department_name)
                            <span class="academic-chip">{{ $uid->department_name }}</span>
                        @else
                            <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($uid->program_name)
                            <span class="academic-chip" style="color:var(--accent-color); border-color:rgba(242,242,13,0.3);">{{ $uid->program_name }}</span>
                        @else
                            <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($uid->academic_year)
                            <span class="academic-chip">{{ $uid->academic_year }}</span>
                        @else
                            <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td dir="ltr" style="text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">{{ $uid->phone ?? '—' }}</td>
                    <td>
                        @if($uid->telegram_chat_id)
                            <code>{{ $uid->telegram_chat_id }}</code>
                        @else
                            <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($uid->is_used)
                            <span class="badge-used">{{ __('messages.used') }}</span>
                        @else
                            <span class="badge-free">{{ __('messages.available') }}</span>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($uid->created_at)->format('Y-m-d') }}</td>
                    <td style="text-align: center;">
                        @if(!$uid->is_used)
                        <div style="display:flex; gap:0.5rem; justify-content:center;">
                            <button type="button" onclick="openEditModal({{ json_encode($uid) }})" class="btn-delete" style="background:transparent; border:1px solid #3b82f6; color:#3b82f6;">
                                <i class="fa-solid fa-pen"></i> {{ __('messages.edit') }}
                            </button>
                            <form method="POST" action="{{ route('affairs.university_ids.delete', $uid->id) }}" onsubmit="return confirm('{{ __('messages.confirm_delete_id') }}')">
                                @csrf
                                <button type="submit" class="btn-delete"><i class="fa-solid fa-trash"></i> {{ __('messages.delete') }}</button>
                            </form>
                        </div>
                        @else
                            <span style="color:var(--text-secondary); font-size:0.85rem;">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr id="no-records-row">
                    <td colspan="11" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                        <i class="fa-solid fa-inbox" style="font-size:2rem; opacity:0.4; display:block; margin-bottom:0.75rem;"></i>
                        {{ __('messages.no_university_ids_yet') }}
                    </td>
                </tr>
                @endforelse
                <tr id="no-filter-match-row" style="display:none;">
                    <td colspan="11" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                        <i class="fa-solid fa-filter-circle-xmark" style="font-size:2rem; opacity:0.4; display:block; margin-bottom:0.75rem;"></i>
                        لا توجد نتائج تطابق معايير الفلترة المحددة.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal إضافة -->
<div class="modal-overlay" id="addModal">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3><i class="fa-solid fa-plus-circle" style="color:var(--accent-color); margin-inline-end:0.5rem;"></i> {{ __('messages.add_university_id') }}</h3>
            <button onclick="document.getElementById('addModal').classList.remove('active')"
                    style="background:none; border:none; color:var(--text-secondary); font-size:1.3rem; cursor:pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('affairs.university_ids.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label style="font-weight:700;"><i class="fa-solid fa-id-card" style="color:var(--accent-color); margin-inline-end:0.35rem;"></i> {{ __('messages.university_id') }} <span style="color:red;">*</span></label>
                <input type="text" name="university_id" class="form-control" placeholder="{{ app()->getLocale() === 'ar' ? 'أدخل الرقم الجامعي كما هو بالهوية الجامعية (مثال: 2024105)' : 'Student ID as on card (e.g., 2024105)' }}" required dir="ltr" value="{{ old('university_id') }}">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('messages.first_name') }} <span style="color:red;">*</span></label>
                    <input type="text" name="first_name" class="form-control" placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: أحمد' : 'e.g., Ahmad' }}" required value="{{ old('first_name') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('messages.last_name') }} <span style="color:red;">*</span></label>
                    <input type="text" name="last_name" class="form-control" placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: محمود' : 'e.g., Mahmoud' }}" required value="{{ old('last_name') }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('messages.date_of_birth') }} <span class="optional-label">{{ __('messages.optional') }}</span></label>
                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('messages.phone') }} <span class="optional-label">{{ __('messages.optional') }}</span></label>
                    <input type="text" name="phone" class="form-control" placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: 0912345678' : 'e.g., 0912345678' }}" value="{{ old('phone') }}">
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('messages.telegram_chat_id') }} <span class="optional-label">{{ __('messages.optional') }}</span></label>
                <input type="text" name="telegram_chat_id" class="form-control" placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: 123456789' : 'e.g., 123456789' }}" value="{{ old('telegram_chat_id') }}">
            </div>

            <div class="form-group">
                <label>{{ __('messages.student_photo') }} <span class="optional-label">{{ __('messages.optional') }}</span></label>
                <div class="photo-upload-area" id="addPhotoArea">
                    <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this, 'addPhotoPreview', 'addPhotoArea')">
                    <img id="addPhotoPreview" class="photo-preview" style="display:none;">
                    <div id="addPhotoText">
                        <i class="fa-solid fa-cloud-arrow-up" style="font-size:1.5rem; color:#999; margin-bottom:0.5rem; display:block;"></i>
                        <span style="color:#666; font-size:0.9rem;">{{ __('messages.click_to_choose_photo') }}</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> {{ __('messages.create_university_id') }}
            </button>
        </form>
    </div>
</div>

<!-- Modal التعديل -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3><i class="fa-solid fa-pen" style="color:var(--accent-color); margin-inline-end:0.5rem;"></i> {{ __('messages.edit_student_data') }}</h3>
            <button onclick="document.getElementById('editModal').classList.remove('active')"
                    style="background:none; border:none; color:var(--text-secondary); font-size:1.3rem; cursor:pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editForm" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label style="font-weight:700;"><i class="fa-solid fa-id-card" style="color:var(--accent-color); margin-inline-end:0.35rem;"></i> {{ __('messages.university_id') }} <span style="color:red;">*</span></label>
                <input type="text" id="edit_university_id" name="university_id" class="form-control" required dir="ltr">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('messages.first_name') }} <span style="color:red;">*</span></label>
                    <input type="text" id="edit_first_name" name="first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.last_name') }} <span style="color:red;">*</span></label>
                    <input type="text" id="edit_last_name" name="last_name" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('messages.date_of_birth') }} <span class="optional-label">{{ __('messages.optional') }}</span></label>
                    <input type="date" id="edit_date_of_birth" name="date_of_birth" class="form-control">
                </div>
                <div class="form-group">
                    <label>{{ __('messages.phone') }} <span class="optional-label">{{ __('messages.optional') }}</span></label>
                    <input type="text" id="edit_phone" name="phone" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('messages.telegram_chat_id') }} <span class="optional-label">{{ __('messages.optional') }}</span></label>
                <input type="text" id="edit_telegram_chat_id" name="telegram_chat_id" class="form-control">
            </div>

            <div class="form-group">
                <label>{{ __('messages.student_photo_edit_hint') }}</label>
                <div class="photo-upload-area" id="editPhotoArea">
                    <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this, 'editPhotoPreview', 'editPhotoArea')">
                    <img id="editPhotoPreview" class="photo-preview" style="display:none;">
                    <div id="editPhotoText">
                        <i class="fa-solid fa-cloud-arrow-up" style="font-size:1.5rem; color:#999; margin-bottom:0.5rem; display:block;"></i>
                        <span style="color:#666; font-size:0.9rem;">{{ __('messages.click_to_choose_new_photo') }}</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> {{ __('messages.save_changes') }}
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

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});

// فلترة التخصصات بحسب القسم المختار في القوائم المنسدلة
function filterProgramsByDept(deptId, targetSelectId) {
    const select = document.getElementById(targetSelectId);
    if (!select) return;
    const options = select.querySelectorAll('option');
    let hasSelected = false;

    options.forEach(opt => {
        if (!opt.value) return; // خيار "اختر التخصص"
        const optDept = opt.getAttribute('data-dept');
        if (!deptId || optDept === String(deptId)) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
            if (opt.selected) {
                opt.selected = false;
                hasSelected = true;
            }
        }
    });

    if (hasSelected) {
        select.value = '';
    }
}

// عند تغيير فلتر القسم في شريط الفلترة الرئيسي
function onFilterDeptChange() {
    const deptId = document.getElementById('filter_department').value;
    filterProgramsByDept(deptId, 'filter_program');
    applyInstantFilter();
}

// تطبيق الفلترة الفورية في الجدول (بدون انتظار إعادة تحميل الصفحة)
function applyInstantFilter() {
    const selectedDept = document.getElementById('filter_department').value.trim();
    const selectedProg = document.getElementById('filter_program').value.trim();
    const selectedYear = document.getElementById('filter_year').value.trim();
    const selectedStatus = document.getElementById('filter_status').value.trim();
    const searchQuery = document.getElementById('filter_search').value.toLowerCase().trim();

    const rows = document.querySelectorAll('.uid-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowDept = row.getAttribute('data-dept');
        const rowProg = row.getAttribute('data-prog');
        const rowYear = row.getAttribute('data-year');
        const rowStatus = row.getAttribute('data-status');
        const rowSearch = row.getAttribute('data-search') || '';

        let match = true;

        if (selectedDept && rowDept !== selectedDept) match = false;
        if (selectedProg && rowProg !== selectedProg) match = false;
        if (selectedYear && rowYear !== selectedYear) match = false;
        if (selectedStatus && rowStatus !== selectedStatus) match = false;
        if (searchQuery && !rowSearch.includes(searchQuery)) match = false;

        if (match) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // تحديث العداد
    const counter = document.getElementById('visible-count');
    if (counter) counter.innerText = visibleCount;

    // إظهار رسالة لا توجد نتائج عند عدم تطابق أي صف
    const noMatchRow = document.getElementById('no-filter-match-row');
    if (noMatchRow) {
        noMatchRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
}

// إعادة ضبط جميع الفلاتر
function resetAllFilters() {
    document.getElementById('filter_department').value = '';
    document.getElementById('filter_program').value = '';
    document.getElementById('filter_year').value = '';
    document.getElementById('filter_status').value = '';
    document.getElementById('filter_search').value = '';

    filterProgramsByDept('', 'filter_program');
    applyInstantFilter();
}

function openEditModal(uid) {
    const form = document.getElementById('editForm');
    form.action = `/affairs/university-ids/update/${uid.id}`;
    document.getElementById('edit_university_id').value = uid.university_id || '';
    document.getElementById('edit_first_name').value = uid.first_name || uid.full_name || '';
    document.getElementById('edit_last_name').value = uid.last_name || '';
    document.getElementById('edit_date_of_birth').value = uid.date_of_birth || '';
    document.getElementById('edit_phone').value = uid.phone || '';
    document.getElementById('edit_telegram_chat_id').value = uid.telegram_chat_id || '';

    const preview = document.getElementById('editPhotoPreview');
    const text = document.getElementById('editPhotoText');
    if (uid.photo) {
        preview.src = `/storage/${uid.photo}`;
        preview.style.display = 'block';
        text.style.display = 'none';
    } else {
        preview.style.display = 'none';
        text.style.display = 'block';
    }

    document.getElementById('editModal').classList.add('active');
}

function previewPhoto(input, previewId, areaId) {
    const preview = document.getElementById(previewId);
    const textId = areaId === 'addPhotoArea' ? 'addPhotoText' : 'editPhotoText';
    const text = document.getElementById(textId);

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            text.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// تنفيذ الفلترة الأولية في حال وجود معايير في الرابط
document.addEventListener('DOMContentLoaded', function() {
    const currentDept = document.getElementById('filter_department').value;
    if (currentDept) {
        filterProgramsByDept(currentDept, 'filter_program');
    }
});
</script>
@endpush
