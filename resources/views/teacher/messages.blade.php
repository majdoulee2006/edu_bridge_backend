@extends('layouts.teacher')
@section('title', 'الرسائل')

@push('styles')
<style>
    .msg-card { background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.25rem 1.5rem; box-shadow: var(--shadow); margin-bottom: 0.75rem; cursor: pointer; transition: transform 0.15s; }
    .msg-card:hover { transform: translateX(-3px); }
    .avatar { width: 46px; height: 46px; border-radius: 50%; background: var(--accent-color); color: #1a1a1a; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-card { background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; width: 100%; max-width: 520px; }
    .form-input { width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; }
    .form-input:focus { outline: none; border-color: var(--accent-color); }
    .section-label { font-size: 0.8rem; font-weight: 700; color: var(--accent-color); margin: 1.25rem 0 0.5rem; }
</style>
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 0.5rem; background: var(--bg-secondary); border-radius: 0.75rem; padding: 0.35rem;">
            <button class="tab-btn-msg active" onclick="switchMsgTab('inbox', this)" style="padding: 0.4rem 1rem; border-radius: 0.5rem; border: none; background: var(--accent-color); color: #1a1a1a; font-weight: 700; cursor: pointer; font-family: inherit;">الوارد</button>
            <button class="tab-btn-msg" onclick="switchMsgTab('sent', this)" style="padding: 0.4rem 1rem; border-radius: 0.5rem; border: none; background: transparent; color: var(--text-secondary); font-weight: 600; cursor: pointer; font-family: inherit;">المُرسَل</button>
        </div>
        <button onclick="document.getElementById('send-modal').classList.add('active')" style="background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; padding: 0.6rem 1.25rem; font-weight: 700; cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-plus"></i> رسالة جديدة
        </button>
    </div>

    <!-- Inbox -->
    <div id="tab-inbox">
        @forelse($messages as $m)
            <div class="msg-card">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="avatar">{{ mb_substr($m->sender_name, 0, 1) }}</div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700;">{{ $m->sender_name }}</span>
                            <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ \Carbon\Carbon::parse($m->created_at)->diffForHumans() }}</span>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.88rem; margin-top: 0.2rem;">{{ Str::limit($m->content, 80) }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
                <i class="fa-solid fa-inbox" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; color: var(--accent-color);"></i>
                لا توجد رسائل واردة
            </div>
        @endforelse
    </div>

    <!-- Sent -->
    <div id="tab-sent" style="display: none;">
        @forelse($sent as $m)
            <div class="msg-card">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="avatar" style="background: var(--bg-primary); border: 2px solid var(--accent-color); color: var(--text-primary);">
                        {{ mb_substr($m->receiver_name, 0, 1) }}
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700;">إلى: {{ $m->receiver_name }}</span>
                            <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ \Carbon\Carbon::parse($m->created_at)->diffForHumans() }}</span>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.88rem; margin-top: 0.2rem;">{{ Str::limit($m->content, 80) }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
                لا توجد رسائل مُرسَلة
            </div>
        @endforelse
    </div>

    <!-- Send Message Modal -->
    <div id="send-modal" class="modal-overlay">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="font-weight: 800;">رسالة جديدة</h3>
                <button onclick="document.getElementById('send-modal').classList.remove('active')" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('teacher.messages.send') }}" method="POST">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">إرسال إلى</label>
                    <select name="receiver_id" class="form-input" required>
                        <option value="">← اختر المستلم</option>
                        @foreach($users as $u)
                            <option value="{{ $u->user_id }}">{{ $u->full_name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">نص الرسالة</label>
                    <textarea name="content" class="form-input" rows="4" placeholder="اكتب رسالتك هنا..." required style="resize: vertical;"></textarea>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="flex: 1; padding: 0.85rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-family: inherit; font-size: 1rem;">إرسال</button>
                    <button type="button" onclick="document.getElementById('send-modal').classList.remove('active')" style="flex: 1; padding: 0.85rem; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 1rem;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function switchMsgTab(tab, btn) {
    document.querySelectorAll('.tab-btn-msg').forEach(b => {
        b.style.background = 'transparent';
        b.style.color = 'var(--text-secondary)';
    });
    btn.style.background = 'var(--accent-color)';
    btn.style.color = '#1a1a1a';
    document.getElementById('tab-inbox').style.display = tab === 'inbox' ? 'block' : 'none';
    document.getElementById('tab-sent').style.display = tab === 'sent' ? 'block' : 'none';
}
</script>
@endpush
