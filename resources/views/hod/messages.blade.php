@extends('layouts.hod')

@section('title', 'الرسائل')

@push('styles')
<style>
    .search-box {
        position: relative;
        margin-bottom: 2rem;
    }
    
    .search-input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem; /* padding-left for icon in RTL */
        border-radius: 2rem;
        border: 1px solid var(--border-color);
        background-color: var(--bg-secondary);
        color: var(--text-primary);
        font-family: inherit;
        font-size: 1rem;
        outline: none;
        box-shadow: var(--shadow);
    }
    
    .search-icon {
        position: absolute;
        left: 1.5rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .section-title {
        color: var(--text-secondary);
        font-weight: 700;
        font-size: 1rem;
    }
    
    .new-msg-link {
        color: #ca8a04;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .message-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: var(--shadow);
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .message-card:hover {
        transform: translateY(-2px);
    }
    
    .avatar-container {
        position: relative;
    }
    
    .msg-avatar {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .avatar-initial {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .bg-orange { background-color: #ffedd5; color: #c2410c; }
    .bg-pink { background-color: #fce7f3; color: #be185d; }
    
    .online-indicator {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        background-color: #22c55e;
        border: 2px solid var(--bg-secondary);
        border-radius: 50%;
    }
    
    .msg-content {
        flex: 1;
    }
    
    .msg-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.25rem;
    }
    
    .msg-name {
        font-size: 1.1rem;
        font-weight: 700;
    }
    
    .msg-time {
        color: var(--text-secondary);
        font-size: 0.85rem;
    }
    
    .msg-time.highlight {
        color: #ca8a04;
        font-weight: 600;
    }
    
    .msg-subject {
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 0.25rem;
        color: var(--text-primary);
    }
    
    .msg-preview {
        color: var(--text-secondary);
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 90%;
    }

    /* Modal styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .modal-overlay.active {
        opacity: 1;
        pointer-events: auto;
    }
    .modal-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 2.5rem;
        width: 90%;
        max-width: 500px;
        box-shadow: var(--shadow);
        transform: translateY(20px);
        transition: transform 0.3s ease;
    }
    .modal-overlay.active .modal-card {
        transform: translateY(0);
    }
</style>
@endpush

@section('content')

    <div class="search-box">
        <input type="text" id="msg-search-input" onkeyup="filterMessages()" class="search-input" placeholder="ابحث عن رسالة، مرسل أو مستقبل...">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
    </div>

    <div class="section-header">
        <span class="section-title">المحادثات والرسائل الأخيرة</span>
        <button onclick="openMessageModal()" class="new-msg-link" style="background: transparent; border: none; cursor: pointer; font-family: inherit; font-size: 0.95rem;">
            <i class="fa-solid fa-paper-plane" style="margin-left: 0.25rem;"></i> إنشاء رسالة جديدة
        </button>
    </div>

    <div id="messages-list">
        @forelse($messages as $message)
        <!-- Message Card -->
        <div class="message-card" data-content="{{ strtolower($message->sender_name . ' ' . $message->receiver_name . ' ' . $message->message) }}">
            <div class="avatar-container">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($message->sender_name ?? 'م') }}&background=random" class="msg-avatar" alt="Avatar">
            </div>
            <div class="msg-content">
                <div class="msg-header">
                    <div>
                        <span class="msg-name" style="color: var(--accent-color);">من: {{ $message->sender_name ?? 'مستخدم غير معروف' }}</span>
                        <span style="color: var(--text-secondary); margin: 0 0.5rem;">←</span>
                        <span class="msg-name" style="font-weight: 500; font-size: 0.95rem;">إلى: {{ $message->receiver_name ?? 'الكل' }}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="msg-time {{ \Carbon\Carbon::parse($message->created_at)->isToday() ? 'highlight' : '' }}">
                            {{ \Carbon\Carbon::parse($message->created_at)->diffForHumans() }}
                        </span>
                        
                        <form action="{{ route('hod.messages.delete', $message->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الرسالة؟')" style="margin: 0; padding: 0;">
                            @csrf
                            <button type="submit" style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-size: 1rem;">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="msg-preview" style="white-space: normal; overflow: visible; max-width: 100%; margin-top: 0.5rem; color: var(--text-primary);">
                    {{ $message->message }}
                </div>
            </div>
        </div>
        @empty
        <div class="message-card" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
            لا توجد رسائل حالياً.
        </div>
        @endforelse
    </div>

    <!-- Add Message Modal -->
    <div id="message-modal" class="modal-overlay">
        <div class="modal-card">
            <h4 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; text-align: center;">إنشاء وإرسال رسالة جديدة</h4>
            <form action="{{ route('hod.messages.store') }}" method="POST">
                @csrf
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">المستقبل (إلى)</label>
                    <select name="receiver_id" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary); font-family: inherit;">
                        @foreach($users as $user)
                            <option value="{{ $user->user_id }}">
                                {{ $user->full_name }} ({{ $user->role_name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--text-secondary);">محتوى الرسالة</label>
                    <textarea name="message" rows="4" required placeholder="اكتب نص الرسالة هنا..." style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary); font-family: inherit; resize: none;"></textarea>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn" style="background-color: var(--accent-color); color: #1a1a1a; flex: 1; padding: 0.75rem; border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer; font-size: 1rem;">إرسال الرسالة</button>
                    <button type="button" onclick="closeMessageModal()" class="btn" style="background-color: transparent; border: 1px solid var(--border-color); color: var(--text-primary); flex: 1; padding: 0.75rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-size: 1rem;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function openMessageModal() {
        document.getElementById('message-modal').classList.add('active');
    }

    function closeMessageModal() {
        document.getElementById('message-modal').classList.remove('active');
    }

    function filterMessages() {
        const query = document.getElementById('msg-search-input').value.toLowerCase();
        const cards = document.querySelectorAll('.message-card');
        
        cards.forEach(card => {
            const content = card.getAttribute('data-content');
            if (content && content.includes(query)) {
                card.style.display = 'flex';
            } else if (card.style.textAlign === 'center') {
                // Keep the "no messages" container
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>
@endpush
