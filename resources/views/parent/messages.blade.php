@extends('layouts.parent')

@section('title', 'الرسائل')
@section('subtitle', 'التواصل المباشر مع إدارة الكلية ورؤساء الأقسام')

@section('content')
<!-- Tailwind standard Google icons & font -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

<div class="flex gap-6 h-[calc(100vh-12rem)] min-h-[500px] overflow-hidden rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-soft transition-colors" id="chat-app-container">
    
    <!-- ================= SIDEBAR (CONTACTS) ================= -->
    <div class="w-full md:w-80 flex flex-col border-l border-slate-200 dark:border-slate-800 h-full shrink-0" id="contacts-sidebar-pane">
        <!-- Search -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800">
            <div class="relative">
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <span class="material-symbols-outlined text-xl">search</span>
                </span>
                <input id="contact-search" oninput="filterContactsList()" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl py-3 pr-12 pl-4 text-xs font-semibold text-slate-700 dark:text-slate-200 placeholder:text-slate-400 outline-none" placeholder="البحث في جهات الاتصال..." type="text"/>
            </div>
        </div>

        <!-- Contacts Scrollable List -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/50" id="contacts-list-container">
            <!-- Loading Indicator -->
            <div id="contacts-loading" class="flex flex-col items-center justify-center py-12 gap-3 text-slate-400">
                <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent"></div>
                <span class="text-xs font-bold">جاري تحميل جهات الاتصال...</span>
            </div>
            
            <div id="contacts-wrapper" class="hidden"></div>
            
            <div id="contacts-empty" class="hidden flex flex-col items-center justify-center py-12 text-slate-400 text-center px-4">
                <span class="material-symbols-outlined text-4xl mb-2 text-slate-300">contact_support</span>
                <span class="text-xs font-bold">لا توجد جهات اتصال متاحة للمحادثة</span>
            </div>
        </div>
    </div>

    <!-- ================= CHAT ROOM WINDOW ================= -->
    <div class="flex-1 flex flex-col h-full bg-slate-50/50 dark:bg-slate-950/20 relative" id="chat-room-pane">
        
        <!-- Chat Placeholder (Visible when no active chat) -->
        <div id="chat-placeholder" class="absolute inset-0 flex flex-col items-center justify-center p-8 bg-slate-50 dark:bg-slate-900 z-10 text-center">
            <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-6 shadow-soft">
                <span class="material-symbols-outlined text-5xl">forum</span>
            </div>
            <h3 class="text-lg font-black text-slate-800 dark:text-white mb-2">مرحباً بك في نظام المحادثات</h3>
            <p class="text-xs text-slate-400 max-w-sm">اختر أحد جهات الاتصال المتاحة في القائمة الجانبية لبدء المحادثة الفورية مع إدارة الكلية أو رؤساء أقسام أبنائك.</p>
        </div>

        <!-- Active Chat Window Container -->
        <div id="active-chat-window" class="flex flex-col h-full hidden">
            
            <!-- Chat Header -->
            <div class="px-6 py-4 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Contact Avatar -->
                    <div class="relative">
                        <div id="active-contact-avatar-placeholder" class="w-10 h-10 rounded-full bg-primary/20 text-yellow-700 dark:text-yellow-400 flex items-center justify-center font-bold text-sm select-none">
                            A
                        </div>
                        <img id="active-contact-avatar-img" class="w-10 h-10 rounded-full object-cover hidden" src="" alt="Avatar">
                    </div>
                    <!-- Contact Name & Role Badge -->
                    <div class="flex flex-col">
                        <span id="active-contact-name" class="text-sm font-bold text-slate-800 dark:text-white">...</span>
                        <span id="active-contact-role" class="text-[10px] text-slate-400 font-semibold mt-0.5">...</span>
                    </div>
                </div>
            </div>

            <!-- Messages Feed -->
            <div id="messages-feed" class="flex-1 p-6 overflow-y-auto space-y-4 flex flex-col bg-slate-50/50 dark:bg-slate-950/20">
                <!-- Messages will load dynamically here -->
            </div>

            <!-- Chat Bottom Input Bar -->
            <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
                <form id="chat-send-form" onsubmit="event.preventDefault(); submitMessage();" class="flex items-center gap-3" enctype="multipart/form-data">
                    <input type="hidden" id="current-receiver-id" value="">
                    
                    <!-- Input Bar Elements -->
                    <div id="standard-input-elements" class="flex-1 flex items-center gap-3">
                        <!-- Custom File Input -->
                        <label class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 cursor-pointer transition-colors shrink-0" title="إرفاق ملف">
                            <span class="material-symbols-outlined text-xl">attach_file</span>
                            <input type="file" id="message-file" class="hidden" onchange="handleFileSelection(event)">
                        </label>

                        <!-- Message Text Input -->
                        <div class="flex-1 relative">
                            <input id="message-text" class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-full py-3 px-6 text-sm font-semibold text-slate-800 dark:text-slate-200 placeholder:text-slate-400 outline-none" placeholder="اكتب رسالتك..." type="text" autocomplete="off"/>
                        </div>
                    </div>

                    <!-- Send Button -->
                    <button type="submit" id="send-btn" class="w-12 h-12 rounded-full bg-[#FFCC00] hover:bg-[#E6B800] text-black flex items-center justify-center transition-all shadow-md shrink-0 active:scale-95" title="إرسال">
                        <span id="send-btn-icon" class="material-symbols-outlined rotate-180">send</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('js')
<!-- Load Pusher JS -->
<script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
<script>
    const currentUserId = {{ Auth::id() }};
    let activeContactId = null;
    let selectedFile = null;

    // Initialize Pusher Client
    const pusher = new Pusher('7ddc52d35c1e7beb4c83', {
        cluster: 'eu',
        encrypted: true
    });

    // Subscribe to Private Message Notifications
    const channel = pusher.subscribe('private-chat.' + currentUserId);
    channel.bind('App\\Events\\MessageSent', function(data) {
        const msg = data.message;
        if (activeContactId && (parseInt(msg.sender_id) === parseInt(activeContactId) || parseInt(msg.receiver_id) === parseInt(activeContactId))) {
            appendMessageBubble(msg);
            markActiveChatAsRead();
        } else {
            loadContactsList();
        }
    });

    // Load Allowed Contacts via AJAX
    function loadContactsList() {
        fetch('/chat/contacts')
            .then(res => res.json())
            .then(data => {
                document.getElementById('contacts-loading').classList.add('hidden');
                const wrapper = document.getElementById('contacts-wrapper');
                const empty = document.getElementById('contacts-empty');
                
                wrapper.innerHTML = '';
                
                if (data.status === 'success' && data.data.length > 0) {
                    empty.classList.add('hidden');
                    wrapper.classList.remove('hidden');

                    data.data.forEach(contact => {
                        const hasUnread = contact.unread > 0;
                        const unreadBadge = hasUnread ? `<span class="bg-red-500 text-white rounded-full px-2 py-0.5 text-[9px] font-black">${contact.unread}</span>` : '';
                        const avatarPlaceholder = contact.name.charAt(0);
                        
                        wrapper.innerHTML += `
                            <div onclick="selectContact(${contact.id}, '${contact.name}', '${contact.role}', '${contact.image || ''}')" 
                                 class="contact-row flex items-center gap-3 p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors ${activeContactId == contact.id ? 'bg-slate-100/50 dark:bg-slate-800/80' : ''}" 
                                 data-name="${contact.name.toLowerCase()}">
                                <div class="w-10 h-10 rounded-full bg-primary/10 text-yellow-800 dark:text-yellow-400 flex items-center justify-center font-bold text-sm shrink-0">
                                    ${avatarPlaceholder}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-800 dark:text-white truncate">${contact.name}</span>
                                        <span class="text-[9px] text-slate-400">${contact.time || ''}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1 truncate font-semibold">${contact.last_message || 'انقر لبدء المحادثة...'}</div>
                                </div>
                                ${unreadBadge}
                            </div>
                        `;
                    });
                } else {
                    wrapper.classList.add('hidden');
                    empty.classList.remove('hidden');
                }
            });
    }

    function filterContactsList() {
        const query = document.getElementById('contact-search').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.contact-row');
        rows.forEach(row => {
            const name = row.getAttribute('data-name');
            if (name.includes(query)) {
                row.classList.remove('hidden');
                row.classList.add('flex');
            } else {
                row.classList.remove('flex');
                row.classList.add('hidden');
            }
        });
    }

    function selectContact(id, name, role, avatar) {
        activeContactId = id;
        document.getElementById('chat-placeholder').classList.add('hidden');
        document.getElementById('active-chat-window').classList.remove('hidden');
        document.getElementById('active-contact-name').innerText = name;
        document.getElementById('active-contact-role').innerText = role;

        const avatarPlaceholder = document.getElementById('active-contact-avatar-placeholder');
        const avatarImg = document.getElementById('active-contact-avatar-img');

        if (avatar) {
            avatarImg.src = avatar;
            avatarImg.classList.remove('hidden');
            avatarPlaceholder.classList.add('hidden');
        } else {
            avatarImg.src = "";
            avatarImg.classList.add('hidden');
            avatarPlaceholder.innerText = name.charAt(0);
            avatarPlaceholder.classList.remove('hidden');
        }

        fetchMessages(id);
    }

    function fetchMessages(contactId) {
        const feed = document.getElementById('messages-feed');
        feed.innerHTML = '<div class="text-center text-xs text-slate-400 py-12">جاري تحميل الرسائل...</div>';
        
        fetch(`/chat/messages/${contactId}`)
            .then(res => res.json())
            .then(data => {
                feed.innerHTML = '';
                if (data.status === 'success' && data.data.length > 0) {
                    // Show messages in ascending order (older first)
                    data.data.slice().reverse().forEach(msg => appendMessageBubble(msg));
                    scrollMessagesToBottom();
                } else {
                    feed.innerHTML = '<div class="text-center text-xs text-slate-400 py-12">لا توجد رسائل سابقة. ابدأ المحادثة الآن!</div>';
                }
                markActiveChatAsRead();
            });
    }

    // Adapt layout themes (dark/light)
    function syncDarkMode() {
        const isDark = (document.documentElement && document.documentElement.classList.contains('dark')) || 
                       (document.documentElement && document.documentElement.getAttribute('data-theme') === 'dark') || 
                       localStorage.getItem('theme') === 'dark' ||
                       (document.body && document.body.classList.contains('dark'));
        
        const card = document.getElementById('chat-app-container');
        if (card) {
            if (isDark) {
                card.classList.add('dark');
            } else {
                card.classList.remove('dark');
            }
        }
    }

    function appendMessageBubble(msg) {
        const feed = document.getElementById('messages-feed');
        const isMe = parseInt(msg.sender_id) === currentUserId;
        
        // Remove empty state
        const empty = feed.querySelector('.text-center');
        if (empty) empty.remove();

        const msgHtml = `
            <div class="flex ${isMe ? 'justify-end' : 'justify-start'}">
                <div class="max-w-[70%] p-3 rounded-2xl ${isMe ? 'bg-[#FFCC00] text-black rounded-tl-none' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-tr-none border border-slate-200/50 dark:border-slate-800/80'} shadow-sm">
                    <p class="text-xs font-semibold leading-relaxed break-words">${msg.message || ''}</p>
                    ${msg.attachment ? `
                        <div class="mt-2 pt-2 border-t ${isMe ? 'border-black/10' : 'border-slate-200 dark:border-slate-700'}">
                            <a href="${msg.attachment}" target="_blank" class="flex items-center gap-1 text-[10px] font-bold underline ${isMe ? 'text-black/80' : 'text-primary'}">
                                <span class="material-symbols-outlined text-sm">download</span>
                                تحميل المرفق
                            </a>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        feed.insertAdjacentHTML('beforeend', msgHtml);
        scrollMessagesToBottom();
    }

    function scrollMessagesToBottom() {
        const feed = document.getElementById('messages-feed');
        feed.scrollTop = feed.scrollHeight;
    }

    function handleFileSelection(event) {
        selectedFile = event.target.files[0];
        if (selectedFile) {
            const textInput = document.getElementById('message-text');
            textInput.placeholder = `ملف محدد: ${selectedFile.name}`;
        }
    }

    function submitMessage() {
        const text = document.getElementById('message-text').value.trim();
        const fileInput = document.getElementById('message-file');
        
        if (!text && !selectedFile) return;

        const fd = new FormData();
        fd.append('receiver_id', activeContactId);
        fd.append('message', text || '[مرفق]');
        if (selectedFile) {
            fd.append('attachment', selectedFile);
        }

        fetch('{{ route("chat.send-message") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            appendMessageBubble(data.data);
            document.getElementById('message-text').value = '';
            document.getElementById('message-text').placeholder = 'اكتب رسالتك...';
            fileInput.value = '';
            selectedFile = null;
            loadContactsList();
        });
    }

    function markActiveChatAsRead() {
        fetch(`/chat/messages/${activeContactId}/mark-read`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(() => loadContactsList());
    }

    // Load list initially
    loadContactsList();
    syncDarkMode();
    
    // Theme sync listener
    const observer = new MutationObserver(() => syncDarkMode());
    if (document.documentElement) {
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });
    }
</script>
@endpush
@endsection
