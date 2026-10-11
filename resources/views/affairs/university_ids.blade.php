@extends('layouts.affairs')
@section('title', __('messages.university_ids'))

@push('styles')
<style>
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; }
.page-header h2 { font-size:1.8rem; font-weight:800; color:var(--text-primary); }
.add-btn { background:var(--accent-color); color:#1a1a1a; border:none; padding:0.8rem 1.5rem; border-radius:0.8rem; font-weight:800; cursor:pointer; display:flex; align-items:center; gap:0.5rem; }
.add-btn:hover { opacity:0.9; }
table { width:100%; border-collapse:collapse; background:var(--bg-secondary); border-radius:1rem; overflow:hidden; }
th { background:var(--bg-primary); padding:1rem 1.2rem; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; font-size:0.85rem; color:var(--text-secondary); font-weight:700; }
td { padding:1rem 1.2rem; border-bottom:1px solid var(--border-color); color:var(--text-primary); font-size:0.95rem; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; }
tr:last-child td { border-bottom:none; }
.badge-used { background:#fee2e2; color:#dc2626; padding:0.25rem 0.8rem; border-radius:2rem; font-size:0.8rem; font-weight:700; }
.badge-free { background:#d1fae5; color:#059669; padding:0.25rem 0.8rem; border-radius:2rem; font-size:0.8rem; font-weight:700; }
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; }
.modal-overlay.active { display:flex; }
.modal-content { background:var(--bg-secondary); border-radius:1.5rem; padding:2rem; width:520px; max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; }
.modal-content h3, .modal-content label { color:var(--text-primary) !important; }
.modal-content .form-control { background:var(--bg-primary); color:var(--text-primary); border-color:var(--border-color); }
.modal-content h3 { font-size:1.3rem; font-weight:800; margin-bottom:1.5rem; }
.form-group { margin-bottom:1.2rem; }
.form-group label { display:block; font-weight:700; margin-bottom:0.5rem; font-size:0.9rem; }
.form-control { width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border-color); border-radius:0.75rem; background:var(--bg-primary); color:var(--text-primary); font-size:1rem; font-family:inherit; box-sizing:border-box; }
.form-row { display:flex; gap:1rem; }
.form-row .form-group { flex:1; }
.btn-save { width:100%; padding:0.9rem; background:var(--accent-color); color:#1a1a1a; border:none; border-radius:0.8rem; font-weight:800; font-size:1rem; cursor:pointer; margin-top:0.5rem; }
.btn-delete { background:none; border:none; color:#ef4444; cursor:pointer; padding:0.4rem 0.8rem; border-radius:0.5rem; font-size:0.85rem; }
.btn-delete:hover { background:#fee2e2; }
.student-photo { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--accent-color); }
.student-photo-placeholder { width:40px; height:40px; border-radius:50%; background:var(--bg-primary); display:flex; align-items:center; justify-content:center; color:var(--text-secondary); font-size:1.1rem; }
.photo-upload-area { border:2px dashed var(--border-color); border-radius:1rem; padding:1.5rem; text-align:center; cursor:pointer; transition:border-color 0.3s; position:relative; }
.photo-upload-area:hover { border-color:var(--accent-color); }
.photo-upload-area input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; }
.photo-preview { width:80px; height:80px; border-radius:50%; object-fit:cover; margin:0 auto 0.5rem; display:block; border:2px solid var(--accent-color); }
.optional-label { font-weight:400; font-size:0.8rem; color:#999; margin-inline-start:0.3rem; }
</style>
@endpush

@section('content')
<div style="max-width:1100px; margin:2rem auto; padding:0 1rem;">
    <div class="page-header">
        <h2><i class="fa-solid fa-id-card" style="color:var(--accent-color); margin-inline-end:0.5rem;"></i> {{ __('messages.university_ids') }}</h2>
        <button class="add-btn" onclick="document.getElementById('addModal').classList.add('active')">
            <i class="fa-solid fa-plus"></i> {{ __('messages.add_university_id') }}
        </button>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('messages.photo') }}</th>
                <th>{{ __('messages.university_id') }}</th>
                <th>{{ __('messages.full_name') }}</th>
                <th>{{ __('messages.date_of_birth') }}</th>
                <th>{{ __('messages.phone') }}</th>
                <th>{{ __('messages.telegram') }}</th>
                <th>{{ __('messages.status') }}</th>
                <th>{{ __('messages.date_added') }}</th>
                <th style="text-align: center;">{{ __('messages.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ids as $uid)
            <tr>
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
                <td>{{ $uid->date_of_birth ?? '—' }}</td>
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
            <tr>
                <td colspan="9" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                    <i class="fa-solid fa-inbox" style="font-size:2rem; opacity:0.4; display:block; margin-bottom:0.75rem;"></i>
                    {{ __('messages.no_university_ids_yet') }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
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

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('messages.first_name') }} <span style="color:red;">*</span></label>
                    <input type="text" name="first_name" class="form-control" placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: أحمد' : 'e.g., Ahmad' }}" required>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.last_name') }} <span style="color:red;">*</span></label>
                    <input type="text" name="last_name" class="form-control" placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: محمود' : 'e.g., Mahmoud' }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('messages.date_of_birth') }} <span class="optional-label">{{ __('messages.optional') }}</span></label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>
                <div class="form-group">
                    <label>{{ __('messages.phone') }} <span class="optional-label">{{ __('messages.optional') }}</span></label>
                    <input type="text" name="phone" class="form-control" placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: 0912345678' : 'e.g., 0912345678' }}">
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('messages.telegram_chat_id') }} <span class="optional-label">{{ __('messages.optional') }}</span></label>
                <input type="text" name="telegram_chat_id" class="form-control" placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: 123456789' : 'e.g., 123456789' }}">
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

function openEditModal(uid) {
    const form = document.getElementById('editForm');
    form.action = `/affairs/university-ids/update/${uid.id}`;

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
</script>
@endpush
