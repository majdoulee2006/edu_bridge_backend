@extends('layouts.parent')

@section('title', 'الرسائل')
@section('subtitle', 'التواصل المباشر مع إدارة الكلية ورؤساء الأقسام والمشرفين')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<div class="flex gap-0 md:gap-6 h-[calc(100vh-10rem)] min-h-[500px] overflow-hidden rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-soft transition-colors" id="chat-app-container">
    
    <!-- ================= SIDEBAR (CONTACTS) ================= -->
    <div class="w-full md:w-80 flex flex-col border-l border-slate-200 dark:border-slate-800 h-full shrink-0 md:flex" id="contacts-sidebar-pane">
        <!-- Search and Filter -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 space-y-3">
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <span class="material-symbols-outlined text-xl">search</span>
                    </span>
                    <input id="contact-search" oninput="filterContactsList()" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl py-3 pr-12 pl-4 text-xs font-semibold text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 transition-all outline-none" placeholder="البحث في جهات الاتصال..." type="text"/>
                </div>
                <button type="button" onclick="openNewChatModal()" class="w-12 h-12 rounded-2xl bg-[#FFCC00] hover:bg-[#E6B800] text-black flex items-center justify-center transition-all shrink-0 shadow-sm" title="محادثة جديدة">
                    <span class="material-symbols-outlined font-bold">add_comment</span>
                </button>
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
    <div class="flex-1 flex flex-col h-full bg-slate-50/50 dark:bg-slate-950/20 relative hidden md:flex" id="chat-room-pane">
        
        <!-- Chat Placeholder (Visible when no active chat) -->
        <div id="chat-placeholder" class="absolute inset-0 flex flex-col items-center justify-center p-8 bg-slate-50 dark:bg-slate-900 z-10 text-center transition-colors duration-300">
            <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-6 shadow-soft">
                <span class="material-symbols-outlined text-5xl">forum</span>
            </div>
            <h3 class="text-lg font-black text-slate-800 dark:text-white mb-2">مرحباً بك في نظام الدردشة الموحد</h3>
            <p class="text-xs text-slate-400 max-w-sm leading-relaxed">اختر أحد جهات الاتصال المتاحة لبدء المحادثة الفورية مع إمكانية إرفاق الملفات وتسجيل الملاحظات الصوتية مع إدارة الكلية ورؤساء الأقسام.</p>
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

<!-- ================= NEW CHAT MODAL ================= -->
<div id="new-chat-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-all opacity-0">
    <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-[2rem] shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform scale-95 transition-all" id="new-chat-modal-content">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-800 dark:text-white">محادثة جديدة</h3>
            <button type="button" onclick="closeNewChatModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <div class="p-4">
            <div class="relative mb-4">
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <span class="material-symbols-outlined text-xl">search</span>
                </span>
                <input id="modal-contact-search" oninput="filterModalContacts()" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl py-3 pr-12 pl-4 text-xs font-semibold text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 transition-all outline-none" placeholder="البحث عن شخص..." type="text"/>
            </div>
            
            <div class="max-h-80 overflow-y-auto hide-scrollbar space-y-2" id="modal-contacts-list">
                @if(isset($allUsers) && count($allUsers) > 0)
                    @foreach($allUsers as $user)
                        @php
                            $roleAr = $user->role;
                            $badgeClass = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
                            if ($user->role === 'admin') { $badgeClass = 'bg-rose-500/10 text-rose-500'; $roleAr = 'الإدارة'; }
                            else if ($user->role === 'teacher') { $badgeClass = 'bg-blue-500/10 text-blue-500'; $roleAr = 'أستاذ المادة'; }
                            else if ($user->role === 'head') { $badgeClass = 'bg-amber-500/10 text-amber-500'; $roleAr = 'رئيس القسم'; }
                            else if ($user->role === 'affairs') { $badgeClass = 'bg-cyan-500/10 text-cyan-500'; $roleAr = 'شؤون الطلاب'; }
                            
                            $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : '';
                            $initials = mb_substr(trim($user->full_name), 0, 1);
                        @endphp
                        <div onclick="startNewChat({{ $user->user_id }}, '{{ addslashes($user->full_name) }}', '{{ $roleAr }}', '{{ $avatarUrl }}')" class="modal-contact-row flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/40 cursor-pointer transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-700" data-name="{{ strtolower($user->full_name) }}">
                            <!-- Avatar -->
                            <div class="relative shrink-0 select-none">
                                @if($user->avatar)
                                    <img class="w-10 h-10 rounded-full object-cover" src="{{ $avatarUrl }}" alt="Avatar">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-primary/20 text-yellow-700 dark:text-yellow-400 flex items-center justify-center font-bold text-sm">{{ $initials }}</div>
                                @endif
                            </div>
                            <!-- Details -->
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-800 dark:text-white truncate">{{ $user->full_name }}</div>
                                <div class="text-[10px] font-semibold text-slate-400 truncate">{{ $user->email }}</div>
                            </div>
                            <div class="shrink-0">
                                <span class="text-[9px] font-bold px-2 py-1 rounded-full {{ $badgeClass }}">{{ $roleAr }}</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8 text-slate-400 text-xs font-semibold">لا يوجد كادر إداري متاح حالياً للمحادثة</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ================= CSS EXTRA POLISHING ================= -->
@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
window.tailwind = {
    config: {
        corePlugins: {
            preflight: false,
        },
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
}
</script>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
</style>
@endpush

@push('scripts')
<script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
<script>
    const currentUserId = {{ Auth::id() }};
    let activeContactId = null;
    let selectedFile = null;

    // Audio recording state vars
    let mediaRecorder = null;
    let audioChunks = [];
    let recordingTimerInterval = null;
    let recordingSeconds = 0;
    let recordedAudioBlob = null;
    let isCancelledRecording = false;

    // Initialize Pusher Client
    const pusher = new Pusher('7ddc52d35c1e7beb4c83', {
        cluster: 'eu',
        encrypted: true
    });

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

    function loadContactsList() {
        fetch('{{ route("parent.messages.contacts") }}')
            .then(res => res.json())
            .then(data => {
                const loading = document.getElementById('contacts-loading');
                if (loading) loading.classList.add('hidden');

                const wrapper = document.getElementById('contacts-wrapper');
                const empty = document.getElementById('contacts-empty');
                wrapper.innerHTML = '';
                
                if (data.status === 'success' && data.data.length > 0) {
                    empty.classList.add('hidden');
                    wrapper.classList.remove('hidden');

                    data.data.forEach(contact => {
                        const hasUnread = contact.unread > 0;
                        const unreadBadge = hasUnread ? `<span class="bg-red-500 text-white rounded-full px-2 py-0.5 text-[9px] font-black shrink-0">${contact.unread}</span>` : '';
                        
                        let roleAr = contact.role;
                        let roleBadgeClass = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
                        if (contact.role === 'admin') { roleBadgeClass = 'bg-rose-500/10 text-rose-500'; roleAr = 'الإدارة'; }
                        else if (contact.role === 'teacher') { roleBadgeClass = 'bg-blue-500/10 text-blue-500'; roleAr = 'أستاذ المادة'; }
                        else if (contact.role === 'head') { roleBadgeClass = 'bg-amber-500/10 text-amber-500'; roleAr = 'رئيس القسم'; }
                        else if (contact.role === 'affairs') { roleBadgeClass = 'bg-cyan-500/10 text-cyan-500'; roleAr = 'شؤون الطلاب'; }

                        const avatarPlaceholder = contact.name.charAt(0);
                        const isSelected = activeContactId == contact.id;
                        
                        wrapper.innerHTML += `
                            <div onclick="selectContact(${contact.id}, '${contact.name.replace(/'/g, "\\'")}', '${roleAr}', '${contact.image || ''}')" 
                                 class="contact-row flex items-center gap-3 p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors ${isSelected ? 'bg-slate-100/80 dark:bg-slate-800/80 border-r-4 border-primary' : ''}" 
                                 data-name="${contact.name.toLowerCase()}">
                                <div class="relative shrink-0">
                                    ${contact.image ? 
                                        `<img src="${contact.image}" class="w-10 h-10 rounded-full object-cover" alt="">` :
                                        `<div class="w-10 h-10 rounded-full bg-primary/20 text-yellow-700 dark:text-yellow-400 flex items-center justify-center font-bold text-sm select-none">${avatarPlaceholder}</div>`
                                    }
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center mb-0.5">
                                        <span class="text-xs font-bold text-slate-800 dark:text-white truncate">${contact.name}</span>
                                        <span class="text-[9px] text-slate-400">${contact.time || ''}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] text-slate-400 truncate font-semibold">${contact.last_message || 'انقر لبدء المحادثة...'}</span>
                                        <span class="text-[8px] font-bold px-1.5 py-0.5 rounded ${roleBadgeClass}">${roleAr}</span>
                                    </div>
                                </div>
                                ${unreadBadge}
                            </div>
                        `;
                    });

                    if (!activeContactId && data.data.length > 0 && window.innerWidth >= 768) {
                        const first = data.data[0];
                        let roleAr = first.role;
                        if (first.role === 'admin') roleAr = 'الإدارة';
                        else if (first.role === 'teacher') roleAr = 'أستاذ المادة';
                        else if (first.role === 'head') roleAr = 'رئيس القسم';
                        else if (first.role === 'affairs') roleAr = 'شؤون الطلاب';
                        selectContact(first.id, first.name.replace(/'/g, "\\'"), roleAr, first.image || '');
                    }
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

        if (avatar && avatar !== 'null') {
            avatarImg.src = avatar;
            avatarImg.classList.remove('hidden');
            avatarPlaceholder.classList.add('hidden');
        } else {
            avatarImg.src = "";
            avatarImg.classList.add('hidden');
            avatarPlaceholder.innerText = name.charAt(0);
            avatarPlaceholder.classList.remove('hidden');
        }

        // Hide sidebar on mobile
        if (window.innerWidth < 768) {
            const sidebar = document.getElementById('contacts-sidebar-pane');
            const chatRoom = document.getElementById('chat-room-pane');
            if (sidebar) {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('flex');
            }
            if (chatRoom) {
                chatRoom.classList.remove('hidden');
                chatRoom.classList.add('flex');
            }
        }

        fetchMessages(id);
    }

    function showSidebarOnMobile() {
        const sidebar = document.getElementById('contacts-sidebar-pane');
        const chatRoom = document.getElementById('chat-room-pane');
        if (sidebar) {
            sidebar.classList.remove('hidden');
            sidebar.classList.add('flex');
        }
        if (chatRoom) {
            chatRoom.classList.add('hidden');
            chatRoom.classList.remove('flex');
        }
    }

    function fetchMessages(contactId) {
        const feed = document.getElementById('messages-feed');
        feed.innerHTML = '<div class="text-center text-xs text-slate-400 py-12">جاري تحميل الرسائل...</div>';
        
        fetch(`/parent/messages/conversation/${contactId}`)
            .then(res => res.json())
            .then(messages => {
                feed.innerHTML = '';
                if (messages && messages.length > 0) {
                    messages.forEach(msg => appendMessageBubble(msg));
                    scrollMessagesToBottom();
                } else {
                    feed.innerHTML = '<div class="text-center text-xs text-slate-400 py-12">لا توجد رسائل سابقة. ابدأ المحادثة الآن!</div>';
                }
                markActiveChatAsRead();
            });
    }

    function appendMessageBubble(msg) {
        const feed = document.getElementById('messages-feed');
        const isMe = parseInt(msg.sender_id) === currentUserId;
        
        const empty = feed.querySelector('.text-center');
        if (empty && empty.innerText.includes('لا توجد رسائل')) empty.remove();

        const timeStr = msg.created_at ? new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
        const isVoice = (msg.attachment && (msg.attachment.endsWith('.webm') || msg.attachment.endsWith('.wav') || msg.attachment.endsWith('.mp3') || msg.attachment.endsWith('.ogg'))) || msg.message === '[Voice Note]';

        let fileType = '';
        if (msg.fileObject) {
            fileType = msg.fileObject.type;
        } else if (msg.attachment) {
            const ext = msg.attachment.split('.').pop().toLowerCase().split('?')[0];
            if (['png', 'jpg', 'jpeg', 'webp', 'gif'].includes(ext)) {
                fileType = 'image/';
            } else if (['mp3', 'wav', 'm4a', 'ogg', 'aac'].includes(ext) || isVoice) {
                fileType = 'audio/';
            } else if (['mp4', 'mov', 'webm', 'avi'].includes(ext)) {
                if (ext === 'webm' && (msg.message === '[Voice Note]' || isVoice)) {
                    fileType = 'audio/';
                } else {
                    fileType = 'video/';
                }
            }
        }

        let pendingOverlay = '';
        let pendingProgressHtml = '';
        if (msg.isPending) {
            let pendingLabel = 'جاري رفع الملف...';
            if (fileType.startsWith('image/')) pendingLabel = 'جاري رفع الصورة...';
            else if (fileType.startsWith('video/')) pendingLabel = 'جاري رفع الفيديو...';
            else if (fileType.startsWith('audio/') || isVoice) pendingLabel = 'جاري رفع الصوت...';

            pendingOverlay = `
                <div class="absolute inset-0 bg-slate-900/75 backdrop-blur-[2px] flex flex-col items-center justify-center text-white rounded-xl gap-2 p-3 z-20 select-none transition-all">
                    <div class="w-full bg-white/20 rounded-full h-2 overflow-hidden max-w-[180px]">
                        <div id="overlay-progress-bar-${msg.id}" class="bg-[#FFCC00] h-full rounded-full transition-all duration-150" style="width: 0%;"></div>
                    </div>
                    <span id="overlay-progress-text-${msg.id}" class="text-[10px] font-bold tracking-wide drop-shadow-sm">${pendingLabel} 0%</span>
                </div>
            `;

            pendingProgressHtml = `
                <div class="mt-2.5 w-full min-w-[180px] dir-rtl">
                    <div class="w-full bg-black/10 dark:bg-white/10 rounded-full h-2 overflow-hidden border border-black/5 dark:border-white/10">
                        <div id="progress-bar-${msg.id}" class="bg-amber-500 dark:bg-amber-400 h-full rounded-full transition-all duration-150" style="width: 0%;"></div>
                    </div>
                    <div class="flex items-center justify-between mt-1 text-[10px] font-bold opacity-80">
                        <span id="progress-text-${msg.id}">${pendingLabel} 0%</span>
                        <span class="material-symbols-outlined text-[13px] animate-spin">progress_activity</span>
                    </div>
                </div>
            `;
        }

        let attachmentHtml = '';
        if (msg.attachment) {
            if (isVoice) {
                const voiceId = 'voice-' + (msg.id || msg.message_id || Math.random().toString(36).substring(2, 9));
                attachmentHtml = `
                    <div class="relative mt-1 max-w-[270px]">
                        ${pendingOverlay}
                        <div class="flex items-center gap-3 p-3 rounded-2xl ${isMe ? 'bg-black/10 text-black' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100'} min-w-[230px] dir-ltr select-none border border-black/5 dark:border-white/10">
                            <audio id="${voiceId}" src="${msg.attachment}" preload="metadata" ontimeupdate="updateVoiceProgress('${voiceId}')" onended="resetVoicePlayer('${voiceId}')"></audio>
                            
                            <button type="button" onclick="toggleVoicePlay('${voiceId}')" id="btn-${voiceId}" class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 ${isMe ? 'bg-black text-white hover:bg-slate-900' : 'bg-amber-500 text-white hover:bg-amber-600'} transition-all shadow-md active:scale-95">
                                <span class="material-symbols-outlined text-xl" id="icon-${voiceId}">play_arrow</span>
                            </button>

                            <div class="flex-1 flex flex-col justify-center gap-1.5 min-w-0">
                                <div class="relative w-full h-2 bg-black/10 dark:bg-white/15 rounded-full overflow-hidden cursor-pointer" onclick="seekVoice('${voiceId}', event)">
                                    <div id="progress-${voiceId}" class="h-full ${isMe ? 'bg-black' : 'bg-amber-500'} rounded-full transition-all duration-100" style="width: 0%;"></div>
                                </div>
                                
                                <div class="flex items-center justify-between text-[10px] font-bold opacity-75 dir-rtl">
                                    <span id="time-${voiceId}">00:00</span>
                                    <span class="flex items-center gap-0.5"><span class="material-symbols-outlined text-[13px]">graphic_eq</span> صوتية</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                const fileName = msg.attachment.split('/').pop();
                attachmentHtml = `
                    <div class="relative mt-2 pt-2 border-t ${isMe ? 'border-black/10' : 'border-slate-200 dark:border-slate-700'}">
                        ${pendingOverlay}
                        <a href="${msg.attachment}" target="_blank" download class="flex items-center gap-2 text-xs font-bold underline ${isMe ? 'text-slate-900' : 'text-primary-dark dark:text-primary'}">
                            <span class="material-symbols-outlined text-sm">download</span>
                            <span class="truncate max-w-[150px]">${fileName}</span>
                        </a>
                    </div>
                `;
            }
        }

        const msgId = msg.id || msg.message_id;
        const msgTextEscaped = (msg.message || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');

        let actionButtons = '';
        if (!msg.isPending) {
            actionButtons = `
                <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1 shrink-0 ${isMe ? 'mr-2' : 'ml-2'}">
                    ${isMe && (!msg.attachment && msg.message !== '[Voice Note]') ? `
                    <button onclick="editMessagePrompt(${msgId}, '${msgTextEscaped}')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" title="تعديل">
                        <span class="material-symbols-outlined text-sm">edit</span>
                    </button>` : ''}
                    <button onclick="deleteMessageConfirm(${msgId}, ${isMe})" class="text-slate-400 hover:text-red-500" title="حذف">
                        <span class="material-symbols-outlined text-sm">delete</span>
                    </button>
                </div>
            `;
        }

        const pendingClass = msg.isPending ? 'opacity-70 message-pending' : '';
        const msgHtml = `
            <div class="flex ${isMe ? 'justify-end' : 'justify-start'} group message-bubble-item mb-3.5 ${pendingClass}" data-message-text="${(msg.message || '').toLowerCase()}" id="${msg.isPending ? 'msg-' + msg.id : 'msg-bubble-' + msgId}">
                ${isMe ? actionButtons : ''}
                <div class="max-w-[70%] p-3.5 ${isMe ? 'bg-[#FFCC00] text-black chat-bubble-sent' : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 chat-bubble-received border border-slate-200/60 dark:border-slate-700/60'} shadow-sm">
                    <p class="text-xs font-semibold leading-relaxed break-words" id="msg-text-content-${msgId}">${msg.message && msg.message !== '[Voice Note]' ? msg.message : ''}</p>
                    ${attachmentHtml}
                    ${msg.isPending && (!msg.attachment || (!fileType.startsWith('image/') && !fileType.startsWith('video/') && !fileType.startsWith('audio/') && !isVoice)) ? pendingProgressHtml : ''}
                    <div class="text-[9px] ${isMe ? 'text-black/60' : 'text-slate-400'} mt-1 text-left font-mono">${timeStr}</div>
                </div>
                ${!isMe ? actionButtons : ''}
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
            document.getElementById('attachment-preview-container').classList.remove('hidden');
            document.getElementById('preview-filename').textContent = selectedFile.name;
            document.getElementById('preview-filesize').textContent = (selectedFile.size / 1024).toFixed(1) + ' KB';
        }
    }

    function clearSelectedAttachment() {
        selectedFile = null;
        document.getElementById('message-file').value = '';
        document.getElementById('attachment-preview-container').classList.add('hidden');
    }

    // Audio Voice Note Recording
    function startAudioRecording() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('المتصفح لا يدعم تسجيل الصوت المباشر.');
            return;
        }

        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                isCancelledRecording = false;

                mediaRecorder.ondataavailable = e => {
                    if (e.data.size > 0) audioChunks.push(e.data);
                };

                mediaRecorder.onstop = () => {
                    stream.getTracks().forEach(track => track.stop());
                    if (!isCancelledRecording && audioChunks.length > 0) {
                        recordedAudioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                        sendVoiceNoteMessage();
                    }
                };

                mediaRecorder.start();
                recordingSeconds = 0;
                document.getElementById('voice-recording-interface').classList.remove('hidden');
                document.getElementById('standard-input-elements').classList.add('hidden');
                
                recordingTimerInterval = setInterval(() => {
                    recordingSeconds++;
                    const mins = String(Math.floor(recordingSeconds / 60)).padStart(2, '0');
                    const secs = String(recordingSeconds % 60).padStart(2, '0');
                    document.getElementById('recording-timer').textContent = `${mins}:${secs}`;
                }, 1000);
            })
            .catch(err => {
                alert('يتعذر الوصول إلى الميكروفون: ' + err.message);
            });
    }

    function stopAudioRecording(cancel = false) {
        isCancelledRecording = cancel;
        if (recordingTimerInterval) clearInterval(recordingTimerInterval);
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        document.getElementById('voice-recording-interface').classList.add('hidden');
        document.getElementById('standard-input-elements').classList.remove('hidden');
    }

    function cancelAudioRecording() {
        stopAudioRecording(true);
    }

    // --- Voice Note Custom Player Logic ---
    function toggleVoicePlay(id) {
        const audio = document.getElementById(id);
        const icon = document.getElementById('icon-' + id);
        if (!audio || !icon) return;

        document.querySelectorAll('audio[id^="voice-"]').forEach(a => {
            if (a.id !== id && !a.paused) {
                a.pause();
                const otherIcon = document.getElementById('icon-' + a.id);
                if (otherIcon) otherIcon.innerText = 'play_arrow';
            }
        });

        if (audio.paused) {
            audio.play().then(() => {
                icon.innerText = 'pause';
            }).catch(err => console.error("Audio play failed: ", err));
        } else {
            audio.pause();
            icon.innerText = 'play_arrow';
        }
    }

    function updateVoiceProgress(id) {
        const audio = document.getElementById(id);
        const progress = document.getElementById('progress-' + id);
        const timeEl = document.getElementById('time-' + id);
        if (!audio || !progress) return;

        const currentTime = audio.currentTime || 0;
        const duration = audio.duration || 0;
        
        if (duration > 0) {
            const percent = (currentTime / duration) * 100;
            progress.style.width = percent + '%';
        }

        if (timeEl) {
            const mins = Math.floor(currentTime / 60).toString().padStart(2, '0');
            const secs = Math.floor(currentTime % 60).toString().padStart(2, '0');
            timeEl.innerText = `${mins}:${secs}`;
        }
    }

    function resetVoicePlayer(id) {
        const icon = document.getElementById('icon-' + id);
        const progress = document.getElementById('progress-' + id);
        const timeEl = document.getElementById('time-' + id);
        if (icon) icon.innerText = 'play_arrow';
        if (progress) progress.style.width = '0%';
        if (timeEl) timeEl.innerText = '00:00';
    }

    function seekVoice(id, event) {
        const audio = document.getElementById(id);
        if (!audio || !audio.duration) return;

        const rect = event.currentTarget.getBoundingClientRect();
        const clickX = event.clientX - rect.left;
        const width = rect.width;
        const seekTime = (clickX / width) * audio.duration;
        audio.currentTime = seekTime;
    }

    function sendVoiceNoteMessage() {
        if (!recordedAudioBlob || !activeContactId) return;

        const fd = new FormData();
        fd.append('receiver_id', activeContactId);
        fd.append('message', '[Voice Note]');
        fd.append('attachment', recordedAudioBlob, `voice_note_${Date.now()}.webm`);

        const tempTime = new Date().toISOString();
        const tempId = 'temp_' + Date.now();
        appendMessageBubble({
            id: tempId,
            sender_id: currentUserId,
            receiver_id: activeContactId,
            message: '[Voice Note]',
            attachment: URL.createObjectURL(recordedAudioBlob),
            fileObject: recordedAudioBlob,
            created_at: tempTime,
            isPending: true
        });
        scrollMessagesToBottom();

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route("parent.messages.send") }}', true);
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                const pBar = document.getElementById(`progress-bar-${tempId}`);
                const pText = document.getElementById(`progress-text-${tempId}`);
                const oBar = document.getElementById(`overlay-progress-bar-${tempId}`);
                const oText = document.getElementById(`overlay-progress-text-${tempId}`);

                if (pBar) pBar.style.width = percent + '%';
                if (pText) pText.innerText = `جاري رفع الصوت... ${percent}%`;
                if (oBar) oBar.style.width = percent + '%';
                if (oText) oText.innerText = `جاري رفع الصوت... ${percent}%`;
            }
        };

        xhr.onload = function() {
            const pendingBubble = document.getElementById(`msg-${tempId}`);
            if (pendingBubble) {
                pendingBubble.remove();
            }
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.status === 'success' || data.data) {
                        const newMsg = data.data || data.message;
                        if (newMsg && !document.getElementById('msg-bubble-' + (newMsg.id || newMsg.message_id))) {
                            appendMessageBubble(newMsg);
                        }
                        loadContactsList();
                    } else {
                        alert('فشل إرسال الملاحظة الصوتية: ' + (data.message || data.error || 'خطأ غير معروف'));
                    }
                } catch (e) {
                    alert('حدث خطأ أثناء معالجة استجابة الخادم.');
                }
            } else {
                alert('فشل إرسال الصوت. خطأ الخادم: ' + xhr.status);
            }
            recordedAudioBlob = null;
        };

        xhr.onerror = function() {
            const pendingBubble = document.getElementById(`msg-${tempId}`);
            if (pendingBubble) {
                pendingBubble.remove();
            }
            alert('فشل إرسال الملاحظة الصوتية. يرجى التحقق من اتصالك بالشبكة.');
            recordedAudioBlob = null;
        };

        xhr.send(fd);
    }

    function submitMessage() {
        const textInput = document.getElementById('message-text');
        const text = textInput.value.trim();
        if (!text && !selectedFile) return;
        if (!activeContactId) return;

        const fd = new FormData();
        fd.append('receiver_id', activeContactId);
        fd.append('message', selectedFile && text === '' ? '[Attachment]' : text);
        if (selectedFile) {
            fd.append('attachment', selectedFile);
        }

        const tempTime = new Date().toISOString();
        const tempId = 'temp_' + Date.now();
        const fileToUpload = selectedFile;

        appendMessageBubble({
            id: tempId,
            sender_id: currentUserId,
            receiver_id: activeContactId,
            message: fileToUpload && text === '' ? '' : text,
            attachment: fileToUpload ? URL.createObjectURL(fileToUpload) : null,
            fileObject: fileToUpload,
            created_at: tempTime,
            isPending: true
        });
        scrollMessagesToBottom();

        textInput.value = '';
        clearSelectedAttachment();

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route("parent.messages.send") }}', true);
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                const pBar = document.getElementById(`progress-bar-${tempId}`);
                const pText = document.getElementById(`progress-text-${tempId}`);
                const oBar = document.getElementById(`overlay-progress-bar-${tempId}`);
                const oText = document.getElementById(`overlay-progress-text-${tempId}`);

                if (pBar) pBar.style.width = percent + '%';
                if (pText) pText.innerText = `جاري الرفع... ${percent}%`;
                if (oBar) oBar.style.width = percent + '%';
                if (oText) oText.innerText = `جاري الرفع... ${percent}%`;
            }
        };

        xhr.onload = function() {
            const pendingBubble = document.getElementById(`msg-${tempId}`);
            if (pendingBubble) {
                pendingBubble.remove();
            }
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.status === 'success' || data.data) {
                        const newMsg = data.data || data.message;
                        if (newMsg && !document.getElementById('msg-bubble-' + (newMsg.id || newMsg.message_id))) {
                            appendMessageBubble(newMsg);
                        }
                        loadContactsList();
                    } else {
                        alert('فشل في إرسال الرسالة: ' + (data.message || data.error || 'خطأ غير معروف'));
                    }
                } catch (e) {
                    alert('حدث خطأ أثناء معالجة استجابة الخادم.');
                }
            } else {
                alert('فشل إرسال الرسالة. خطأ من الخادم: ' + xhr.status);
            }
        };

        xhr.onerror = function() {
            const pendingBubble = document.getElementById(`msg-${tempId}`);
            if (pendingBubble) {
                pendingBubble.remove();
            }
            alert('فشل إرسال الرسالة. يرجى التحقق من اتصالك بالشبكة.');
        };

        xhr.send(fd);
    }

    function markActiveChatAsRead() {
        if (!activeContactId) return;
        fetch(`/parent/messages/conversation/${activeContactId}`)
            .then(() => loadContactsList());
    }

    function editMessagePrompt(id, currentText) {
        const newText = prompt('تعديل الرسالة:', currentText);
        if (newText !== null && newText.trim() !== '') {
            fetch(`/parent/messages/${id}/edit`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ message: newText.trim() })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const contentElem = document.getElementById(`msg-text-content-${id}`);
                    if (contentElem) contentElem.textContent = newText.trim();
                }
            });
        }
    }

    function deleteMessageConfirm(id, isMe) {
        if (typeof Swal !== 'undefined') {
            if (isMe) {
                Swal.fire({
                    title: 'حذف الرسالة',
                    text: 'اختر نوع الحذف المطلوب:',
                    icon: 'warning',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonColor: '#ef4444',
                    denyButtonColor: '#3b82f6',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'حذف لدى الجميع',
                    denyButtonText: 'حذف لدي فقط',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performDeleteMessage(id, 'everyone');
                    } else if (result.isDenied) {
                        performDeleteMessage(id, 'me');
                    }
                });
            } else {
                Swal.fire({
                    title: 'حذف الرسالة',
                    text: 'هل أنت متأكد من حذف هذه الرسالة لديك؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'حذف لدي',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performDeleteMessage(id, 'me');
                    }
                });
            }
        } else {
            let type = 'me';
            if (isMe) {
                if (confirm('هل تريد حذف الرسالة لدى الجميع؟ (اضغط إلغاء للحذف لديك فقط)')) {
                    type = 'everyone';
                } else if (!confirm('هل تريد حذف الرسالة لديك فقط؟')) {
                    return;
                }
            } else {
                if (!confirm('هل أنت متأكد من حذف هذه الرسالة لديك؟')) return;
            }
            performDeleteMessage(id, type);
        }
    }

    function performDeleteMessage(id, type) {
        fetch(`/parent/messages/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ type: type })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const elem = document.getElementById(`msg-bubble-${id}`);
                if (elem) elem.remove();
                loadContactsList();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('خطأ', data.message || 'فشل الحذف', 'error');
                } else {
                    alert('فشل الحذف: ' + (data.message || 'خطأ غير معروف'));
                }
            }
        }).catch(err => {
            console.error("Delete error:", err);
            alert('خطأ في الاتصال أثناء تنفيذ عملية الحذف.');
        });
    }

    function searchActiveChatMessages() {
        const q = document.getElementById('message-search-input').value.toLowerCase().trim();
        const bubbles = document.querySelectorAll('.message-bubble-item');
        bubbles.forEach(b => {
            const txt = b.getAttribute('data-message-text') || '';
            if (txt.includes(q)) {
                b.style.display = 'flex';
            } else {
                b.style.display = 'none';
            }
        });
    }

    // Modal Functions
    function openNewChatModal() {
        const modal = document.getElementById('new-chat-modal');
        const content = document.getElementById('new-chat-modal-content');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
        }, 10);
    }

    function closeNewChatModal() {
        const modal = document.getElementById('new-chat-modal');
        const content = document.getElementById('new-chat-modal-content');
        modal.classList.add('opacity-0');
        content.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    function filterModalContacts() {
        const q = document.getElementById('modal-contact-search').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.modal-contact-row');
        rows.forEach(r => {
            const name = r.getAttribute('data-name');
            if (name.includes(q)) {
                r.classList.remove('hidden');
                r.classList.add('flex');
            } else {
                r.classList.remove('flex');
                r.classList.add('hidden');
            }
        });
    }

    function startNewChat(id, name, role, avatar) {
        closeNewChatModal();
        selectContact(id, name, role, avatar);
    }

    loadContactsList();
</script>
</script>
@endpush
@endsection
