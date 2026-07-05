@extends('layouts.hod')

@section('title', 'الرسائل')
@section('header-title', 'نظام الدردشة الموحد')
@section('header-subtitle', 'تواصل فوري مع الكادر الإداري والتعليمي')

@section('content')
<div class="flex gap-6 h-[calc(100vh-12rem)] min-h-[500px] overflow-hidden rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-soft transition-colors" id="chat-app-container">
    
    <!-- ================= SIDEBAR (CONTACTS) ================= -->
    <div class="w-full md:w-80 flex flex-col border-l border-slate-200 dark:border-slate-800 h-full shrink-0" id="contacts-sidebar-pane">
        <!-- Search and Filter -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 space-y-3">
            <div class="relative">
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <span class="material-symbols-outlined text-xl">search</span>
                </span>
                <input id="contact-search" oninput="filterContactsList()" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl py-3 pr-12 pl-4 text-xs font-semibold text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 transition-all outline-none" placeholder="البحث في جهات الاتصال..." type="text"/>
            </div>
        </div>

        <!-- Contacts Scrollable List -->
        <div class="flex-1 overflow-y-auto hide-scrollbar divide-y divide-slate-100 dark:divide-slate-800/50" id="contacts-list-container">
            <!-- Loading Indicator -->
            <div id="contacts-loading" class="flex flex-col items-center justify-center py-12 gap-3 text-slate-400">
                <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent"></div>
                <span class="text-xs font-bold">جاري تحميل جهات الاتصال...</span>
            </div>
            
            <!-- Dynamic Contacts will be rendered here -->
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
        <div id="chat-placeholder" class="absolute inset-0 flex flex-col items-center justify-center p-8 bg-slate-50 dark:bg-slate-900 z-10 text-center transition-colors duration-300">
            <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-6 shadow-soft">
                <span class="material-symbols-outlined text-5xl">forum</span>
            </div>
            <h3 class="text-lg font-black text-slate-800 dark:text-white mb-2">مرحباً بك في نظام الدردشة الموحد</h3>
            <p class="text-xs text-slate-400 max-w-sm leading-relaxed">اختر أحد جهات الاتصال المتاحة في القائمة الجانبية لبدء المحادثة الفورية مع إمكانية إرفاق الملفات وتسجيل الملاحظات الصوتية.</p>
        </div>

        <!-- Active Chat Window Container -->
        <div id="active-chat-window" class="flex flex-col h-full hidden">
            
            <!-- Chat Header -->
            <div class="px-6 py-4 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between transition-colors">
                <div class="flex items-center gap-3">
                    <!-- Back button on Mobile -->
                    <button onclick="showSidebarOnMobile()" class="md:hidden flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200">
                        <span class="material-symbols-outlined text-xl">arrow_forward</span>
                    </button>
                    <!-- Contact Avatar -->
                    <div class="relative">
                        <div id="active-contact-avatar-placeholder" class="w-10 h-10 rounded-full bg-primary/20 text-yellow-700 dark:text-yellow-400 flex items-center justify-center font-bold text-sm select-none">
                            A
                        </div>
                        <img id="active-contact-avatar-img" class="w-10 h-10 rounded-full object-cover hidden" src="" alt="Avatar">
                        <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white dark:border-slate-900 rounded-full"></div>
                    </div>
                    <!-- Contact Name & Role Badge -->
                    <div class="flex flex-col">
                        <span id="active-contact-name" class="text-sm font-bold text-slate-800 dark:text-white leading-tight">...</span>
                        <span id="active-contact-role" class="text-[10px] text-slate-400 font-semibold mt-0.5">...</span>
                    </div>
                </div>
                <!-- Search Bar -->
                <div class="hidden md:block relative">
                    <input type="text" id="message-search-input" onkeyup="searchActiveChatMessages()" placeholder="البحث في المحادثة..." class="bg-slate-100 dark:bg-slate-800 border-none rounded-full py-2 px-4 text-xs font-semibold focus:ring-2 focus:ring-primary/50 text-slate-800 dark:text-slate-200 outline-none w-48 transition-all">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <span class="material-symbols-outlined text-sm">search</span>
                    </span>
                </div>
            </div>

            <!-- Messages Feed -->
            <div id="messages-feed" class="flex-1 p-6 overflow-y-auto space-y-4 flex flex-col bg-slate-50/50 dark:bg-slate-950/20">
                <!-- Messages will load dynamically here -->
            </div>

            <!-- Chat Bottom Input Bar -->
            <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 transition-colors">
                <form id="chat-send-form" onsubmit="event.preventDefault(); submitMessage();" class="flex items-center gap-3" enctype="multipart/form-data">
                    <input type="hidden" id="current-receiver-id" value="">
                    
                    <!-- Attachment Preview Floating Container -->
                    <div id="attachment-preview-container" class="hidden absolute bottom-24 right-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-3 shadow-lg flex items-center gap-3 max-w-sm z-20">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary" id="preview-icon">
                            <span class="material-symbols-outlined">insert_drive_file</span>
                        </div>
                        <div class="flex flex-col max-w-[200px]">
                            <span id="preview-filename" class="text-xs font-bold text-slate-800 dark:text-white truncate">file.pdf</span>
                            <span id="preview-filesize" class="text-[10px] text-slate-400">0 KB</span>
                        </div>
                        <button type="button" onclick="clearSelectedAttachment()" class="text-slate-400 hover:text-red-500 transition-colors">
                            <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                    </div>

                    <!-- Voice Recording Overlay Interface -->
                    <div id="voice-recording-interface" class="hidden flex-1 flex items-center justify-between bg-slate-100 dark:bg-slate-800 rounded-full px-4 py-2 text-slate-700 dark:text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 bg-red-500 rounded-full animate-ping"></span>
                            <span class="text-xs font-black text-red-500">تسجيل...</span>
                            <span id="recording-timer" class="text-xs font-bold font-mono">00:00</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Cancel Recording -->
                            <button type="button" onclick="cancelAudioRecording()" class="flex items-center justify-center p-1.5 text-slate-400 hover:text-red-500 transition-colors" title="إلغاء">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                            <!-- Finish and Send -->
                            <button type="button" onclick="stopAudioRecording(false)" class="flex items-center justify-center w-8 h-8 rounded-full bg-green-500 text-white hover:bg-green-600 transition-colors" title="إرسال الملاحظة الصوتية">
                                <span class="material-symbols-outlined text-lg">check</span>
                            </button>
                        </div>
                    </div>

                    <!-- Input Bar Elements (Visible when not recording) -->
                    <div id="standard-input-elements" class="flex-1 flex items-center gap-3">
                        <!-- Custom File Input with Paperclip Icon -->
                        <label class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 cursor-pointer transition-colors shrink-0" title="إرفاق ملف">
                            <span class="material-symbols-outlined text-xl">attach_file</span>
                            <input type="file" id="message-file" class="hidden" onchange="handleFileSelection(event)">
                        </label>

                        <!-- Oval Input -->
                        <div class="flex-1 relative">
                            <input id="message-text" class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-full py-3 px-6 text-sm font-semibold text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 transition-all outline-none" placeholder="اكتب رسالتك..." type="text" autocomplete="off"/>
                        </div>

                        <!-- Microphone Icon for Voice Notes -->
                        <button type="button" onclick="startAudioRecording()" class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 transition-colors shrink-0" title="سجل ملاحظة صوتية">
                            <span class="material-symbols-outlined text-xl">mic</span>
                        </button>
                    </div>

                    <!-- Send Button (Yellow Circular Send button with arrow icon) -->
                    <button type="submit" id="send-btn" class="w-12 h-12 rounded-full bg-[#FFCC00] hover:bg-[#E6B800] text-black flex items-center justify-center transition-all shadow-md shrink-0 active:scale-95" title="إرسال">
                        <span id="send-btn-icon" class="material-symbols-outlined rotate-180">send</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================= CSS EXTRA POLISHING ================= -->
@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
tailwind.config = {
    darkMode: ['class', '[data-theme="dark"]'],
    theme: {
        extend: {
            colors: {
                "primary": "#f2f20d",
                "primary-dark": "#d9d905",
                "primary-content": "#1a1a00",
            },
            fontFamily: {
                "display": ["Cairo", "Lexend", "sans-serif"],
                "body": ["Cairo", "Lexend", "sans-serif"],
            },
            boxShadow: {
                "soft": "0 4px 20px -2px rgba(0,0,0,0.06)",
                "glow": "0 0 25px rgba(242,242,13,0.4)",
            }
        }
    }
}
</script>
<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    .chat-bubble-received {
        border-top-left-radius: 1.25rem;
        border-top-right-radius: 1.25rem;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 1.25rem;
    }
    .chat-bubble-sent {
        border-top-left-radius: 1.25rem;
        border-top-right-radius: 1.25rem;
        border-bottom-left-radius: 1.25rem;
        border-bottom-right-radius: 0;
    }
    
    @keyframes flash {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
    .animate-ping {
        animation: flash 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>
@endpush

<!-- ================= REAL-TIME PUSHER & SCRIPTS ================= -->
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.js"></script>

<script>
    let currentUserId = @json(auth()->id());
    let activeContactId = null;
    let selectedAttachmentFile = null;
    window.activeMessagesData = {};
    let editingMessageId = null;
    
    // Echo Connection Instance
    let echoInstance = null;

    // Media Recording variables
    let mediaRecorder = null;
    let audioChunks = [];
    let recordTimerInterval = null;
    let recordDurationSecs = 0;
    let isRecordingCancelled = false;

    // Run on window load
    document.addEventListener("DOMContentLoaded", function () {
        loadContacts();
        initializeEcho();
    });

    // Initialize Laravel Echo
    function initializeEcho() {
        if (typeof window.Echo === 'undefined') {
            console.error('Laravel Echo CDN failed to load properly.');
            return;
        }

        window.Pusher = Pusher;
        echoInstance = new window.Echo({
            broadcaster: 'pusher',
            key: '{{ env('PUSHER_APP_KEY', '7ddc52d35c1e7beb4c83') }}',
            cluster: '{{ env('PUSHER_APP_CLUSTER', 'eu') }}',
            forceTLS: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }
        });

        // Listen for new messages on our private channel
        echoInstance.private('chat.' + currentUserId)
            .listen('MessageSent', (e) => {
                console.log('Echo real-time event:', e);
                // Check if the incoming message is from the active contact
                if (activeContactId && parseInt(e.sender_id) === parseInt(activeContactId)) {
                    if (!document.getElementById('msg-' + e.id)) {
                        appendMessageBubble({
                            id: e.id,
                            sender_id: e.sender_id,
                            receiver_id: e.receiver_id,
                            message: e.message,
                            attachment: e.attachment,
                            created_at: e.created_at
                        });
                        scrollMessagesToBottom();
                        markActiveChatAsRead();
                    }
                } else {
                    // Trigger dynamic contacts list update to increment unread counter and update last message
                    loadContactsSilently();
                }
            })
            .listen('MessagesMarkedAsRead', (e) => {
                console.log('Read receipt event:', e);
                if (activeContactId && parseInt(e.reader_id) === parseInt(activeContactId)) {
                    // Update all single ticks (done) to double blue ticks (done_all) for our sent messages
                    document.querySelectorAll('#messages-feed .chat-bubble-sent').forEach(bubble => {
                        const tickSpan = bubble.parentElement.querySelector('.material-symbols-outlined');
                        if (tickSpan && tickSpan.innerText === 'done') {
                            tickSpan.innerText = 'done_all';
                            tickSpan.classList.remove('text-slate-400');
                            tickSpan.classList.add('text-blue-500');
                        }
                    });
                }
            });
    }

    // Dynamic Contacts List Loader
    function loadContacts() {
        const loadingDiv = document.getElementById('contacts-loading');
        const emptyDiv = document.getElementById('contacts-empty');
        const wrapperDiv = document.getElementById('contacts-wrapper');

        loadingDiv.classList.remove('hidden');
        emptyDiv.classList.add('hidden');
        wrapperDiv.classList.add('hidden');

        fetch("{{ route('chat.contacts') }}")
            .then(res => res.json())
            .then(data => {
                loadingDiv.classList.add('hidden');
                if (data.status === 'success' && data.data.length > 0) {
                    renderContactsList(data.data);
                    wrapperDiv.classList.remove('hidden');
                } else {
                    emptyDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                console.error("Contacts loading failed: ", err);
                loadingDiv.classList.add('hidden');
                emptyDiv.classList.remove('hidden');
            });
    }

    // Refresh contact list quietly to keep unread badges and messages synced
    function loadContactsSilently() {
        fetch("{{ route('chat.contacts') }}")
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    renderContactsList(data.data);
                    document.getElementById('contacts-wrapper').classList.remove('hidden');
                    document.getElementById('contacts-empty').classList.add('hidden');
                }
            });
    }

    // Render Contact Item Rows with Role Badges and excerpt
    function renderContactsList(contacts) {
        const wrapper = document.getElementById('contacts-wrapper');
        wrapper.innerHTML = '';

        contacts.forEach(contact => {
            // Pick Role badge color classes
            let badgeClass = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
            let roleAr = contact.role;
            if (contact.role === 'Administration') {
                badgeClass = 'bg-rose-500/10 text-rose-500';
                roleAr = 'الإدارة';
            } else if (contact.role === 'Teacher') {
                badgeClass = 'bg-blue-500/10 text-blue-500';
                roleAr = 'المدرب';
            } else if (contact.role === 'Student') {
                badgeClass = 'bg-emerald-500/10 text-emerald-500';
                roleAr = 'الطالب';
            } else if (contact.role === 'Parent') {
                badgeClass = 'bg-purple-500/10 text-purple-500';
                roleAr = 'الأهل';
            } else if (contact.role === 'Head of Department') {
                badgeClass = 'bg-amber-500/10 text-amber-500';
                roleAr = 'رئيس القسم';
            } else if (contact.role === 'Affairs Officer') {
                badgeClass = 'bg-cyan-500/10 text-cyan-500';
                roleAr = 'الشؤون';
            }

            const unreadBadge = contact.unread > 0 
                ? `<div class="bg-rose-500 text-white text-[9px] font-black w-5 h-5 rounded-full flex items-center justify-center animate-pulse">${contact.unread}</div>` 
                : '';

            const isActive = activeContactId && parseInt(contact.id) === parseInt(activeContactId) 
                ? 'bg-slate-100 dark:bg-slate-800/80 border-r-4 border-[#FFCC00]' 
                : '';

            const initials = contact.name.trim().charAt(0);
            
            // Format Last Message preview label
            let lastMsgText = contact.last_message || 'ابدأ محادثة جديدة';
            if (contact.last_message === '[Attachment]') {
                lastMsgText = '📁 ملف مرفق';
            } else if (contact.last_message === '[Voice Note]') {
                lastMsgText = '🎤 رسالة صوتية';
            }

            const contactHtml = `
                <div onclick="selectContact(${contact.id}, '${contact.name}', '${roleAr}', '${contact.image || ''}')" 
                     class="contact-row flex items-center gap-3 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/40 cursor-pointer transition-all ${isActive}"
                     data-name="${contact.name.toLowerCase()}">
                    
                    <!-- Initials / Avatar image -->
                    <div class="relative shrink-0 select-none">
                        ${contact.image 
                            ? `<img class="w-11 h-11 rounded-full object-cover" src="${contact.image}" alt="Avatar">`
                            : `<div class="w-11 h-11 rounded-full bg-primary/20 text-yellow-700 dark:text-yellow-400 flex items-center justify-center font-bold text-sm">${initials}</div>`
                        }
                        <div class="absolute bottom-0 right-0 w-3 h-3 bg-slate-300 border-2 border-white dark:border-slate-900 rounded-full status-indicator"></div>
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 dark:text-white truncate">${contact.name}</span>
                            <span class="text-[9px] text-slate-400">${contact.time || ''}</span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[10px] font-semibold truncate text-slate-400 dark:text-slate-500 max-w-[130px]">${lastMsgText}</span>
                            <span class="text-[8px] font-bold px-2 py-0.5 rounded-full ${badgeClass}">${roleAr}</span>
                        </div>
                    </div>
                    
                    <!-- Unread counter -->
                    <div class="shrink-0">
                        ${unreadBadge}
                    </div>
                </div>
            `;
            wrapper.innerHTML += contactHtml;
        });
    }

    // Filter Contacts by Search Box input
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

    // Switch conversation details and fetch old chat history
    function selectContact(contactId, name, roleLabel, avatarUrl) {
        activeContactId = contactId;

        // Toggle Sidebar visibility on mobile
        if (window.innerWidth < 768) {
            document.getElementById('contacts-sidebar-pane').classList.add('hidden');
        }

        document.getElementById('chat-placeholder').classList.add('hidden');
        document.getElementById('active-chat-window').classList.remove('hidden');
        document.getElementById('current-receiver-id').value = contactId;

        // Update header contact name
        document.getElementById('active-contact-name').innerText = name;
        document.getElementById('active-contact-role').innerText = roleLabel;

        // Update Header Avatar image
        const avatarPlaceholder = document.getElementById('active-contact-avatar-placeholder');
        const avatarImg = document.getElementById('active-contact-avatar-img');

        if (avatarUrl) {
            avatarImg.src = avatarUrl;
            avatarImg.classList.remove('hidden');
            avatarPlaceholder.classList.add('hidden');
        } else {
            avatarImg.src = "";
            avatarImg.classList.add('hidden');
            avatarPlaceholder.innerText = name.charAt(0);
            avatarPlaceholder.classList.remove('hidden');
        }

        // Highlight selected row in sidebar
        loadContactsSilently();

        // Fetch Messages from Server
        fetchMessagesFeed(contactId);
    }

    // Fetch conversation logs from server
    function fetchMessagesFeed(contactId) {
        const feed = document.getElementById('messages-feed');
        feed.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full gap-2 text-slate-400 py-12">
                <div class="animate-spin rounded-full h-6 w-6 border-2 border-primary border-t-transparent"></div>
                <span class="text-xs font-semibold">تحميل الرسائل...</span>
            </div>
        `;

        fetch(`/chat/messages/${contactId}`)
            .then(res => res.json())
            .then(data => {
                feed.innerHTML = '';
                if (data.status === 'success' && data.data.length > 0) {
                    data.data.slice().reverse().forEach(msg => {
                        appendMessageBubble(msg);
                    });
                    scrollMessagesToBottom();
                } else {
                    feed.innerHTML = `
                        <div class="flex flex-col items-center justify-center h-full text-slate-400 py-12 text-center">
                            <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">forum</span>
                            <span class="text-xs font-bold">لا توجد رسائل سابقة. ابدأ المحادثة الآن!</span>
                        </div>
                    `;
                }
                markActiveChatAsRead();
            })
            .catch(err => {
                console.error("Messages history load error: ", err);
                feed.innerHTML = `<div class="text-center text-xs text-red-500 py-6">خطأ في تحميل أرشيف المحادثة.</div>`;
            });
    }

    // Mark current active messages as read
    function markActiveChatAsRead() {
        if (!activeContactId) return;
        fetch(`/chat/messages/${activeContactId}/mark-read`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(() => {
            loadContactsSilently();
        });
    }

    // Append Message Bubble into Feed container
    function appendMessageBubble(msg) {
        const feed = document.getElementById('messages-feed');
        const isMe = parseInt(msg.sender_id) === parseInt(currentUserId);
        
        const emptyState = feed.querySelector('.text-center');
        if (emptyState && emptyState.innerText.includes('لا توجد رسائل')) {
            feed.innerHTML = '';
        }

        const alignClass = isMe ? 'justify-end' : 'justify-start';
        const bgBubble = isMe 
            ? 'bg-[#FFCC00] text-black chat-bubble-sent shadow-soft' 
            : 'bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 chat-bubble-received border border-slate-200/50 dark:border-slate-800/80 shadow-soft';

        const msgTime = new Date(msg.created_at).toLocaleTimeString('ar-EG', {
            hour: '2-digit',
            minute: '2-digit'
        });

        let fileType = '';
        let fileName = '';
        let url = msg.attachment || '';

        if (msg.fileObject) {
            fileName = msg.fileObject.name || 'voice.webm';
            fileType = msg.fileObject.type;
        } else if (msg.attachment) {
            fileName = url.split('/').pop();
            const ext = fileName.split('.').pop().toLowerCase().split('?')[0];
            if (['png', 'jpg', 'jpeg', 'webp', 'gif'].includes(ext)) {
                fileType = 'image/';
            } else if (['mp3', 'wav', 'm4a', 'ogg', 'aac'].includes(ext)) {
                fileType = 'audio/';
            } else if (['mp4', 'mov', 'webm', 'avi'].includes(ext)) {
                if (ext === 'webm' && (msg.message === '[Voice Note]' || url.includes('voice') || url.includes('audio') || msg.message.includes('صوتية'))) {
                    fileType = 'audio/';
                } else {
                    fileType = 'video/';
                }
            }
        }

        let mediaHtml = '';
        if (url) {
            if (fileType.startsWith('image/')) {
                mediaHtml = `
                    <div class="mt-2 rounded-xl overflow-hidden border border-black/10 dark:border-white/10 max-w-[260px]">
                        <img src="${url}" class="w-full object-cover cursor-pointer max-h-52" onclick="window.open('${url}', '_blank')" alt="Attachment">
                    </div>
                `;
            } else if (fileType.startsWith('audio/') || msg.message === '[Voice Note]') {
                mediaHtml = `
                    <div class="mt-2 max-w-[260px]">
                        <audio src="${url}" controls class="w-full max-h-12">
                            متصفحك لا يدعم مشغل الصوت.
                        </audio>
                    </div>
                `;
            } else if (fileType.startsWith('video/')) {
                mediaHtml = `
                    <div class="mt-2 rounded-xl overflow-hidden max-w-[260px]">
                        <video src="${url}" controls class="w-full max-h-52">
                        </video>
                    </div>
                `;
            } else {
                const displayName = fileName.substring(0, 20);
                mediaHtml = `
                    <a href="${url}" target="_blank" class="mt-2 flex items-center gap-2 p-2 rounded-xl bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 hover:bg-black/10 text-xs font-bold transition-all truncate max-w-[260px]">
                        <span class="material-symbols-outlined">description</span>
                        <span class="truncate">${displayName}</span>
                    </a>
                `;
            }
        }

        let textToShow = msg.message || '';
        if (url && (textToShow === '[Attachment]' || textToShow === '[Voice Note]')) {
            textToShow = '';
        }

        const isRead = parseInt(msg.is_read) === 1;
        const checkmarkIcon = isRead
            ? '<span class="material-symbols-outlined text-[10px] text-blue-500">done_all</span>'
            : '<span class="material-symbols-outlined text-[10px] text-slate-400">done</span>';

        const bubbleId = msg.id ? `id="msg-${msg.id}"` : '';
        const pendingClass = msg.isPending ? 'opacity-60 message-pending' : '';

        window.activeMessagesData[msg.id] = msg;

        let optionsHtml = '';
        if (isMe && !msg.isPending) {
            optionsHtml = `
                <div class="relative group/options flex flex-col justify-center px-2">
                    <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 opacity-0 group-hover:opacity-100 transition-opacity focus:opacity-100" onclick="toggleMsgOptions('${msg.id}')">
                        <span class="material-symbols-outlined text-sm">more_vert</span>
                    </button>
                    <div id="msg-options-${msg.id}" class="hidden absolute left-0 bottom-8 mb-1 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 py-1 text-xs min-w-[80px] z-[99] flex flex-col">
                        <button type="button" onclick="editMessageInit('${msg.id}')" class="px-3 py-1.5 text-right hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 w-full">تعديل</button>
                        <button type="button" onclick="deleteMessage('${msg.id}')" class="px-3 py-1.5 text-right hover:bg-red-50 dark:hover:bg-red-900/30 text-red-500 w-full">حذف</button>
                    </div>
                </div>
            `;
        }

        const bubbleHtml = `
            <div ${bubbleId} class="group flex ${alignClass} w-full ${pendingClass}">
                ${isMe ? optionsHtml : ''}
                <div class="flex flex-col max-w-[75%]">
                    <div class="px-4 py-3 text-sm leading-relaxed ${bgBubble}">
                        ${textToShow ? `<p class="whitespace-pre-line font-medium">${textToShow}</p>` : ''}
                        ${mediaHtml}
                    </div>
                    <span class="text-[9px] text-slate-400 mt-1 px-1 font-semibold flex items-center gap-1 ${isMe ? 'self-end' : 'self-start'}">
                        ${msgTime}
                        ${isMe ? checkmarkIcon : ''}
                    </span>
                </div>
                ${!isMe ? optionsHtml : ''}
            </div>
        `;

        feed.innerHTML += bubbleHtml;
    }

    function scrollMessagesToBottom() {
        const feed = document.getElementById('messages-feed');
        feed.scrollTop = feed.scrollHeight;
    }

    function showSidebarOnMobile() {
        document.getElementById('contacts-sidebar-pane').classList.remove('hidden');
    }

    function handleFileSelection(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (file.size > 51200 * 1024) {
            alert('حجم الملف كبير جداً! الحد الأقصى المسموح به هو 50 ميجابايت.');
            return;
        }

        selectedAttachmentFile = file;

        document.getElementById('preview-filename').innerText = file.name;
        document.getElementById('preview-filesize').innerText = (file.size / 1024).toFixed(1) + ' KB';
        document.getElementById('attachment-preview-container').classList.remove('hidden');
    }

    function clearSelectedAttachment() {
        selectedAttachmentFile = null;
        document.getElementById('message-file').value = '';
        document.getElementById('attachment-preview-container').classList.add('hidden');
    }

    function submitMessage() {
        const input = document.getElementById('message-text');
        const text = input.value.trim();
        
        if (!activeContactId) return;
        if (text === '' && !selectedAttachmentFile) return;

        if (editingMessageId) {
            fetch('/chat/messages/' + editingMessageId + '/edit', {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: text })
            }).then(res => res.json()).then(data => {
                if (data.status === 'success') {
                    if (window.activeMessagesData[editingMessageId]) {
                        window.activeMessagesData[editingMessageId].message = text;
                    }
                    const bubble = document.getElementById('msg-' + editingMessageId);
                    if(bubble) {
                        const p = bubble.querySelector('p');
                        if(p) p.innerText = text;
                    }
                    cancelEditMode();
                } else {
                    alert('فشل التعديل: ' + data.message);
                }
            }).catch(err => {
                alert('خطأ في الاتصال.');
            });
            return;
        }

        const fd = new FormData();
        fd.append('sender_id', currentUserId);
        fd.append('receiver_id', activeContactId);
        fd.append('message', selectedAttachmentFile && text === '' ? '[Attachment]' : text);

        if (selectedAttachmentFile) {
            fd.append('attachment', selectedAttachmentFile);
        }

        const tempTime = new Date().toISOString();
        const tempId = 'temp_' + Date.now();
        appendMessageBubble({
            id: tempId,
            sender_id: currentUserId,
            receiver_id: activeContactId,
            message: selectedAttachmentFile && text === '' ? 'جاري رفع الملف...' : text,
            attachment: selectedAttachmentFile ? URL.createObjectURL(selectedAttachmentFile) : null,
            fileObject: selectedAttachmentFile,
            created_at: tempTime,
            isPending: true
        });
        scrollMessagesToBottom();

        input.value = '';
        clearSelectedAttachment();

        fetch("{{ route('chat.send-message') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: fd
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Server error: ' + res.statusText);
            }
            return res.json();
        })
        .then(data => {
            const pendingBubble = document.getElementById(`msg-${tempId}`);
            if (pendingBubble) {
                pendingBubble.remove();
            }
            if (data.status === 'success') {
                if (!document.getElementById('msg-' + data.data.id)) {
                    appendMessageBubble(data.data);
                    scrollMessagesToBottom();
                }
                loadContactsSilently();
            } else {
                alert('فشل في إرسال الرسالة: ' + (data.error || 'خطأ غير معروف'));
            }
        })
        .catch(err => {
            console.error('Send error: ', err);
            const pendingBubble = document.getElementById(`msg-${tempId}`);
            if (pendingBubble) {
                pendingBubble.remove();
            }
            alert('فشل إرسال الرسالة. يرجى التحقق من اتصالك بالشبكة.');
        });
    }

    async function startAudioRecording() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('تسجيل الصوت غير مدعوم في هذا المتصفح.');
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            
            audioChunks = [];
            isRecordingCancelled = false;
            mediaRecorder = new MediaRecorder(stream);

            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    audioChunks.push(event.data);
                }
            };

            mediaRecorder.onstop = () => {
                stream.getTracks().forEach(track => track.stop());

                if (isRecordingCancelled) {
                    clearRecordingTimer();
                    return;
                }

                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                uploadVoiceBlob(audioBlob);
            };

            document.getElementById('standard-input-elements').classList.add('hidden');
            document.getElementById('voice-recording-interface').classList.remove('hidden');

            recordDurationSecs = 0;
            document.getElementById('recording-timer').innerText = "00:00";
            
            recordTimerInterval = setInterval(() => {
                recordDurationSecs++;
                const mins = Math.floor(recordDurationSecs / 60).toString().padLeft(2, '0');
                const secs = (recordDurationSecs % 60).toString().padLeft(2, '0');
                document.getElementById('recording-timer').innerText = `${mins}:${secs}`;
            }, 1000);

            mediaRecorder.start();

        } catch (err) {
            console.error('Microphone access denied: ', err);
            alert('لا يمكن الوصول للميكروفون. يرجى تفعيل صلاحية استخدام الميكروفون في متصفحك.');
        }
    }

    function cancelAudioRecording() {
        if (!mediaRecorder) return;
        isRecordingCancelled = true;
        mediaRecorder.stop();
        clearRecordingTimer();
        resetInputBarUI();
    }

    function stopAudioRecording(cancelled = false) {
        if (!mediaRecorder) return;
        isRecordingCancelled = cancelled;
        mediaRecorder.stop();
        clearRecordingTimer();
        resetInputBarUI();
    }

    function clearRecordingTimer() {
        if (recordTimerInterval) {
            clearInterval(recordTimerInterval);
            recordTimerInterval = null;
        }
    }

    // --- New Advanced Chat Features ---
    function toggleMsgOptions(id) {
        const el = document.getElementById('msg-options-' + id);
        if (el) {
            document.querySelectorAll('[id^="msg-options-"]').forEach(opts => {
                if(opts.id !== 'msg-options-' + id) opts.classList.add('hidden');
            });
            el.classList.toggle('hidden');
        }
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.group\\/options')) {
            document.querySelectorAll('[id^="msg-options-"]').forEach(opts => opts.classList.add('hidden'));
        }
    });

    function editMessageInit(id) {
        document.getElementById('msg-options-' + id).classList.add('hidden');
        const msg = window.activeMessagesData[id];
        if (!msg || msg.attachment || msg.message === '[Voice Note]') {
            alert('لا يمكن تعديل المرفقات أو الرسائل الصوتية.');
            return;
        }
        editingMessageId = id;
        document.getElementById('message-text').value = msg.message;
        document.getElementById('message-text').focus();
        
        document.getElementById('send-btn-icon').innerText = 'update';
        document.getElementById('send-btn').classList.replace('bg-[#FFCC00]', 'bg-blue-500');
        document.getElementById('send-btn').classList.replace('text-black', 'text-white');
    }

    function cancelEditMode() {
        editingMessageId = null;
        document.getElementById('message-text').value = '';
        document.getElementById('send-btn-icon').innerText = 'send';
        document.getElementById('send-btn').classList.replace('bg-blue-500', 'bg-[#FFCC00]');
        document.getElementById('send-btn').classList.replace('text-white', 'text-black');
    }

    function deleteMessage(id) {
        document.getElementById('msg-options-' + id).classList.add('hidden');
        if(!confirm('هل أنت متأكد من حذف هذه الرسالة؟')) return;
        
        fetch('/chat/messages/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                const bubble = document.getElementById('msg-' + id);
                if (bubble) bubble.remove();
                delete window.activeMessagesData[id];
            } else {
                alert('فشل الحذف: ' + data.message);
            }
        });
    }

    function searchActiveChatMessages() {
        const query = document.getElementById('message-search-input').value.trim();
        if (query === '') {
            fetchMessagesFeed(activeContactId);
            return;
        }
        
        fetch(`/chat/messages/${activeContactId}/search?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                const feed = document.getElementById('messages-feed');
                feed.innerHTML = '';
                window.activeMessagesData = {};
                if (data.status === 'success' && data.data.length > 0) {
                    data.data.slice().reverse().forEach(msg => appendMessageBubble(msg));
                    scrollMessagesToBottom();
                } else {
                    feed.innerHTML = `<div class="text-center text-xs text-slate-400 py-6">لا توجد نتائج مطابقة لـ "${query}"</div>`;
                }
            });
    }

    function resetInputBarUI() {
        document.getElementById('standard-input-elements').classList.remove('hidden');
        document.getElementById('voice-recording-interface').classList.add('hidden');
    }

    function uploadVoiceBlob(audioBlob) {
        if (!activeContactId) return;

        const fd = new FormData();
        fd.append('sender_id', currentUserId);
        fd.append('receiver_id', activeContactId);
        fd.append('message', '[Voice Note]');
        fd.append('attachment', audioBlob, `voice_note_${Date.now()}.webm`);

        const tempTime = new Date().toISOString();
        const tempId = 'temp_' + Date.now();
        appendMessageBubble({
            id: tempId,
            sender_id: currentUserId,
            receiver_id: activeContactId,
            message: 'جاري رفع الملاحظة الصوتية...',
            attachment: URL.createObjectURL(audioBlob),
            fileObject: audioBlob,
            created_at: tempTime,
            isPending: true
        });
        scrollMessagesToBottom();

        fetch("{{ route('chat.send-message') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: fd
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Server error: ' + res.statusText);
            }
            return res.json();
        })
        .then(data => {
            const pendingBubble = document.getElementById(`msg-${tempId}`);
            if (pendingBubble) {
                pendingBubble.remove();
            }
            if (data.status === 'success') {
                if (!document.getElementById('msg-' + data.data.id)) {
                    appendMessageBubble(data.data);
                    scrollMessagesToBottom();
                }
                loadContactsSilently();
            } else {
                alert('فشل إرسال الملاحظة الصوتية: ' + (data.error || 'خطأ غير معروف'));
            }
        })
        .catch(err => {
            console.error('Audio upload error: ', err);
            const pendingBubble = document.getElementById(`msg-${tempId}`);
            if (pendingBubble) {
                pendingBubble.remove();
            }
            alert('فشل في رفع الملاحظة الصوتية.');
        });
    }

    String.prototype.padLeft = function (length, character) {
        return this.length >= length ? this : (new Array(length - this.length + 1).join(character) + this);
    };

    // Dark Mode Synchronization
    function syncDarkMode() {
        const isDark = (document.documentElement && document.documentElement.classList.contains('dark')) || 
                       (document.documentElement && document.documentElement.getAttribute('data-theme') === 'dark') || 
                       localStorage.getItem('theme') === 'dark' ||
                       (document.body && document.body.classList.contains('dark'));
        
        const body = document.body;
        const mainContent = document.querySelector('.main-content');
        const header = document.querySelector('.header');
        const pageTitle = document.querySelector('.page-title');

        if (isDark) {
            document.documentElement.classList.add('dark');
            document.documentElement.setAttribute('data-theme', 'dark');
            if (body) body.classList.add('bg-slate-900', 'text-white');
            if (mainContent) mainContent.classList.add('bg-slate-900');
            if (header) header.classList.add('bg-slate-900', 'border-slate-800');
            if (pageTitle) pageTitle.classList.add('text-white');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.setAttribute('data-theme', 'light');
            if (body) body.classList.remove('bg-slate-900', 'text-white');
            if (mainContent) mainContent.classList.remove('bg-slate-900');
            if (header) header.classList.remove('bg-slate-900', 'border-slate-800');
            if (pageTitle) pageTitle.classList.remove('text-white');
        }
    }

    // Run immediately on load
    syncDarkMode();

    // Observe changes to the html/body tags to react to the layout's theme toggle
    const themeObserver = new MutationObserver(() => {
        syncDarkMode();
    });
    if (document.documentElement) {
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });
    }
    if (document.body) {
        themeObserver.observe(document.body, { attributes: true, attributeFilter: ['class', 'data-theme'] });
    }
</script>
@endpush
@endsection
