@extends('layouts.teacher')
@section('title', 'الرسائل')

@push('styles')
<style>
    .messaging-layout {
        max-width: 1300px;
        margin: 1.5rem auto;
        padding: 0 1rem;
        display: flex;
        gap: 1.5rem;
        height: 80vh;
        min-height: 600px;
        position: relative;
    }

    /* Contacts Sidebar */
    .contacts-sidebar {
        flex: 0 0 320px;
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }
    .sidebar-header h3 {
        margin: 0 0 1rem 0;
        font-size: 1.4rem;
        color: var(--text-primary);
        font-weight: 800;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .btn-new-msg {
        background: var(--accent-color);
        color: var(--primary-dark);
        border: none;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .btn-new-msg:hover { transform: scale(1.1); }

    .search-chat {
        position: relative;
    }
    .search-chat input {
        width: 100%;
        padding: 0.7rem 2.2rem 0.7rem 1rem;
        border-radius: 2rem;
        border: 1px solid var(--border-color);
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: inherit;
    }
    .search-chat input:focus { outline: none; border-color: var(--accent-color); }
    .search-chat i {
        position: absolute;
        right: 0.8rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
    }

    .contacts-list {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 0;
    }
    .contacts-list::-webkit-scrollbar { width: 6px; }
    .contacts-list::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        cursor: pointer;
        transition: background 0.2s;
        border-right: 4px solid transparent;
    }
    .contact-item:hover { background: var(--bg-primary); }
    .contact-item.active {
        background: var(--bg-primary);
        border-right-color: var(--accent-color);
    }
    
    .contact-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-color), #f9d813);
        color: var(--primary-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        position: relative;
        flex-shrink: 0;
    }
    .unread-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #ef4444;
        color: white;
        font-size: 0.7rem;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        border: 2px solid var(--bg-secondary);
        font-weight: bold;
    }
    
    .contact-info { flex: 1; overflow: hidden; }
    .contact-name { font-weight: 800; color: var(--text-primary); margin-bottom: 0.2rem; display: flex; justify-content: space-between; }
    .contact-time { font-size: 0.75rem; color: var(--text-secondary); font-weight: normal; }
    .contact-last-msg { font-size: 0.85rem; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .contact-item.active .contact-last-msg { color: var(--text-primary); font-weight: 600; }

    /* Chat Area */
    .chat-area {
        flex: 1;
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        position: relative;
    }

    /* Chat Placeholder */
    .chat-placeholder {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: var(--bg-secondary);
        z-index: 10;
        color: var(--text-secondary);
    }
    .chat-placeholder i {
        font-size: 5rem;
        color: var(--accent-color);
        opacity: 0.5;
        margin-bottom: 1.5rem;
    }
    .chat-placeholder h3 {
        font-size: 1.5rem;
        color: var(--text-primary);
        font-weight: 800;
        margin-bottom: 0.5rem;
    }

    /* Active Chat Container (hidden by default) */
    .active-chat-container {
        display: none;
        flex-direction: column;
        height: 100%;
        width: 100%;
    }

    .chat-header {
        padding: 1.2rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 1rem;
        background: var(--bg-secondary);
    }
    
    .btn-back-chat {
        display: none;
        background: none;
        border: none;
        font-size: 1.2rem;
        color: var(--text-primary);
        cursor: pointer;
        padding: 0.5rem;
        margin-right: -0.5rem;
    }

    .chat-header .contact-avatar { width: 40px; height: 40px; font-size: 1.1rem; }
    .chat-header-info { flex: 1; }
    .chat-header-info h4 { margin: 0 0 0.2rem 0; font-size: 1.1rem; color: var(--text-primary); font-weight: 800; }
    .chat-header-info p { margin: 0; font-size: 0.85rem; color: #10b981; }

    .chat-messages {
        flex: 1;
        padding: 1.5rem;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        background: var(--bg-primary);
    }
    .chat-messages::-webkit-scrollbar { width: 6px; }
    .chat-messages::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }

    .msg-bubble {
        max-width: 70%;
        padding: 1rem 1.2rem;
        border-radius: 1rem;
        font-size: 0.95rem;
        line-height: 1.5;
        position: relative;
    }
    .msg-received {
        background: var(--bg-secondary);
        color: var(--text-primary);
        align-self: flex-start;
        border-bottom-right-radius: 0.2rem;
    }
    .msg-sent {
        background: var(--accent-color);
        color: var(--primary-dark);
        align-self: flex-end;
        border-bottom-left-radius: 0.2rem;
    }
    
    .msg-time {
        font-size: 0.7rem;
        opacity: 0.7;
        margin-top: 0.5rem;
        text-align: left;
    }
    .msg-sent .msg-time { color: var(--primary-dark); }
    .msg-received .msg-time { color: var(--text-secondary); text-align: right; }

    .chat-input-area {
        padding: 1.2rem 1.5rem;
        background: var(--bg-secondary);
        border-top: 1px solid var(--border-color);
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    .chat-input-area input {
        flex: 1;
        padding: 1rem 1.5rem;
        border-radius: 2rem;
        border: 1px solid var(--border-color);
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: inherit;
        font-size: 0.95rem;
    }
    .chat-input-area input:focus { outline: none; border-color: var(--accent-color); }
    
    .btn-send {
        background: var(--accent-color);
        color: var(--primary-dark);
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .btn-send:hover { transform: scale(1.05); }

    /* Modal for New Message */
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

    /* Mobile Responsive Logic */
    @media (max-width: 900px) {
        .messaging-layout {
            display: block; 
            height: calc(100vh - 120px);
        }
        
        .contacts-sidebar {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0; left: 0;
            z-index: 2;
        }

        .chat-area {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0; left: 0;
            z-index: 1;
        }

        .btn-back-chat {
            display: block; /* Show back button on mobile */
        }
        
        /* When chat is active on mobile */
        .messaging-layout.chat-open .contacts-sidebar {
            transform: translateX(100%); /* slide out sidebar */
            pointer-events: none;
        }
        .messaging-layout.chat-open .chat-area {
            z-index: 3;
        }
    }
</style>
@endpush

@section('content')
<div class="messaging-layout" id="messagingLayout">
    
    <!-- Sidebar -->
    <div class="contacts-sidebar">
        <div class="sidebar-header">
            <h3>المحادثات <button class="btn-new-msg" id="btnNewMsg" title="رسالة جديدة"><i class="fa-solid fa-pen-to-square"></i></button></h3>
            <div class="search-chat">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="contactSearch" placeholder="البحث في الرسائل..." onkeyup="filterContacts()">
            </div>
        </div>
        
        <div class="contacts-list" id="contactsList">
            @forelse($contacts as $contact)
                @php
                    // نجلب آخر رسالة بين المستخدم الحالي وجهة الاتصال
                    $lastMsg = \App\Models\Message::where(function($q) use ($contact) {
                        $q->where('sender_id', auth()->id())->where('receiver_id', $contact->user_id);
                    })->orWhere(function($q) use ($contact) {
                        $q->where('sender_id', $contact->user_id)->where('receiver_id', auth()->id());
                    })->latest()->first();

                    $unreadCount = \App\Models\Message::where('sender_id', $contact->user_id)
                        ->where('receiver_id', auth()->id())
                        ->where('is_read', false)->count();
                @endphp
                <div class="contact-item" onclick="switchChat(this, {{ $contact->user_id }}, '{{ $contact->full_name }}')" data-name="{{ strtolower($contact->full_name) }}">
                    <div class="contact-avatar">
                        <i class="fa-solid fa-user"></i>
                        @if($unreadCount > 0)
                            <div class="unread-badge">{{ $unreadCount }}</div>
                        @endif
                    </div>
                    <div class="contact-info">
                        <div class="contact-name">
                            {{ $contact->full_name }} 
                            <span class="contact-time">{{ $lastMsg ? $lastMsg->created_at->diffForHumans() : '' }}</span>
                        </div>
                        <div class="contact-last-msg">
                            {{ $lastMsg ? \Illuminate\Support\Str::limit($lastMsg->message, 40) : 'بدء محادثة جديدة' }}
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align:center; padding: 2rem; color: var(--text-secondary);">لا توجد محادثات حالياً</div>
            @endforelse
        </div>
    </div>

    <!-- Chat Area -->
    <div class="chat-area">
        <!-- Placeholder (Visible Initially) -->
        <div class="chat-placeholder" id="chatPlaceholder">
            <i class="fa-regular fa-comments"></i>
            <h3>اختر محادثة للبدء</h3>
            <p>انقر على أي جهة اتصال من القائمة لعرض المحادثة.</p>
        </div>

        <!-- Active Chat (Hidden Initially) -->
        <div class="active-chat-container" id="activeChatContainer">
            <div class="chat-header">
                <button class="btn-back-chat" onclick="closeChatMobile()"><i class="fa-solid fa-arrow-right"></i></button>
                <div class="contact-avatar"><i class="fa-solid fa-user"></i></div>
                <div class="chat-header-info">
                    <h4 id="activeChatName">اسم المستخدم</h4>
                    <p id="activeChatStatus">متصل</p>
                </div>
            </div>
            
            <div class="chat-messages" id="chatBox">
                <!-- Messages populated via AJAX -->
            </div>
            
            <div class="chat-input-area">
                <input type="hidden" id="currentChatUserId">
                <input type="text" id="msgInput" placeholder="اكتب رسالتك هنا..." onkeypress="handleEnter(event)">
                <button class="btn-send" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal-overlay" id="newMsgModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>رسالة جديدة</h3>
            <button class="close-modal" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('teacher.messages.send') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>إلى (المستلم)</label>
                    <select name="receiver_id" class="form-control" required>
                        <option value="">← اختر المستلم...</option>
                        @foreach($allUsers as $u)
                            <option value="{{ $u->user_id }}">{{ $u->full_name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>الرسالة</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="اكتب رسالتك هنا..." required></textarea>
                </div>
                <button type="submit" class="btn-save">إرسال الرسالة</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // New Message Modal
    const modal = document.getElementById('newMsgModal');
    document.getElementById('btnNewMsg').addEventListener('click', () => modal.classList.add('active'));
    function closeModal() { modal.classList.remove('active'); }
    modal.addEventListener('click', (e) => { if(e.target === modal) closeModal(); });

    // Filter contacts
    function filterContacts() {
        const val = document.getElementById('contactSearch').value.toLowerCase();
        document.querySelectorAll('.contact-item').forEach(item => {
            const name = item.getAttribute('data-name');
            item.style.display = name.includes(val) ? 'flex' : 'none';
        });
    }

    const messagingLayout = document.getElementById('messagingLayout');
    const chatPlaceholder = document.getElementById('chatPlaceholder');
    const activeChatContainer = document.getElementById('activeChatContainer');
    const chatBox = document.getElementById('chatBox');
    let currentUserId = {{ auth()->id() }};

    function switchChat(element, userId, name) {
        document.querySelectorAll('.contact-item').forEach(item => item.classList.remove('active'));
        element.classList.add('active');

        const badge = element.querySelector('.unread-badge');
        if (badge) badge.style.display = 'none';

        chatPlaceholder.style.display = 'none';
        activeChatContainer.style.display = 'flex';
        messagingLayout.classList.add('chat-open');

        document.getElementById('activeChatName').innerText = name;
        document.getElementById('currentChatUserId').value = userId;
        
        chatBox.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-secondary);">جاري التحميل... <i class="fa-solid fa-spinner fa-spin"></i></div>';

        // Fetch messages via AJAX
        fetch(`/teacher/messages/conversation/${userId}`)
            .then(res => res.json())
            .then(data => {
                chatBox.innerHTML = '';
                if(data.length === 0) {
                    chatBox.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-secondary);">لا توجد رسائل سابقة. ابدأ المحادثة الآن!</div>';
                    return;
                }
                data.forEach(msg => {
                    const time = new Date(msg.created_at).toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });
                    const isSent = msg.sender_id === currentUserId;
                    chatBox.innerHTML += `
                        <div class="msg-bubble ${isSent ? 'msg-sent' : 'msg-received'}">
                            ${msg.message}
                            <div class="msg-time">${time} ${isSent ? '<i class="fa-solid fa-check"></i>' : ''}</div>
                        </div>
                    `;
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            });
    }

    function closeChatMobile() {
        messagingLayout.classList.remove('chat-open');
        document.querySelectorAll('.contact-item').forEach(item => item.classList.remove('active'));
        chatPlaceholder.style.display = 'flex';
        activeChatContainer.style.display = 'none';
    }

    function sendMessage() {
        const input = document.getElementById('msgInput');
        const text = input.value.trim();
        const receiverId = document.getElementById('currentChatUserId').value;
        if(text === '' || !receiverId) return;

        // Add optimistic UI bubble
        const time = new Date().toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });
        chatBox.innerHTML += `
            <div class="msg-bubble msg-sent" style="opacity:0.7">
                ${text}
                <div class="msg-time">${time} <i class="fa-regular fa-clock"></i></div>
            </div>
        `;
        chatBox.scrollTop = chatBox.scrollHeight;
        input.value = '';

        // Post to server
        fetch(`{{ route('teacher.messages.send') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ receiver_id: receiverId, message: text })
        }).then(res => res.json())
          .then(data => {
              if(data.success) {
                  // Re-fetch conversation to sync exactly with DB
                  switchChat(document.querySelector('.contact-item.active'), receiverId, document.getElementById('activeChatName').innerText);
              }
          });
    }

    function handleEnter(e) {
        if (e.key === 'Enter') sendMessage();
    }
</script>
@endpush
