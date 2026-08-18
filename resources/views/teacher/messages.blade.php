@extends('layouts.teacher')

@section('title', 'الرسائل')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<div class="flex h-[calc(100vh-8.5rem)] min-h-[550px] overflow-hidden rounded-3xl bg-[#141417] border border-[#27272a] shadow-2xl text-slate-100 transition-colors" id="chat-app-container">
    
    <!-- ================= SIDEBAR (CONTACTS PANEL) ================= -->
    <div class="w-full md:w-80 flex flex-col border-l border-[#27272a] h-full shrink-0 md:flex p-4 bg-[#141417] overflow-hidden" id="contacts-sidebar-pane">
        
        <!-- Header & Search Title -->
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-extrabold text-white tracking-wide">مكان للبحث عن</h2>
            <button type="button" onclick="openNewChatModal()" class="w-8 h-8 rounded-xl bg-[#27272a] hover:bg-[#3f3f46] text-[#f2f20d] flex items-center justify-center transition-all shadow-md" title="محادثة جديدة">
                <span class="material-symbols-outlined text-lg">add_comment</span>
            </button>
        </div>

        <!-- Search Input -->
        <div class="relative mb-4">
            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                <span class="material-symbols-outlined text-lg">search</span>
            </span>
            <input id="contact-search" oninput="filterContactsList()" class="w-full bg-[#1c1c20] border border-[#2d2d35] rounded-xl py-2.5 pr-10 pl-4 text-xs font-semibold text-slate-200 placeholder:text-slate-500 focus:ring-1 focus:ring-[#f2f20d] transition-all outline-none" placeholder="ابحث عن زملائك..." type="text"/>
        </div>

        <!-- Section Title -->
        <div class="text-[11px] font-bold text-slate-400 mb-2 px-1">أحدث المحادثات</div>

        <!-- Contacts Scrollable List -->
        <div class="flex-1 overflow-y-auto hide-scrollbar space-y-1.5 pr-1" id="contacts-list-container">
            <!-- Loading Indicator -->
            <div id="contacts-loading" class="flex flex-col items-center justify-center py-12 gap-3 text-slate-400">
                <div class="animate-spin rounded-full h-7 w-7 border-2 border-[#f2f20d] border-t-transparent"></div>
                <span class="text-xs font-semibold">جاري تحميل المحادثات...</span>
            </div>
            
            <!-- Dynamic Contacts List Wrapper -->
            <div id="contacts-wrapper" class="hidden space-y-1.5"></div>
            
            <div id="contacts-empty" class="hidden flex flex-col items-center justify-center py-12 text-slate-400 text-center px-4">
                <span class="material-symbols-outlined text-4xl mb-2 text-slate-600">contact_support</span>
                <span class="text-xs font-bold">لا توجد جهات اتصال متاحة</span>
            </div>
        </div>
    </div>

    <!-- ================= CHAT ROOM WINDOW ================= -->
    <div class="flex-1 flex flex-col h-full bg-[#101014] relative hidden md:flex overflow-hidden" id="chat-room-pane">
        
        <!-- Chat Placeholder (Visible when no active chat) -->
        <div id="chat-placeholder" class="absolute inset-0 flex flex-col items-center justify-center p-8 bg-[#101014] z-10 text-center transition-colors duration-300">
            <div class="w-20 h-20 rounded-full bg-[#1c1c20] border border-[#27272a] flex items-center justify-center text-[#f2f20d] mb-5 shadow-lg">
                <span class="material-symbols-outlined text-4xl">forum</span>
            </div>
            <h3 class="text-base font-black text-white mb-2">مرحباً بك في نظام المحادثات الفورية</h3>
            <p class="text-xs text-slate-400 max-w-sm leading-relaxed">اختر إحدى المحادثات المتاحة في القائمة للبدء أو انقر على زر إضافة محادثة لبدء تواصل جديد.</p>
        </div>

        <!-- Active Chat Window Container -->
        <div id="active-chat-window" class="flex flex-col h-full hidden">
            
            <!-- Chat Room Header -->
            <div class="px-5 py-3.5 bg-[#141417] border-b border-[#27272a] flex items-center justify-between transition-colors shrink-0">
                <div class="flex items-center gap-3">
                    <!-- Mobile Back Arrow -->
                    <button onclick="showSidebarOnMobile()" class="md:hidden flex items-center justify-center w-8 h-8 rounded-lg bg-[#27272a] text-slate-300 hover:bg-[#3f3f46]">
                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </button>

                    <!-- Contact Avatar & Status -->
                    <div class="relative shrink-0 select-none">
                        <div id="active-contact-avatar-placeholder" class="w-10 h-10 rounded-full bg-[#f2f20d]/20 text-[#f2f20d] border border-[#f2f20d]/30 flex items-center justify-center font-bold text-sm">
                            ?
                        </div>
                        <img id="active-contact-avatar-img" class="w-10 h-10 rounded-full object-cover border border-[#27272a] hidden" src="" alt="Avatar">
                        <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-[#141417] rounded-full"></div>
                    </div>

                    <!-- Contact Info -->
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span id="active-contact-name" class="text-sm font-bold text-white leading-tight">...</span>
                            <span id="active-contact-role" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-[#27272a] text-slate-300">...</span>
                        </div>
                        <span class="text-[10px] text-emerald-400 font-semibold mt-0.5 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> متصل الآن
                        </span>
                    </div>
                </div>

                <!-- Top Header Actions -->
                <div class="flex items-center gap-2">
                    <!-- Disappearing Timer Menu Dropdown -->
                    <div class="relative">
                        <button type="button" onclick="toggleDisappearingMenu()" id="disappearing-menu-btn" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#1c1c20] hover:bg-[#27272a] text-slate-300 hover:text-[#f2f20d] text-xs font-semibold transition-all border border-[#27272a]" title="الرسائل ذاتية الاختفاء">
                            <span class="material-symbols-outlined text-base text-amber-400">timer</span>
                            <span id="disappearing-btn-label" class="hidden sm:inline">ذاتية الاختفاء</span>
                        </button>
                        
                        <!-- Dropdown Options -->
                        <div id="disappearing-menu" class="hidden absolute left-0 top-10 mt-1 bg-[#1c1c20] rounded-xl shadow-2xl border border-[#27272a] p-2 text-xs min-w-[200px] z-[99] space-y-1">
                            <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 border-b border-[#27272a]">مؤقت اختفاء الرسائل الجديدة</div>
                            <button type="button" onclick="setDisappearingTimer(0, 'إيقاف')" class="w-full text-right px-3 py-2 rounded-lg hover:bg-[#27272a] flex items-center justify-between font-medium text-slate-200">
                                <span>🚫 إيقاف (رسائل دائمة)</span>
                            </button>
                            <button type="button" onclick="setDisappearingTimer(300, '5 دقائق')" class="w-full text-right px-3 py-2 rounded-lg hover:bg-[#27272a] flex items-center justify-between font-medium text-slate-200">
                                <span>⏱️ 5 دقائق</span>
                            </button>
                            <button type="button" onclick="setDisappearingTimer(3600, 'ساعة واحدة')" class="w-full text-right px-3 py-2 rounded-lg hover:bg-[#27272a] flex items-center justify-between font-medium text-slate-200">
                                <span>⏱️ ساعة واحدة</span>
                            </button>
                            <button type="button" onclick="setDisappearingTimer(86400, '24 ساعة')" class="w-full text-right px-3 py-2 rounded-lg hover:bg-[#27272a] flex items-center justify-between font-medium text-slate-200">
                                <span>⏱️ 24 ساعة (يوم)</span>
                            </button>
                            <button type="button" onclick="setDisappearingTimer(604800, '7 أيام')" class="w-full text-right px-3 py-2 rounded-lg hover:bg-[#27272a] flex items-center justify-between font-medium text-slate-200">
                                <span>⏱️ 7 أيام (أسبوع)</span>
                            </button>
                        </div>
                    </div>

                    <!-- In-Chat Search Bar -->
                    <div class="hidden sm:block relative">
                        <input type="text" id="message-search-input" onkeyup="searchActiveChatMessages()" placeholder="البحث في المحادثة..." class="bg-[#1c1c20] border border-[#2d2d35] rounded-xl py-1.5 pr-8 pl-3 text-xs font-medium text-slate-200 placeholder:text-slate-500 focus:ring-1 focus:ring-[#f2f20d] outline-none w-36 sm:w-44 transition-all">
                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <span class="material-symbols-outlined text-sm">search</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Messages Feed Container -->
            <div id="messages-feed" class="flex-1 p-4 md:p-6 overflow-y-auto space-y-4 flex flex-col bg-[#101014]">
                <!-- Messages render here -->
            </div>

            <!-- Chat Bottom Bar Input Area -->
            <div class="p-3 md:p-4 bg-[#141417] border-t border-[#27272a] shrink-0 relative">
                
                <!-- Disappearing Messages Active Banner -->
                <div id="disappearing-active-banner" class="hidden bg-amber-500/10 border border-amber-500/20 rounded-xl px-3.5 py-1.5 flex items-center justify-between text-xs text-amber-400 font-bold mb-2 transition-all">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">timer</span>
                        <span>الرسائل الجديدة ستختفي تلقائياً بعد: <strong id="disappearing-banner-time">24 ساعة</strong></span>
                    </div>
                    <button type="button" onclick="setDisappearingTimer(0, 'إيقاف')" class="text-slate-400 hover:text-red-400 p-0.5">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>

                <!-- Attachment Preview Banner -->
                <div id="attachment-preview-container" class="hidden bg-[#1c1c20] border border-[#27272a] rounded-xl p-2.5 shadow-lg flex items-center justify-between gap-3 w-full mb-2 transition-all">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-lg bg-[#f2f20d]/20 flex items-center justify-center text-[#f2f20d] shrink-0" id="preview-icon">
                            <span class="material-symbols-outlined text-lg">insert_drive_file</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span id="preview-filename" class="text-xs font-bold text-slate-100 truncate max-w-[240px]">file.pdf</span>
                            <span id="preview-filesize" class="text-[10px] font-semibold text-slate-400">0 KB</span>
                        </div>
                    </div>
                    <button type="button" onclick="clearSelectedAttachment()" class="w-7 h-7 rounded-full bg-[#27272a] flex items-center justify-center text-slate-400 hover:text-red-400 transition-colors shrink-0" title="إلغاء الملف">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>

                <!-- Form Bar -->
                <form id="chat-send-form" onsubmit="event.preventDefault(); submitMessage();" class="flex items-center gap-2" enctype="multipart/form-data">
                    <input type="hidden" id="current-receiver-id" value="">

                    <!-- Voice Recording Overlay -->
                    <div id="voice-recording-interface" class="hidden flex-1 flex items-center justify-between bg-[#1c1c20] border border-[#27272a] rounded-2xl px-4 py-2.5 text-slate-200">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 bg-red-500 rounded-full animate-ping"></span>
                            <span class="text-xs font-black text-red-500">جاري تسجيل الصوت...</span>
                            <span id="recording-timer" class="text-xs font-bold font-mono text-slate-300">00:00</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="cancelAudioRecording()" class="flex items-center justify-center p-1.5 text-slate-400 hover:text-red-400 transition-colors" title="إلغاء">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                            <button type="button" onclick="stopAudioRecording(false)" class="flex items-center justify-center w-8 h-8 rounded-full bg-emerald-500 text-white hover:bg-emerald-600 transition-colors shadow-md" title="إرسال الصوت">
                                <span class="material-symbols-outlined text-lg">check</span>
                            </button>
                        </div>
                    </div>

                    <!-- Input Elements (Visible when not recording) -->
                    <div id="standard-input-elements" class="flex-1 flex items-center gap-2 bg-[#1c1c20] border border-[#2d2d35] rounded-2xl px-3 py-1.5 focus-within:border-[#f2f20d] transition-all">
                        
                        <!-- File Input -->
                        <label class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-[#27272a] text-slate-400 hover:text-[#f2f20d] cursor-pointer transition-colors shrink-0" title="إرفاق ملف أو صورة">
                            <span class="material-symbols-outlined text-xl">attach_file</span>
                            <input type="file" id="message-file" class="hidden" onchange="handleFileSelection(event)">
                        </label>

                        <!-- Input Field -->
                        <input id="message-text" class="flex-1 bg-transparent border-none py-1.5 px-2 text-xs md:text-sm font-medium text-slate-100 placeholder:text-slate-500 focus:outline-none" placeholder="اكتب رسالتك..." type="text" autocomplete="off"/>

                        <!-- Microphone Button -->
                        <button type="button" onclick="startAudioRecording()" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-[#27272a] text-slate-400 hover:text-[#f2f20d] transition-colors shrink-0" title="تسجيل ملاحظة صوتية">
                            <span class="material-symbols-outlined text-xl">mic</span>
                        </button>
                    </div>

                    <!-- Send Button -->
                    <button type="submit" id="send-btn" class="w-11 h-11 rounded-2xl bg-[#27272a] hover:bg-[#3f3f46] text-[#f2f20d] flex items-center justify-center transition-all shadow-lg shrink-0 active:scale-95 border border-[#3f3f46]" title="إرسال">
                        <span id="send-btn-icon" class="material-symbols-outlined text-xl rotate-180">send</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================= NEW CHAT MODAL ================= -->
<div id="new-chat-modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-all opacity-0">
    <div class="bg-[#141417] border border-[#27272a] w-full max-w-md rounded-3xl shadow-2xl overflow-hidden transform scale-95 transition-all" id="new-chat-modal-content">
        <div class="p-5 border-b border-[#27272a] flex items-center justify-between">
            <h3 class="text-base font-extrabold text-white">بدء محادثة جديدة</h3>
            <button type="button" onclick="closeNewChatModal()" class="w-8 h-8 rounded-xl bg-[#1c1c20] hover:bg-[#27272a] flex items-center justify-center text-slate-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <div class="p-4">
            <div class="relative mb-4">
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                    <span class="material-symbols-outlined text-lg">search</span>
                </span>
                <input id="modal-contact-search" oninput="filterModalContacts()" class="w-full bg-[#1c1c20] border border-[#2d2d35] rounded-xl py-2.5 pr-10 pl-4 text-xs font-semibold text-slate-200 placeholder:text-slate-500 focus:ring-1 focus:ring-[#f2f20d] outline-none transition-all" placeholder="البحث باسم المستخدم..." type="text"/>
            </div>
            
            <div class="max-h-80 overflow-y-auto hide-scrollbar space-y-1.5" id="modal-contacts-list">
                @if(isset($allUsers) && count($allUsers) > 0)
                    @foreach($allUsers as $user)
                        @php
                            $roleAr = $user->role;
                            $badgeClass = 'bg-[#27272a] text-slate-300';
                            if ($user->role === 'admin') { $badgeClass = 'bg-rose-500/20 text-rose-400'; $roleAr = 'الإدارة'; }
                            else if ($user->role === 'teacher') { $badgeClass = 'bg-blue-500/20 text-blue-400'; $roleAr = 'المدرب'; }
                            else if ($user->role === 'student') { $badgeClass = 'bg-emerald-500/20 text-emerald-400'; $roleAr = 'الطالب'; }
                            else if ($user->role === 'parent') { $badgeClass = 'bg-purple-500/20 text-purple-400'; $roleAr = 'الأهل'; }
                            else if ($user->role === 'head') { $badgeClass = 'bg-amber-500/20 text-amber-400'; $roleAr = 'رئيس القسم'; }
                            else if ($user->role === 'affairs') { $badgeClass = 'bg-cyan-500/20 text-cyan-400'; $roleAr = 'الشؤون'; }
                            
                            $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : '';
                            $initials = mb_substr(trim($user->full_name), 0, 1);
                        @endphp
                        <div onclick="startNewChat({{ $user->user_id }}, '{{ addslashes($user->full_name) }}', '{{ $roleAr }}', '{{ $avatarUrl }}')" class="modal-contact-row flex items-center gap-3 p-3 rounded-2xl hover:bg-[#1c1c20] cursor-pointer transition-colors border border-transparent hover:border-[#27272a]" data-name="{{ strtolower($user->full_name) }}">
                            <div class="relative shrink-0 select-none">
                                @if($user->avatar)
                                    <img class="w-10 h-10 rounded-full object-cover border border-[#27272a]" src="{{ $avatarUrl }}" alt="Avatar">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-[#f2f20d]/20 text-[#f2f20d] flex items-center justify-center font-bold text-xs border border-[#f2f20d]/30">{{ $initials }}</div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-white truncate">{{ $user->full_name }}</div>
                                <div class="text-[10px] font-medium text-slate-400 truncate">{{ $user->email }}</div>
                            </div>
                            <div class="shrink-0">
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-md {{ $badgeClass }}">{{ $roleAr }}</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8 text-slate-500 text-xs font-semibold">لا يوجد مستخدمين آخرين في النظام</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ================= CSS POLISHING ================= -->
@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    .chat-bubble-received {
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        border-bottom-left-radius: 0.25rem;
        border-bottom-right-radius: 1rem;
    }
    .chat-bubble-sent {
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        border-bottom-left-radius: 1rem;
        border-bottom-right-radius: 0.25rem;
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
    
    let echoInstance = null;

    let mediaRecorder = null;
    let audioChunks = [];
    let recordTimerInterval = null;
    let recordDurationSecs = 0;
    let isRecordingCancelled = false;

    document.addEventListener("DOMContentLoaded", function () {
        loadContacts();
        initializeEcho();
    });

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

        echoInstance.private('chat.' + currentUserId)
            .listen('MessageSent', (e) => {
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
                    loadContactsSilently();
                }
            })
            .listen('MessagesMarkedAsRead', (e) => {
                if (activeContactId && parseInt(e.reader_id) === parseInt(activeContactId)) {
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

    function loadContacts() {
        const loadingDiv = document.getElementById('contacts-loading');
        const emptyDiv = document.getElementById('contacts-empty');
        const wrapperDiv = document.getElementById('contacts-wrapper');

        loadingDiv.classList.remove('hidden');
        emptyDiv.classList.add('hidden');
        wrapperDiv.classList.add('hidden');

        fetch("{{ route('teacher.messages.contacts') }}", {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(res => {
                if (!res.ok) throw new Error("HTTP error " + res.status);
                return res.json();
            })
            .then(data => {
                loadingDiv.classList.add('hidden');
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    renderContactsList(data.data);
                    wrapperDiv.classList.remove('hidden');
                    if (!activeContactId && data.data.length > 0 && window.innerWidth >= 768) {
                        const first = data.data[0];
                        let roleAr = first.role;
                        if (first.role === 'admin') roleAr = 'الإدارة';
                        else if (first.role === 'teacher') roleAr = 'المدرب';
                        else if (first.role === 'student') roleAr = 'الطالب';
                        else if (first.role === 'parent') roleAr = 'الأهل';
                        else if (first.role === 'head') roleAr = 'رئيس القسم';
                        else if (first.role === 'affairs') roleAr = 'الشؤون';
                        selectContact(first.id, first.name, roleAr, first.image || '');
                    }
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

    function loadContactsSilently() {
        fetch("{{ route('teacher.messages.contacts') }}", {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.data) {
                    renderContactsList(data.data);
                    document.getElementById('contacts-wrapper').classList.remove('hidden');
                    document.getElementById('contacts-empty').classList.add('hidden');
                }
            })
            .catch(err => console.error("Silent contacts update failed: ", err));
    }

    function renderContactsList(contacts) {
        const wrapper = document.getElementById('contacts-wrapper');
        wrapper.innerHTML = '';

        contacts.forEach(contact => {
            let badgeClass = 'bg-[#27272a] text-slate-300';
            let roleAr = contact.role;
            if (contact.role === 'admin') {
                badgeClass = 'bg-rose-500/20 text-rose-400';
                roleAr = 'الإدارة';
            } else if (contact.role === 'teacher') {
                badgeClass = 'bg-blue-500/20 text-blue-400';
                roleAr = 'المدرب';
            } else if (contact.role === 'student') {
                badgeClass = 'bg-emerald-500/20 text-emerald-400';
                roleAr = 'الطالب';
            } else if (contact.role === 'parent') {
                badgeClass = 'bg-purple-500/20 text-purple-400';
                roleAr = 'الأهل';
            } else if (contact.role === 'head') {
                badgeClass = 'bg-amber-500/20 text-amber-400';
                roleAr = 'رئيس القسم';
            } else if (contact.role === 'affairs') {
                badgeClass = 'bg-cyan-500/20 text-cyan-400';
                roleAr = 'الشؤون';
            }

            const unreadBadge = contact.unread > 0 
                ? `<div class="bg-rose-500 text-white text-[9px] font-black w-5 h-5 rounded-full flex items-center justify-center animate-pulse">${contact.unread}</div>` 
                : '';

            const isActive = activeContactId && parseInt(contact.id) === parseInt(activeContactId) 
                ? 'bg-[#1c1c22] border-r-4 border-[#f2f20d]' 
                : '';

            const initials = contact.name.trim().charAt(0);
            
            let lastMsgText = contact.last_message || 'ابدأ محادثة جديدة';
            if (contact.last_message === '[Attachment]') {
                lastMsgText = '📁 ملف مرفق';
            } else if (contact.last_message === '[Voice Note]') {
                lastMsgText = '🎤 رسالة صوتية';
            }

            const safeName = (contact.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const safeRole = (roleAr || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const safeImg = (contact.image || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');

            const contactHtml = `
                <div onclick="selectContact(${contact.id}, '${safeName}', '${safeRole}', '${safeImg}')" 
                     class="contact-row flex items-center gap-3 p-3 rounded-2xl hover:bg-[#1c1c20] cursor-pointer transition-all ${isActive}"
                     data-name="${contact.name.toLowerCase()}">
                    
                    <div class="relative shrink-0 select-none">
                        ${contact.image 
                            ? `<img class="w-10 h-10 rounded-full object-cover border border-[#27272a]" src="${contact.image}" alt="Avatar">`
                            : `<div class="w-10 h-10 rounded-full bg-[#f2f20d]/20 text-[#f2f20d] flex items-center justify-center font-bold text-xs border border-[#f2f20d]/30">${initials}</div>`
                        }
                        <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-[#141417] rounded-full"></div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-white truncate">${contact.name}</span>
                            <span class="text-[9px] text-slate-500">${contact.time || ''}</span>
                        </div>
                        <div class="flex items-center justify-between mt-0.5">
                            <span class="text-[11px] font-medium truncate text-slate-400 max-w-[130px]">${lastMsgText}</span>
                            <span class="text-[8px] font-bold px-2 py-0.5 rounded-md ${badgeClass}">${roleAr}</span>
                        </div>
                    </div>
                    
                    <div class="shrink-0">
                        ${unreadBadge}
                    </div>
                </div>
            `;
            wrapper.innerHTML += contactHtml;
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

    function openNewChatModal() {
        const modal = document.getElementById('new-chat-modal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            document.getElementById('new-chat-modal-content').classList.remove('scale-95');
        }, 10);
    }

    function closeNewChatModal() {
        const modal = document.getElementById('new-chat-modal');
        modal.classList.add('opacity-0');
        document.getElementById('new-chat-modal-content').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function filterModalContacts() {
        const query = document.getElementById('modal-contact-search').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.modal-contact-row');

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

    function startNewChat(userId, name, roleLabel, avatarUrl) {
        closeNewChatModal();
        selectContact(userId, name, roleLabel, avatarUrl);
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

    function selectContact(contactId, name, roleLabel, avatarUrl) {
        activeContactId = contactId;

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

        document.getElementById('chat-placeholder').classList.add('hidden');
        document.getElementById('active-chat-window').classList.remove('hidden');
        document.getElementById('current-receiver-id').value = contactId;

        document.getElementById('active-contact-name').innerText = name;
        document.getElementById('active-contact-role').innerText = roleLabel;

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

        loadContactsSilently();
        fetchMessagesFeed(contactId);
    }

    function fetchMessagesFeed(contactId) {
        const feed = document.getElementById('messages-feed');
        feed.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full gap-2 text-slate-400 py-12">
                <div class="animate-spin rounded-full h-6 w-6 border-2 border-[#f2f20d] border-t-transparent"></div>
                <span class="text-xs font-semibold">تحميل الرسائل...</span>
            </div>
        `;

        fetch(`/teacher/messages/conversation/${contactId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                feed.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(msg => {
                        appendMessageBubble(msg);
                    });
                    scrollMessagesToBottom();
                } else {
                    feed.innerHTML = `
                        <div class="flex flex-col items-center justify-center h-full text-slate-400 py-12 text-center">
                            <span class="material-symbols-outlined text-4xl text-slate-600 mb-2">forum</span>
                            <span class="text-xs font-bold">لا توجد رسائل سابقة. ابدأ المحادثة الآن!</span>
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.error("Messages history load error: ", err);
                feed.innerHTML = `<div class="text-center text-xs text-red-500 py-6">خطأ في تحميل أرشيف المحادثة.</div>`;
            });
    }

    function markActiveChatAsRead() {
        if (!activeContactId) return;
    }

    function appendMessageBubble(msg) {
        const feed = document.getElementById('messages-feed');
        const isMe = parseInt(msg.sender_id) === parseInt(currentUserId);
        
        const emptyState = feed.querySelector('.text-center');
        if (emptyState && emptyState.innerText.includes('لا توجد رسائل')) {
            feed.innerHTML = '';
        }

        const alignClass = isMe ? 'justify-end' : 'justify-start';
        const bgBubble = isMe 
            ? 'bg-[#2d3748] text-slate-100 chat-bubble-sent shadow-md border border-[#3f3f46]/30' 
            : 'bg-[#27272a] text-slate-100 chat-bubble-received border border-[#3f3f46]/30 shadow-md';

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

        let pendingOverlay = '';
        let pendingProgressHtml = '';
        if (msg.isPending) {
            let pendingLabel = 'جاري رفع الملف...';
            if (fileType.startsWith('image/')) pendingLabel = 'جاري رفع الصورة...';
            else if (fileType.startsWith('video/')) pendingLabel = 'جاري رفع الفيديو...';
            else if (fileType.startsWith('audio/') || msg.message === '[Voice Note]') pendingLabel = 'جاري رفع الصوت...';

            pendingOverlay = `
                <div class="absolute inset-0 bg-slate-900/75 backdrop-blur-[2px] flex flex-col items-center justify-center text-white rounded-xl gap-2 p-3 z-20 select-none transition-all">
                    <div class="w-full bg-white/20 rounded-full h-2 overflow-hidden max-w-[180px]">
                        <div id="overlay-progress-bar-${msg.id}" class="bg-[#f2f20d] h-full rounded-full transition-all duration-150" style="width: 0%;"></div>
                    </div>
                    <span id="overlay-progress-text-${msg.id}" class="text-[10px] font-bold tracking-wide drop-shadow-sm">${pendingLabel} 0%</span>
                </div>
            `;

            pendingProgressHtml = `
                <div class="mt-2.5 w-full min-w-[180px] dir-rtl">
                    <div class="w-full bg-black/20 rounded-full h-2 overflow-hidden border border-white/10">
                        <div id="progress-bar-${msg.id}" class="bg-[#f2f20d] h-full rounded-full transition-all duration-150" style="width: 0%;"></div>
                    </div>
                    <div class="flex items-center justify-between mt-1 text-[10px] font-bold opacity-80">
                        <span id="progress-text-${msg.id}">${pendingLabel} 0%</span>
                        <span class="material-symbols-outlined text-[13px] animate-spin">progress_activity</span>
                    </div>
                </div>
            `;
        }

        let mediaHtml = '';
        if (url) {
            if (fileType.startsWith('image/')) {
                mediaHtml = `
                    <div class="relative mt-2 rounded-xl overflow-hidden border border-white/10 max-w-[260px]">
                        ${pendingOverlay}
                        <img src="${url}" class="w-full object-cover cursor-pointer max-h-52 hover:opacity-95 transition-opacity" onclick="openImageLightbox('${url}')" alt="Attachment">
                    </div>
                `;
            } else if (fileType.startsWith('audio/') || msg.message === '[Voice Note]') {
                const voiceId = 'voice-' + (msg.id || Math.random().toString(36).substring(2, 9));
                mediaHtml = `
                    <div class="relative mt-1 max-w-[270px]">
                        ${pendingOverlay}
                        <div class="flex items-center gap-3 p-3 rounded-2xl bg-[#1c1c22] text-slate-100 min-w-[230px] dir-ltr select-none border border-white/10">
                            <audio id="${voiceId}" src="${url}" preload="metadata" ontimeupdate="updateVoiceProgress('${voiceId}')" onended="resetVoicePlayer('${voiceId}')"></audio>
                            
                            <button type="button" onclick="toggleVoicePlay('${voiceId}')" id="btn-${voiceId}" class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 bg-[#f2f20d] text-black hover:bg-[#d9d90b] transition-all shadow-md active:scale-95">
                                <span class="material-symbols-outlined text-lg" id="icon-${voiceId}">play_arrow</span>
                            </button>

                            <div class="flex-1 flex flex-col justify-center gap-1.5 min-w-0">
                                <div class="relative w-full h-2 bg-white/15 rounded-full overflow-hidden cursor-pointer" onclick="seekVoice('${voiceId}', event)">
                                    <div id="progress-${voiceId}" class="h-full bg-[#f2f20d] rounded-full transition-all duration-100" style="width: 0%;"></div>
                                </div>
                                
                                <div class="flex items-center justify-between text-[10px] font-bold opacity-75 dir-rtl">
                                    <span id="time-${voiceId}">00:00</span>
                                    <span class="flex items-center gap-0.5"><span class="material-symbols-outlined text-[13px]">graphic_eq</span> صوتية</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (fileType.startsWith('video/')) {
                mediaHtml = `
                    <div class="relative mt-2 rounded-xl overflow-hidden max-w-[260px]">
                        ${pendingOverlay}
                        <video src="${url}" controls class="w-full max-h-52">
                        </video>
                    </div>
                `;
            } else {
                const displayName = fileName.substring(0, 20);
                mediaHtml = `
                    <div class="relative mt-2 max-w-[260px]">
                        ${pendingOverlay}
                        <a href="${url}" download="${fileName}" target="_blank" class="flex items-center gap-2 p-2.5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 text-xs font-bold transition-all truncate group/doc">
                            <span class="material-symbols-outlined text-[#f2f20d] shrink-0">description</span>
                            <span class="truncate text-slate-200">${displayName}</span>
                            <span class="material-symbols-outlined text-slate-400 group-hover/doc:text-[#f2f20d] text-sm mr-auto shrink-0 transition-colors">download</span>
                        </a>
                    </div>
                `;
            }
        }

        let textToShow = msg.message || '';
        if (url && (textToShow === '[Attachment]' || textToShow === '[Voice Note]')) {
            textToShow = '';
        }

        const isRead = parseInt(msg.is_read) === 1;
        const checkmarkIcon = msg.isPending
            ? `<span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-400">
                <span class="material-symbols-outlined text-[12px] animate-spin">progress_activity</span>
                <span>جاري الإرسال...</span>
               </span>`
            : (isRead
                ? '<span class="material-symbols-outlined text-[10px] text-blue-400">done_all</span>'
                : '<span class="material-symbols-outlined text-[10px] text-slate-400">done</span>');

        const timerIconHtml = (msg.disappears_after || msg.expires_at) 
            ? `<span class="material-symbols-outlined text-[12px] text-amber-400 shrink-0" title="رسالة ذاتية الاختفاء">timer</span>` 
            : '';

        const bubbleId = msg.id ? `id="msg-${msg.id}"` : '';
        const pendingClass = msg.isPending ? 'opacity-70 message-pending' : '';

        window.activeMessagesData[msg.id] = msg;

        let optionsHtml = '';
        if (!msg.isPending) {
            optionsHtml = `
                <div class="relative group/options flex flex-col justify-center px-1">
                    <button type="button" class="text-slate-500 hover:text-slate-300 p-1 opacity-0 group-hover:opacity-100 transition-opacity focus:opacity-100" onclick="toggleMsgOptions('${msg.id}')">
                        <span class="material-symbols-outlined text-sm">more_vert</span>
                    </button>
                    <div id="msg-options-${msg.id}" class="hidden absolute ${isMe ? 'left-0' : 'right-0'} bottom-8 mb-1 bg-[#1c1c20] rounded-xl shadow-xl border border-[#27272a] py-1 text-xs min-w-[110px] z-[99] flex flex-col">
                        ${isMe && (!msg.attachment && msg.message !== '[Voice Note]') ? `<button type="button" onclick="editMessageInit('${msg.id}')" class="px-3 py-1.5 text-right hover:bg-[#27272a] text-slate-200 w-full">تعديل</button>` : ''}
                        <button type="button" onclick="deleteMessage('${msg.id}')" class="px-3 py-1.5 text-right hover:bg-red-500/20 text-red-400 w-full">حذف</button>
                    </div>
                </div>
            `;
        }

        const activeContactName = document.getElementById('active-contact-name')?.innerText || '';
        const senderLabel = isMe ? '' : (activeContactName ? activeContactName.split(' ')[0] : '');

        const bubbleHtml = `
            <div ${bubbleId} class="group flex ${alignClass} w-full mb-3 ${pendingClass}">
                ${isMe ? optionsHtml : ''}
                <div class="flex flex-col max-w-[75%]">
                    <div class="px-4 py-3 text-xs md:text-sm leading-relaxed ${bgBubble}">
                        ${textToShow ? `<p class="whitespace-pre-line font-medium text-slate-100">${textToShow}</p>` : ''}
                        ${mediaHtml}
                        ${msg.isPending && (!url || (!fileType.startsWith('image/') && !fileType.startsWith('video/') && !fileType.startsWith('audio/') && msg.message !== '[Voice Note]')) ? pendingProgressHtml : ''}
                    </div>
                    <span class="text-[9.5px] text-slate-400 mt-1 px-1 font-semibold flex items-center gap-1.5 ${isMe ? 'self-end' : 'self-start'}">
                        ${senderLabel ? `<span class="text-slate-400 opacity-90">${senderLabel}</span>` : ''}
                        <span>${msgTime}</span>
                        ${timerIconHtml}
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
            fetch('/teacher/messages/' + editingMessageId + '/edit', {
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

        if (currentDisappearsAfter > 0) {
            fd.append('disappears_after', currentDisappearsAfter);
        }

        if (selectedAttachmentFile) {
            fd.append('attachment', selectedAttachmentFile);
        }

        const tempTime = new Date().toISOString();
        const tempId = 'temp_' + Date.now();
        appendMessageBubble({
            id: tempId,
            sender_id: currentUserId,
            receiver_id: activeContactId,
            message: selectedAttachmentFile && text === '' ? '' : text,
            attachment: selectedAttachmentFile ? URL.createObjectURL(selectedAttachmentFile) : null,
            fileObject: selectedAttachmentFile,
            created_at: tempTime,
            isPending: true
        });
        scrollMessagesToBottom();

        input.value = '';
        clearSelectedAttachment();

        const xhr = new XMLHttpRequest();
        xhr.open('POST', "{{ route('teacher.messages.send') }}", true);
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
                    if (data.success) {
                        if (!document.getElementById('msg-' + data.message.id)) {
                            appendMessageBubble(data.message);
                            scrollMessagesToBottom();
                        }
                        loadContactsSilently();
                    } else {
                        alert('فشل في إرسال الرسالة: ' + (data.error || 'خطأ غير معروف'));
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
                const mins = Math.floor(recordDurationSecs / 60).toString().padStart(2, '0');
                const secs = (recordDurationSecs % 60).toString().padStart(2, '0');
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
        document.getElementById('send-btn').classList.replace('bg-[#27272a]', 'bg-blue-600');
        document.getElementById('send-btn').classList.replace('text-[#f2f20d]', 'text-white');
    }

    function cancelEditMode() {
        editingMessageId = null;
        document.getElementById('message-text').value = '';
        document.getElementById('send-btn-icon').innerText = 'send';
        document.getElementById('send-btn').classList.replace('bg-blue-600', 'bg-[#27272a]');
        document.getElementById('send-btn').classList.replace('text-white', 'text-[#f2f20d]');
    }

    function deleteMessage(id) {
        document.getElementById('msg-options-' + id)?.classList.add('hidden');
        const msg = window.activeMessagesData[id];
        const isMe = msg && parseInt(msg.sender_id) === parseInt(currentUserId);

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
        fetch('/teacher/messages/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ type: type })
        }).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                const bubble = document.getElementById('msg-' + id);
                if (bubble) bubble.remove();
                delete window.activeMessagesData[id];
                loadContactsSilently();
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
        const query = document.getElementById('message-search-input').value.trim();
        if (query === '') {
            fetchMessagesFeed(activeContactId);
            return;
        }
        
        fetch(`/teacher/messages/conversation/${activeContactId}/search?q=${encodeURIComponent(query)}`)
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

        if (currentDisappearsAfter > 0) {
            fd.append('disappears_after', currentDisappearsAfter);
        }

        const tempTime = new Date().toISOString();
        const tempId = 'temp_' + Date.now();
        appendMessageBubble({
            id: tempId,
            sender_id: currentUserId,
            receiver_id: activeContactId,
            message: '[Voice Note]',
            attachment: URL.createObjectURL(audioBlob),
            fileObject: audioBlob,
            created_at: tempTime,
            isPending: true
        });
        scrollMessagesToBottom();

        const xhr = new XMLHttpRequest();
        xhr.open('POST', "{{ route('teacher.messages.send') }}", true);
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
                    if (data.success) {
                        if (!document.getElementById('msg-' + data.message.id)) {
                            appendMessageBubble(data.message);
                            scrollMessagesToBottom();
                        }
                        loadContactsSilently();
                    } else {
                        alert('فشل إرسال الملاحظة الصوتية: ' + (data.error || 'خطأ غير معروف'));
                    }
                } catch (e) {
                    alert('حدث خطأ أثناء معالجة استجابة الخادم.');
                }
            } else {
                alert('فشل إرسال الصوت. خطأ الخادم: ' + xhr.status);
            }
        };

        xhr.onerror = function() {
            const pendingBubble = document.getElementById(`msg-${tempId}`);
            if (pendingBubble) {
                pendingBubble.remove();
            }
            alert('فشل إرسال الملاحظة الصوتية. يرجى التحقق من اتصالك بالشبكة.');
        };

        xhr.send(fd);
    }

</script>
@endpush

<!-- ================= IMAGE LIGHTBOX MODAL ================= -->
<div id="image-lightbox-modal" class="fixed inset-0 z-[99999] bg-black/90 backdrop-blur-md hidden flex flex-col items-center justify-between p-4 md:p-6 transition-all duration-300 opacity-0 select-none">
    <div class="w-full flex items-center justify-between text-white max-w-5xl z-10">
        <button type="button" onclick="closeImageLightbox()" class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 text-xs font-bold transition-all text-slate-200 hover:text-white backdrop-blur-sm">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            <span>رجوع للمحادثة</span>
        </button>
        <div class="flex items-center gap-3">
            <a id="lightbox-download-btn" href="#" download target="_blank" class="flex items-center gap-2 px-5 py-2 rounded-full bg-[#f2f20d] text-black font-bold text-xs hover:bg-[#d9d90b] transition-all shadow-lg active:scale-95">
                <span class="material-symbols-outlined text-base">download</span>
                <span>تنزيل الصورة</span>
            </a>
            <button type="button" onclick="closeImageLightbox()" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-all backdrop-blur-sm">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>
    </div>
    
    <div class="flex-1 flex items-center justify-center w-full max-w-5xl my-4 overflow-hidden" onclick="closeImageLightbox()">
        <img id="lightbox-image" src="" alt="صورة المعاينة" class="max-h-[82vh] max-w-full object-contain rounded-2xl shadow-2xl transition-transform duration-300 scale-95" onclick="event.stopPropagation()">
    </div>
</div>

@push('scripts')
<script>
    let currentDisappearsAfter = 0;

    function toggleDisappearingMenu() {
        const menu = document.getElementById('disappearing-menu');
        if (menu) menu.classList.toggle('hidden');
    }

    function setDisappearingTimer(seconds, label) {
        currentDisappearsAfter = seconds;
        const menu = document.getElementById('disappearing-menu');
        if (menu) menu.classList.add('hidden');

        const banner = document.getElementById('disappearing-active-banner');
        const bannerTime = document.getElementById('disappearing-banner-time');
        const btnLabel = document.getElementById('disappearing-btn-label');

        if (seconds > 0) {
            if (banner) banner.classList.remove('hidden');
            if (bannerTime) bannerTime.innerText = label;
            if (btnLabel) btnLabel.innerText = label;
        } else {
            if (banner) banner.classList.add('hidden');
            if (btnLabel) btnLabel.innerText = 'ذاتية الاختفاء';
        }
    }

    function openImageLightbox(url) {
        const modal = document.getElementById('image-lightbox-modal');
        const img = document.getElementById('lightbox-image');
        const dlBtn = document.getElementById('lightbox-download-btn');
        if (!modal || !img) return;

        img.src = url;
        dlBtn.href = url;
        const fileName = (url.split('/').pop() || 'image.jpg').split('?')[0];
        dlBtn.setAttribute('download', fileName);

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            img.classList.remove('scale-95');
        }, 10);
    }

    function closeImageLightbox() {
        const modal = document.getElementById('image-lightbox-modal');
        const img = document.getElementById('lightbox-image');
        if (!modal) return;

        modal.classList.add('opacity-0');
        if (img) img.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            if (img) img.src = '';
        }, 250);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageLightbox();
        }
    });

</script>
@endpush
@endsection
