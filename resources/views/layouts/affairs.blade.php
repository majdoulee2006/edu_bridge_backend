<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edu-Bridge | شؤون الطلاب</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Local Fonts: Cairo & Material Symbols (100% Offline) -->
    <link rel="stylesheet" href="{{ asset('css/fonts-local.css') }}">
    
    <!-- Local FontAwesome (100% Offline) -->
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    
    <!-- Shared HOD Style -->
    <link rel="stylesheet" href="{{ asset('css/hod-style.css') }}?v={{ filemtime(public_path('css/hod-style.css')) }}">

@php
    $sysPrimary = \App\Models\SystemSetting::getSetting('primary_color', '#f2f20d');
@endphp
    <!-- Local Tailwind CSS Engine (100% Offline) -->
    <script src="{{ asset('js/tailwind-play.js') }}"></script>
    <script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "{{ $sysPrimary }}",
                    "primary-dark": "{{ $sysPrimary }}",
                    "primary-content": "#1a1a00",
                    "background-light": "#eef0f4",
                    "background-dark": "#000000",
                    "surface-light": "#ffffff",
                    "surface-dark": "#121212",
                    "card-dark": "#181818"
                },
                fontFamily: {
                    "display": ["Cairo", "Lexend", "sans-serif"],
                    "body": ["Cairo", "Lexend", "sans-serif"],
                    "arabic": ["Cairo", "sans-serif"]
                }
            }
        }
    }
    </script>

    <style id="system-theme-style">
        :root {
            --primary-color: {{ $sysPrimary }};
            --color-primary: {{ $sysPrimary }};
            --primary-yellow: {{ $sysPrimary }};
            --accent-color: {{ $sysPrimary }};
        }
        .bg-primary { background-color: {{ $sysPrimary }} !important; }
        .border-primary { border-color: {{ $sysPrimary }} !important; }
        .accent-primary { accent-color: {{ $sysPrimary }} !important; }
        .text-primary { color: {{ $sysPrimary }} !important; }
        .shadow-glow { box-shadow: 0 0 25px {{ $sysPrimary }}66 !important; }
        
        /* Overrides for hardcoded #f2f20d and #F2F20D classes */
        [class*="bg-[#f2f20d]"], [class*="bg-[#F2F20D]"] { background-color: {{ $sysPrimary }} !important; }
        [class*="border-[#f2f20d]"], [class*="border-[#F2F20D]"] { border-color: {{ $sysPrimary }} !important; }
        [class*="accent-[#f2f20d]"], [class*="accent-[#F2F20D]"] { accent-color: {{ $sysPrimary }} !important; }
        [class*="focus:ring-[#f2f20d]"]:focus { --tw-ring-color: {{ $sysPrimary }} !important; }
        [class*="focus:border-[#f2f20d]"]:focus { border-color: {{ $sysPrimary }} !important; }
        [class*="hover:text-[#f2f20d]"]:hover, [class*="hover:text-[#F2F20D]"]:hover { color: {{ $sysPrimary }} !important; }
        
        [class*="bg-[#f2f20d]/"], [class*="bg-[#F2F20D]/"] { background-color: {{ $sysPrimary }}33 !important; }
        [class*="border-[#f2f20d]/"], [class*="border-[#F2F20D]/"] { border-color: {{ $sysPrimary }}4D !important; }

        /* Force True Pitch-Black Theme in Dark Mode (eliminates navy/slate blue hues) */
        html.dark body { background-color: #000000 !important; }
        html.dark .bg-slate-950 { background-color: #000000 !important; }
        html.dark .bg-slate-900 { background-color: #0a0a0a !important; }
        html.dark .bg-slate-800 { background-color: #121212 !important; }
        html.dark .bg-slate-700 { background-color: #1c1c1e !important; }
        
        /* Transparent opacity variants of slate in dark mode */
        html.dark [class*="bg-slate-900/"] { background-color: rgba(10, 10, 10, 0.7) !important; }
        html.dark [class*="bg-slate-800/"] { background-color: rgba(18, 18, 18, 0.6) !important; }
        html.dark [class*="bg-slate-700/"] { background-color: rgba(28, 28, 30, 0.5) !important; }

        /* Dark borders override to neutral dark grey instead of slate blue-grey */
        html.dark .border-slate-800 { border-color: #242424 !important; }
        html.dark .border-slate-700 { border-color: #2a2a2a !important; }
        html.dark [class*="border-slate-700/"] { border-color: rgba(42, 42, 42, 0.5) !important; }
        html.dark [class*="border-slate-800/"] { border-color: rgba(36, 36, 36, 0.5) !important; }
    </style>

    <style>
        /* Mobile specific bottom navigation */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-secondary);
            box-shadow: 0 -4px 10px rgba(0,0,0,0.05);
            z-index: 1000;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            padding: 0.5rem 1rem;
            align-items: center;
            justify-content: space-between;
        }

        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 700;
            gap: 0.25rem;
            position: relative;
        }

        .bottom-nav-item i {
            font-size: 1.25rem;
        }

        .bottom-nav-item.active {
            color: var(--accent-color); /* We will use Yellow as requested */
            text-shadow: 0 2px 4px rgba(252, 227, 0, 0.4);
        }

        .center-btn {
            width: 56px;
            height: 56px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-size: 1.5rem;
            margin-top: -25px;
            box-shadow: 0 4px 10px rgba(252, 227, 0, 0.4);
            cursor: pointer;
            border: 4px solid var(--bg-primary);
        }

        @media (max-width: 768px) {
            .main-content,
            [dir="rtl"] .main-content,
            [dir="ltr"] .main-content {
                margin-right: 0 !important;
                margin-left: 0 !important;
                padding: 1rem !important;
                padding-bottom: 85px !important; /* space for bottom nav */
                width: 100% !important;
            }
            .bottom-nav {
                display: flex !important;
            }
        }
        
        .header-actions .settings-btn {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            color: var(--text-secondary);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        /* ── Notification Dropdown - International Standard ── */
        .notif-dropdown-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .notif-bell-trigger {
            position: relative;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            color: var(--text-secondary);
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        .notif-bell-trigger:hover, .notif-dropdown-wrapper:hover .notif-bell-trigger, .notif-dropdown-wrapper.is-open .notif-bell-trigger {
            background: var(--accent-color);
            color: #1a1a1a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
        }
        .notif-badge-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background-color: #ef4444;
            border-radius: 50%;
            border: 2px solid var(--bg-secondary);
            box-shadow: 0 0 8px #ef4444;
            animation: notifPulse 2s infinite;
        }
        @keyframes notifPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Dropdown Card */
        .notif-dropdown-card {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            width: 350px;
            max-width: calc(100vw - 2rem);
            background: var(--bg-primary, #ffffff);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 1.25rem;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.25), 0 10px 20px -5px rgba(0, 0, 0, 0.1);
            z-index: 999999;
            display: none;
            flex-direction: column;
            overflow: visible;
            transform-origin: top left;
            animation: notifCardIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Continuous Invisible Hover Bridge */
        .notif-dropdown-card::before {
            content: '';
            position: absolute;
            top: -24px;
            left: -40px;
            right: -40px;
            height: 30px;
            background: transparent;
            z-index: 10;
        }

        .notif-dropdown-wrapper:hover .notif-dropdown-card,
        .notif-dropdown-wrapper.is-open .notif-dropdown-card {
            display: flex;
        }

        @keyframes notifCardIn {
            from {
                opacity: 0;
                transform: translateY(-8px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Card Header */
        .notif-card-header {
            padding: 0.85rem 1.1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
            background: var(--bg-secondary, #f9fafb);
        }
        .notif-card-header-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .notif-card-header-title h4 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--text-primary);
        }
        .notif-pill {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 0.15rem 0.55rem;
            border-radius: 1rem;
        }
        .btn-mark-all-read {
            background: transparent;
            border: none;
            color: #3b82f6;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0.2rem 0.4rem;
            border-radius: 6px;
            transition: all 0.2s;
            font-family: inherit;
        }
        .btn-mark-all-read:hover {
            background: rgba(59, 130, 246, 0.1);
            text-decoration: underline;
        }

        /* Card List Body */
        .notif-card-body {
            max-height: 310px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .notif-card-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.8rem 1rem;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
            position: relative;
            text-align: right;
        }
        .notif-card-item:hover {
            background: rgba(0, 0, 0, 0.03);
        }
        [data-theme="dark"] .notif-card-item:hover {
            background: rgba(255, 255, 255, 0.04);
        }
        .notif-card-item.is-unread {
            background: rgba(245, 158, 11, 0.05);
        }
        [data-theme="dark"] .notif-card-item.is-unread {
            background: rgba(245, 158, 11, 0.08);
        }
        .notif-item-icon-wrap {
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 9px;
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .notif-item-content {
            flex: 1;
            min-width: 0;
        }
        .notif-item-title {
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.15rem;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .notif-item-msg {
            font-size: 0.78rem;
            color: var(--text-secondary);
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 0.3rem;
        }
        .notif-item-time {
            font-size: 0.72rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.3rem;
            opacity: 0.8;
        }
        .notif-unread-dot {
            width: 8px;
            height: 8px;
            min-width: 8px;
            border-radius: 50%;
            background-color: #3b82f6;
            margin-top: 6px;
            flex-shrink: 0;
        }

        /* Empty State */
        .notif-card-empty {
            padding: 2.2rem 1rem;
            text-align: center;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .notif-card-empty i {
            font-size: 1.8rem;
            opacity: 0.35;
        }
        .notif-card-empty span {
            font-size: 0.84rem;
            font-weight: 600;
        }

        /* Card Footer */
        .notif-card-footer {
            padding: 0.75rem 1rem;
            background: var(--bg-secondary, #f9fafb);
            border-top: 1px solid var(--border-color, #e5e7eb);
            text-align: center;
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--accent-color, #f59e0b);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .notif-card-footer:hover {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }
    </style>

    @stack('styles')
    <script>
        const colorTheme = localStorage.getItem('color-theme');
        const hodSettings = JSON.parse(localStorage.getItem('hodSettings') || '{}');
        const theme = colorTheme || hodSettings.theme || 'dark';
        if (theme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="brand">
                <i class="fa-solid fa-graduation-cap" style="color: var(--accent-color);"></i>
                Edu-Bridge
            </div>

            <!-- Affairs Info -->
            <div style="text-align: center; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <div style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--accent-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; font-size: 1.5rem; font-weight: 800; color: #1a1a1a;">
                    {{ mb_substr(auth()->user()->full_name ?? 'أ', 0, 1) }}
                </div>
                <div style="font-weight: 700; font-size: 0.95rem;">{{ auth()->user()->full_name ?? 'أحمد محمد' }}</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">موظف شؤون</div>
            </div>
            
            <nav class="nav-menu" style="display: flex; flex-direction: column; flex: 1;">
                <a href="{{ url('/affairs/dashboard') }}" class="nav-item {{ Request::is('affairs/dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i> الرئيسية
                </a>
                <a href="{{ route('affairs.academic_management') }}" class="nav-item {{ Request::is('affairs/academic-management*') ? 'active' : '' }}">
                    <i class="fa-solid fa-graduation-cap"></i> الأعوام والفصول الدراسية
                </a>
                <a href="{{ url('/affairs/calendar') }}" class="nav-item {{ Request::is('affairs/calendar') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i> التقويم
                </a>
                <a href="{{ route('affairs.appointments') }}" class="nav-item {{ Request::is('affairs/appointments*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i> المواعيد واللقاءات
                </a>
                <a href="{{ route('affairs.course_weights') }}" class="nav-item {{ Request::is('affairs/course-weights') ? 'active' : '' }}">
                    <i class="fa-solid fa-route"></i> المسار الأكاديمي الطلابي
                </a>
                <a href="{{ url('/affairs/activities') }}" class="nav-item {{ Request::is('affairs/activities') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-list"></i> الأنشطة
                </a>
                <a href="{{ url('/affairs/accounts') }}" class="nav-item {{ Request::is('affairs/accounts') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i> الحسابات
                </a>
                <a href="{{ url('/affairs/pending-accounts') }}" class="nav-item {{ Request::is('affairs/pending-accounts') ? 'active' : '' }}" style="display:flex; align-items:center; justify-content:space-between;">
                    <span><i class="fa-solid fa-clock"></i> طلبات التسجيل</span>
                    @php $pc = \App\Models\User::whereIn('role_id',[3,4])->where('status','inactive')->count(); @endphp
                    @if($pc > 0)
                        <span style="background:#ef4444; color:white; border-radius:2rem; padding:0.1rem 0.55rem; font-size:0.75rem; font-weight:800;">{{ $pc }}</span>
                    @endif
                </a>
                <a href="{{ url('/affairs/photo-requests') }}" class="nav-item {{ Request::is('affairs/photo-requests') ? 'active' : '' }}" style="display:flex; align-items:center; justify-content:space-between;">
                    <span><i class="fa-solid fa-camera"></i> طلبات الصورة</span>
                    @php $photoReqCount = \Illuminate\Support\Facades\DB::table('photo_change_requests')->where('status', 'pending')->count(); @endphp
                    @if($photoReqCount > 0)
                        <span style="background:#ffcc00; color:#1a1a1a; border-radius:2rem; padding:0.1rem 0.55rem; font-size:0.75rem; font-weight:800;">{{ $photoReqCount }}</span>
                    @endif
                </a>
                <a href="{{ url('/affairs/leaves') }}" class="nav-item {{ Request::is('affairs/leaves') ? 'active' : '' }}" style="display:flex; align-items:center; justify-content:space-between;">
                    <span><i class="fa-solid fa-file-signature"></i> طلبات الإجازة</span>
                    @php $pendingLeavesCount = \Illuminate\Support\Facades\DB::table('absence_requests')->where('status', 'pending_affairs')->count(); @endphp
                    @if($pendingLeavesCount > 0)
                        <span style="background:#ef4444; color:white; border-radius:2rem; padding:0.1rem 0.55rem; font-size:0.75rem; font-weight:800;">{{ $pendingLeavesCount }}</span>
                    @endif
                </a>
                <a href="{{ url('/affairs/student-services') }}" class="nav-item {{ Request::is('affairs/student-services*') ? 'active' : '' }}">
                    <i class="fa-solid fa-boxes-stacked"></i> الخدمات الطلابية
                </a>
                <a href="{{ url('/affairs/messages') }}" class="nav-item {{ Request::is('affairs/messages') ? 'active' : '' }}" style="position: relative;">
                    <i class="fa-solid fa-comments"></i> الرسائل
                    @php $unreadMessages = \App\Models\Message::where('receiver_id', auth()->id())->where('is_read', false)->count(); @endphp
                    @if($unreadMessages > 0)
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: #ef4444; color: white; border-radius: 50%; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: bold;">{{ $unreadMessages }}</span>
                    @endif
                </a>
                <a href="{{ url('/affairs/notifications') }}" class="nav-item {{ Request::is('affairs/notifications') ? 'active' : '' }}" style="position: relative;">
                    <i class="fa-solid fa-bell"></i> الإشعارات
                    @php $unreadCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count(); @endphp
                    @if($unreadCount > 0)
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: #ef4444; color: white; border-radius: 50%; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: bold;">{{ $unreadCount }}</span>
                    @endif
                </a>
                <a href="{{ url('/affairs/profile') }}" class="nav-item {{ Request::is('affairs/profile') ? 'active' : '' }}">
                    <i class="fa-solid fa-user"></i> الملف الشخصي
                </a>
                <a href="{{ url('/affairs/settings') }}" class="nav-item {{ Request::is('affairs/settings') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i> الإعدادات
                </a>
                
                <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-inline: 1rem;">
                    <form action="{{ route('affairs.logout') }}" method="POST">
                        @csrf
                        <button type="button" onclick="triggerLogoutConfirmation(this.closest('form'))" class="nav-item" style="width: 100%; border: none; background: transparent; color: #ef4444; font-weight: 700; cursor: pointer; text-align: right; padding-inline: 0;">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> تسجيل الخروج
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <!-- Mobile Overlay -->
        <div id="mobile-overlay" class="mobile-overlay" onclick="toggleMobileMenu()"></div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <!-- Settings button for top left usually, but we are RTL so top right depending on layout -->
                    <a href="{{ route('affairs.settings') }}" class="settings-btn" title="الإعدادات">
                        <i class="fa-solid fa-gear"></i>
                    </a>
                    <div>
                        <h1 class="page-title">@yield('title')</h1>
                    </div>
                </div>
                <div class="header-actions" style="display: flex; align-items: center; gap: 1rem;">
                    <!-- Notification Bell Dropdown Card -->
                    @php
                        $headerUnread = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
                        $headerRecentNotifs = \App\Models\Notification::where('user_id', auth()->id())
                            ->orderBy('created_at', 'desc')
                            ->limit(6)
                            ->get();
                    @endphp
                    <div class="notif-dropdown-wrapper" id="affairsNotifDropdown">
                        <a href="{{ url('/affairs/notifications') }}" class="notif-bell-trigger" id="notifBellBtn" title="الإشعارات">
                            <i class="fa-solid fa-bell"></i>
                            @if($headerUnread > 0)
                                <span class="notif-badge-dot" id="headerNotifBadgeDot"></span>
                            @endif
                        </a>

                        <div class="notif-dropdown-card" id="notifDropdownCard">
                            <div class="notif-card-header">
                                <div class="notif-card-header-title">
                                    <h4>الإشعارات</h4>
                                    @if($headerUnread > 0)
                                        <span class="notif-pill" id="notifUnreadChip">{{ $headerUnread }} جديدة</span>
                                    @endif
                                </div>
                                @if($headerUnread > 0)
                                    <button type="button" class="btn-mark-all-read" id="btnMarkAllHeaderNotif" onclick="markAllHeaderNotificationsAsRead(event)">
                                        تحديد الكل كمقروء
                                    </button>
                                @endif
                            </div>

                            <div class="notif-card-body" id="headerNotifListBody">
                                @forelse($headerRecentNotifs as $n)
                                    <a href="{{ url('/affairs/notifications') }}" 
                                       class="notif-card-item {{ !$n->is_read ? 'is-unread' : '' }}" 
                                       id="header-notif-item-{{ $n->id }}"
                                       onclick="markSingleHeaderNotifAsRead(event, {{ $n->id }}, '{{ url('/affairs/notifications') }}')">
                                        <div class="notif-item-icon-wrap">
                                            <i class="fa-solid fa-bell"></i>
                                        </div>
                                        <div class="notif-item-content">
                                            <div class="notif-item-title">{{ $n->title }}</div>
                                            <div class="notif-item-msg">{{ Str::limit($n->message, 70) }}</div>
                                            <div class="notif-item-time">
                                                <i class="fa-regular fa-clock"></i>
                                                <span>{{ $n->created_at?->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                        @if(!$n->is_read)
                                            <span class="notif-unread-dot"></span>
                                        @endif
                                    </a>
                                @empty
                                    <div class="notif-card-empty">
                                        <i class="fa-regular fa-bell-slash"></i>
                                        <span>لا توجد إشعارات حالياً</span>
                                    </div>
                                @endforelse
                            </div>

                            <a href="{{ url('/affairs/notifications') }}" class="notif-card-footer">
                                <span>عرض كافة الإشعارات</span>
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                    <!-- Dark Mode Toggle -->
                    <button onclick="toggleDarkMode()" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 50%; width: 40px; height: 40px; cursor: pointer; color: var(--text-secondary); font-size: 1.1rem; display: flex; align-items: center; justify-content: center;" title="تبديل الوضع">
                        <i class="fa-solid fa-moon" id="dark-mode-icon"></i>
                    </button>
                    <!-- Language Toggle -->
                    <button onclick="toggleLanguage()" title="تبديل اللغة" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 2rem; height: 40px; padding: 0 1rem; cursor: pointer; color: var(--text-secondary); font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 0.4rem; font-family: inherit;">
                        <i class="fa-solid fa-globe"></i>
                        <span id="lang-btn-text">EN</span>
                    </button>
                </div>
            </header>

            @if (session('success'))
                <div class="global-alert-box" style="background-color: hsl(120, 70%, 95%); color: hsl(120, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; transition: all 0.5s ease;">
                    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="global-alert-box" style="background-color: hsl(0, 70%, 95%); color: hsl(0, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; transition: all 0.5s ease;">
                    <i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}
                </div>
            @endif

            <div class="content-body">
                @yield('content')
            </div>
        </main>

        <!-- Mobile Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="{{ route('affairs.dashboard') }}" class="bottom-nav-item {{ Request::is('affairs/dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i>
                <span>الرئيسية</span>
            </a>
            <a href="{{ route('affairs.profile') }}" class="bottom-nav-item {{ Request::is('affairs/profile') ? 'active' : '' }}">
                <i class="fa-solid fa-user"></i>
                <span>الملف الشخصي</span>
            </a>
            
            <div class="center-btn" onclick="toggleMobileMenu()">
                <i class="fa-solid fa-border-all"></i>
            </div>
            
            <a href="{{ route('affairs.notifications') }}" class="bottom-nav-item {{ Request::is('affairs/notifications') ? 'active' : '' }}">
                <i class="fa-solid fa-bell"></i>
                <span>الإشعارات</span>
                <span style="position: absolute; top: -5px; right: 5px; background: #ef4444; width: 8px; height: 8px; border-radius: 50%;"></span>
            </a>
            <a href="{{ route('affairs.messages') }}" class="bottom-nav-item {{ Request::is('affairs/messages') ? 'active' : '' }}">
                <i class="fa-solid fa-envelope"></i>
                <span>الدردشات</span>
            </a>
        </nav>
    </div>

    <!-- Shared JS -->
    <script src="{{ asset('js/hod-settings.js') }}"></script>
    <script>
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('mobile-overlay');

            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            // Lock body scroll when sidebar is open so only sidebar scrolls
            document.body.classList.toggle('sidebar-body-lock', sidebar.classList.contains('active'));
        }

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.global-alert-box');
                alerts.forEach(function(alert) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(function() { alert.remove(); }, 500);
                });
            }, 5000);
        });

        // Notification Header Dropdown functions
        function markAllHeaderNotificationsAsRead(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            fetch("{{ route('affairs.notifications.read_all') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token,
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            }).then(res => res.json()).then(data => {
                document.querySelectorAll('#headerNotifListBody .notif-card-item').forEach(el => {
                    el.classList.remove('is-unread');
                    const dot = el.querySelector('.notif-unread-dot');
                    if (dot) dot.remove();
                });
                const badge = document.getElementById('headerNotifBadgeDot');
                if (badge) badge.remove();
                const unreadChip = document.getElementById('notifUnreadChip');
                if (unreadChip) unreadChip.remove();
                const btnMarkAll = document.getElementById('btnMarkAllHeaderNotif');
                if (btnMarkAll) btnMarkAll.remove();
            }).catch(err => {
                console.error("Error marking notifications as read:", err);
            });
        }

        function markSingleHeaderNotifAsRead(e, id, targetUrl) {
            if (e) e.preventDefault();
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            fetch(`/affairs/notifications/${id}/read`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token,
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            }).finally(() => {
                if (targetUrl) {
                    window.location.href = targetUrl;
                }
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('affairsNotifDropdown');
            if (dropdown && !dropdown.contains(e.target)) {
                dropdown.classList.remove('is-open');
            }
        });

        // Hover grace delay: Keeps dropdown open while moving mouse freely over notifications
        (function() {
            const dropdownWrap = document.getElementById('affairsNotifDropdown');
            let timer = null;

            if (dropdownWrap) {
                dropdownWrap.addEventListener('mouseenter', function() {
                    if (timer) clearTimeout(timer);
                    dropdownWrap.classList.add('is-open');
                });

                dropdownWrap.addEventListener('mouseleave', function() {
                    if (timer) clearTimeout(timer);
                    timer = setTimeout(function() {
                        dropdownWrap.classList.remove('is-open');
                    }, 450); // 450ms smooth grace delay
                });
            }
        })();
    </script>
    @stack('scripts')

    @include('partials.logout_modal')
    @include('partials.inactivity_logout')
    @include('partials.web_toast_notifications')
</body>
</html>

