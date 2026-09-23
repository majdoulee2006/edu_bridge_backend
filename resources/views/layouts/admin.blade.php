<!DOCTYPE html>
<html class="dark" data-theme="dark" dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edu-Bridge | @yield('title', 'الإدارة')</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Local Fonts: Cairo & Material Symbols (100% Offline) -->
    <link rel="stylesheet" href="{{ asset('css/fonts-local.css') }}">
    
    <!-- Local FontAwesome icons (100% Offline) -->
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">

    <!-- Shared HOD/Affairs responsive styles -->
    <link rel="stylesheet" href="{{ asset('css/hod-style.css') }}?v={{ filemtime(public_path('css/hod-style.css')) }}">

    <!-- Local Tailwind CSS Engine (100% Offline) -->
    <script src="{{ asset('js/tailwind-play.js') }}"></script>
@php
    $sysPrimary = \App\Models\SystemSetting::getSetting('primary_color', '#f2f20d');
@endphp
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
                },
                borderRadius: {
                    "DEFAULT": "1rem", "lg": "1.5rem", "xl": "2rem",
                    "2xl": "2.5rem", "full": "9999px"
                },
                boxShadow: {
                    "soft": "0 4px 20px -2px rgba(0,0,0,0.06)",
                    "glow": "0 0 25px rgba(242,242,13,0.4)",
                    "fab": "0 4px 20px rgba(0,0,0,0.4)"
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

    <script>
        // Immediately set the theme to avoid flicker
        // Resolve theme: check both keys so all layouts share the preference
        (function() {
            var colorTheme  = localStorage.getItem('color-theme');
            var hodRaw      = localStorage.getItem('hodSettings');
            var hodTheme    = hodRaw ? (JSON.parse(hodRaw).theme || null) : null;
            var resolved    = colorTheme || hodTheme;
            var isDark      = resolved ? (resolved === 'dark') : true;
            var theme       = isDark ? 'dark' : 'light';
            // Ensure both keys agree
            localStorage.setItem('color-theme', theme);
            if (hodRaw) {
                var h = JSON.parse(hodRaw); h.theme = theme;
                localStorage.setItem('hodSettings', JSON.stringify(h));
            }
            document.documentElement.classList.toggle('dark',  isDark);
            document.documentElement.classList.toggle('light', !isDark);
            document.documentElement.setAttribute('data-theme', theme);
        })();

        // Immediately load custom font size to prevent layout shift
        const savedFontSize = localStorage.getItem('app-font-size');
        if (savedFontSize) {
            document.documentElement.style.fontSize = savedFontSize + 'px';
        }
    </script>

    <style>
        body { font-family: 'Cairo', 'Lexend', sans-serif; min-height: 100vh; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Alert toast messages */
        .alert-toast {
            animation: slideDown 0.4s ease forwards;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Admin Bottom Nav (mobile) same as affairs ── */
        .admin-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-secondary, #1a1a1a);
            box-shadow: 0 -4px 10px rgba(0,0,0,0.15);
            z-index: 1000;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            padding: 0.5rem 0.75rem;
            align-items: center;
            justify-content: space-between;
        }
        .admin-bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 700;
            gap: 0.2rem;
            position: relative;
            padding: 0.3rem 0.5rem;
        }
        .admin-bottom-nav-item i { font-size: 1.2rem; }
        .admin-bottom-nav-item.active { color: var(--primary-color, #f2f20d); }
        .admin-center-btn {
            width: 52px;
            height: 52px;
            background: var(--primary-color, #f2f20d);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-size: 1.4rem;
            margin-top: -22px;
            box-shadow: 0 4px 12px rgba(242,242,13,0.5);
            cursor: pointer;
            border: 3px solid #000;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .admin-bottom-nav { display: flex !important; }
            /* Push content above bottom nav */
            .admin-main-content {
                padding-bottom: 85px !important;
            }
            /* Sidebar stops ABOVE the bottom nav, floats over it */
            #sidebar {
                bottom: 70px !important;
                border-bottom-right-radius: 20px !important;
                border-bottom-left-radius: 20px !important;
            }
        }

        /* Notification Dropdown Component */
        .notif-dropdown-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .notif-bell-trigger {
            position: relative;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            color: #64748b;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .dark .notif-bell-trigger {
            background: #1f1f1f;
            border-color: #2a2a2a;
            color: #94a3b8;
        }
        .notif-bell-trigger:hover, .notif-dropdown-wrapper:hover .notif-bell-trigger, .notif-dropdown-wrapper.is-open .notif-bell-trigger {
            background: var(--primary, #f2f20d);
            color: #101924;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(242, 242, 13, 0.3);
        }
        .notif-badge-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background-color: #ef4444;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 0 8px #ef4444;
            animation: notifPulse 2s infinite;
        }
        .dark .notif-badge-dot {
            border-color: #1f1f1f;
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
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.15), 0 10px 20px -5px rgba(0, 0, 0, 0.05);
            z-index: 999999;
            display: none;
            flex-direction: column;
            overflow: visible;
            transform-origin: top left;
            animation: notifCardIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .dark .notif-dropdown-card {
            background: #121212;
            border-color: #2a2a2a;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.5), 0 10px 20px -5px rgba(0, 0, 0, 0.25);
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
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .dark .notif-card-header {
            border-color: #2a2a2a;
            background: #181818;
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
            color: #1e293b;
        }
        .dark .notif-card-header-title h4 {
            color: #f1f5f9;
        }
        .notif-pill {
            background: rgba(242, 242, 13, 0.2);
            color: #b45309;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 0.15rem 0.55rem;
            border-radius: 1rem;
        }
        .dark .notif-pill {
            color: var(--primary, #f2f20d);
            background: rgba(242, 242, 13, 0.15);
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
            border-bottom: 1px solid #f1f5f9;
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
            position: relative;
            text-align: right;
        }
        .dark .notif-card-item {
            border-color: #2a2a2a;
        }
        .notif-card-item:hover {
            background: rgba(0, 0, 0, 0.03);
        }
        .dark .notif-card-item:hover {
            background: rgba(255, 255, 255, 0.04);
        }
        .notif-card-item.is-unread {
            background: rgba(242, 242, 13, 0.05);
        }
        .notif-item-icon-wrap {
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 9px;
            background: rgba(242, 242, 13, 0.15);
            color: #b45309;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .dark .notif-item-icon-wrap {
            color: var(--primary, #f2f20d);
            background: rgba(242, 242, 13, 0.12);
        }
        .notif-item-content {
            flex: 1;
            min-width: 0;
        }
        .notif-item-title {
            font-size: 0.84rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.15rem;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .notif-item-title {
            color: #f1f5f9;
        }
        .notif-item-msg {
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 0.3rem;
        }
        .dark .notif-item-msg {
            color: #94a3b8;
        }
        .notif-item-time {
            font-size: 0.72rem;
            color: #94a3b8;
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
            color: #94a3b8;
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
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 0.84rem;
            font-weight: 700;
            color: #b45309;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .dark .notif-card-footer {
            background: #181818;
            border-color: #2a2a2a;
            color: var(--primary, #f2f20d);
        }
        .notif-card-footer:hover {
            background: rgba(242, 242, 13, 0.1);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-100 min-h-screen overflow-x-hidden selection:bg-primary selection:text-primary-content font-display transition-colors duration-300">

    <div class="flex min-h-screen">
        <!-- Mobile Sidebar Overlay -->
        <div id="mobile-overlay" class="mobile-overlay" onclick="toggleMobileMenu()"></div>

        {{-- ======= SIDEBAR ======= --}}
        <aside id="sidebar" class="sidebar">
            <!-- Brand -->
            <div class="brand">
                <i class="fa-solid fa-graduation-cap" style="color: var(--accent-color);"></i>
                Edu-Bridge
            </div>

            <!-- Admin Info -->
            <div style="text-align: center; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <div style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--accent-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; font-size: 1.5rem; font-weight: 800; color: #1a1a1a;">
                    {{ mb_substr(Auth::user()->full_name ?? 'إ', 0, 1) }}
                </div>
                <div style="font-weight: 700; font-size: 0.95rem;">{{ Auth::user()->full_name ?? 'إدارة المعهد التقني' }}</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">مدير عام</div>
            </div>

            <!-- Navigation Links -->
            <nav class="nav-menu">
                {{-- Dashboard --}}
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ Request::is('admin/dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i> الرئيسية
                </a>

                {{-- Accounts --}}
                <a href="{{ route('admin.accounts') }}" class="nav-item {{ Request::is('admin/accounts*') ? 'active' : '' }}">
                    <i class="fa-solid fa-address-card"></i> الحسابات
                </a>

                {{-- Courses --}}
                <a href="{{ route('admin.courses') }}" class="nav-item {{ Request::is('admin/courses*') ? 'active' : '' }}">
                    <i class="fa-solid fa-graduation-cap"></i> الدورات
                </a>

                {{-- Semesters --}}
                <a href="{{ route('admin.semesters-subjects') }}" class="nav-item {{ Request::is('admin/semesters-subjects*') ? 'active' : '' }}">
                    <i class="fa-solid fa-book-bookmark"></i> الفصول والمواد
                </a>

                {{-- Lectures --}}
                <a href="{{ route('admin.lectures') }}" class="nav-item {{ Request::is('admin/lectures*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chalkboard-user"></i> المحاضرات
                </a>

                {{-- Reports --}}
                <a href="{{ route('admin.reports') }}" class="nav-item {{ Request::is('admin/reports*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-simple"></i> التقارير
                </a>

                {{-- Student Services --}}
                <a href="{{ route('admin.student_services') }}" class="nav-item {{ Request::is('admin/student-services*') ? 'active' : '' }}">
                    <i class="fa-solid fa-boxes-stacked"></i> الخدمات الطلابية
                </a>

                {{-- Appointments --}}
                <a href="{{ route('admin.appointments') }}" class="nav-item {{ Request::is('admin/appointments*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i> المواعيد واللقاءات
                </a>

                {{-- Notifications --}}
                <a href="{{ route('admin.notifications') }}" class="nav-item {{ Request::is('admin/notifications*') ? 'active' : '' }}" style="position: relative;">
                    <i class="fa-solid fa-bell"></i> الإشعارات
                    @php $unread = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count(); @endphp
                    @if($unread > 0)
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: #ef4444; color: white; border-radius: 50%; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: bold;">{{ $unread }}</span>
                    @endif
                </a>

                {{-- Messages --}}
                <a href="{{ route('admin.messages') }}" class="nav-item {{ Request::is('admin/messages*') ? 'active' : '' }}" style="position: relative;">
                    <i class="fa-solid fa-comments"></i> الرسائل
                    @php $unreadMessages = \App\Models\Message::where('receiver_id', auth()->id())->where('is_read', false)->count(); @endphp
                    @if($unreadMessages > 0)
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: #ef4444; color: white; border-radius: 50%; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: bold;">{{ $unreadMessages }}</span>
                    @endif
                </a>

                {{-- Profile --}}
                <a href="{{ route('admin.profile') }}" class="nav-item {{ Request::is('admin/profile*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user"></i> الملف الشخصي
                </a>

                {{-- Settings --}}
                <a href="{{ route('admin.settings') }}" class="nav-item {{ Request::is('admin/settings*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i> الإعدادات
                </a>

                {{-- Activity Logs --}}
                <a href="{{ route('admin.activity_logs') }}" class="nav-item {{ Request::is('admin/activity-logs*') ? 'active' : '' }}">
                    <i class="fa-solid fa-shield-halved"></i> سجل النشاطات والأمان
                </a>

                <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-inline: 1rem;">
                    <form id="admin-logout-form" action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="button" onclick="triggerLogoutConfirmation(this.closest('form'))" class="nav-item" style="width: 100%; border: none; background: transparent; color: #ef4444; font-weight: 700; cursor: pointer; text-align: right; padding-inline: 0;">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> تسجيل الخروج
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        {{-- ======= MAIN CONTENT AREA ======= --}}
        <div class="main-content flex-col min-h-screen">
            <!-- Top Bar Header -->
            <header class="header bg-white dark:bg-[#121212] border-b border-slate-200 dark:border-slate-800/80 px-4 md:px-8 py-4 flex items-center justify-between sticky top-0 z-20 transition-colors">
                <div class="flex items-center gap-3">
                    <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h1 class="page-title text-slate-800 dark:text-white" style="font-size:1rem; font-weight:800;">
                        أهلاً، {{ Auth::user()->full_name ?? 'إدارة المعهد التقني' }} 👋
                    </h1>
                </div>
                
                <div class="header-actions" style="display:flex; align-items:center; gap:0.75rem;">
                    <!-- Notification Bell Dropdown Card -->
                    @php
                        $headerUnread = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
                        $headerRecentNotifs = \App\Models\Notification::where('user_id', auth()->id())
                            ->orderBy('created_at', 'desc')
                            ->limit(6)
                            ->get();
                    @endphp
                    <div class="notif-dropdown-wrapper" id="adminNotifDropdown">
                        <a href="{{ route('admin.notifications') }}" class="notif-bell-trigger" id="adminNotifBellBtn" title="الإشعارات">
                            <i class="fa-solid fa-bell"></i>
                            @if($headerUnread > 0)
                                <span class="notif-badge-dot" id="headerNotifBadgeDot"></span>
                            @endif
                        </a>

                        <div class="notif-dropdown-card" id="adminNotifDropdownCard">
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
                                    <a href="{{ route('admin.notifications') }}" 
                                       class="notif-card-item {{ !$n->is_read ? 'is-unread' : '' }}" 
                                       id="header-notif-item-{{ $n->id }}"
                                       onclick="markSingleHeaderNotifAsRead(event, {{ $n->id }}, '{{ route('admin.notifications') }}')">
                                        <div class="notif-item-icon-wrap">
                                            <i class="fa-solid fa-bell"></i>
                                        </div>
                                        <div class="notif-item-content">
                                            <div class="notif-item-title">{{ $n->title }}</div>
                                            <div class="notif-item-msg">{{ Str::limit($n->message ?? $n->body, 70) }}</div>
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

                            <a href="{{ route('admin.notifications') }}" class="notif-card-footer">
                                <span>عرض كافة الإشعارات</span>
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Theme Toggle Switch -->
                    <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-[#1f1f1f] flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-[#2a2a2a] transition-all">
                        <i class="fa-solid fa-moon text-base dark:hidden"></i>
                        <i class="fa-solid fa-sun text-base hidden dark:inline"></i>
                    </button>

                    {{-- Profile Avatar info --}}
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#f2f20d] flex items-center justify-center font-black text-sm text-[#101924] shadow-glow">
                            إ
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Toast Messages -->
            @if (session('success'))
                <div class="px-8 pt-4 alert-toast global-alert-box" style="transition: all 0.5s ease;">
                    <div class="flex items-center gap-3 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                        <i class="fa-solid fa-circle-check text-lg"></i>
                        <span class="text-sm font-semibold">{{ session('success') }}</span>
                    </div>
                </div>
            @endif
            @if (session('error'))
                <div class="px-8 pt-4 alert-toast global-alert-box" style="transition: all 0.5s ease;">
                    <div class="flex items-center gap-3 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/30 text-rose-700 dark:text-rose-400">
                        <i class="fa-solid fa-circle-xmark text-lg"></i>
                        <span class="text-sm font-semibold">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Body Grid Container -->
            <main class="admin-main-content flex-1 p-4 md:p-8 flex flex-col gap-6 w-full mx-auto">
                @if (View::hasSection('header-title'))
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            @if (View::hasSection('header-actions'))
                                <div class="flex items-center shrink-0">
                                    @yield('header-actions')
                                </div>
                            @endif
                            <div class="flex flex-col">
                                <h2 class="text-xl font-bold text-slate-800 dark:text-white leading-tight">
                                    @yield('header-title')
                                </h2>
                                @if (View::hasSection('header-subtitle'))
                                    <span class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                        @yield('header-subtitle')
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Mobile Bottom Navigation (Admin) -->
    <nav class="admin-bottom-nav">
        <a href="{{ route('admin.dashboard') }}" class="admin-bottom-nav-item {{ Request::is('admin/dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i>
            <span>الرئيسية</span>
        </a>
        <a href="{{ route('admin.profile') }}" class="admin-bottom-nav-item {{ Request::is('admin/profile*') ? 'active' : '' }}">
            <i class="fa-solid fa-user"></i>
            <span>الملف الشخصي</span>
        </a>
        <div class="admin-center-btn" onclick="toggleMobileMenu()">
            <i class="fa-solid fa-border-all"></i>
        </div>
        <a href="{{ route('admin.notifications') }}" class="admin-bottom-nav-item {{ Request::is('admin/notifications*') ? 'active' : '' }}" style="position:relative;">
            <i class="fa-solid fa-bell"></i>
            <span>الإشعارات</span>
            @if(isset($headerUnread) && $headerUnread > 0)
                <span style="position:absolute;top:-2px;right:6px;background:#ef4444;width:8px;height:8px;border-radius:50%;"></span>
            @endif
        </a>
        <a href="{{ route('admin.messages') }}" class="admin-bottom-nav-item {{ Request::is('admin/messages*') ? 'active' : '' }}">
            <i class="fa-solid fa-envelope"></i>
            <span>الرسائل</span>
        </a>
    </nav>

    @stack('scripts')

    <script>
        function confirmLogout() {
            document.getElementById('logout-confirm-modal').classList.remove('hidden');
        }

        // Theme toggle logic
        const themeToggleBtn = document.getElementById('theme-toggle');

        // Mobile Sidebar Toggle – same mechanism as affairs/hod
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
            const overlay = document.getElementById('mobile-overlay');
            if (!sidebar || !overlay) return;
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            // Lock body scroll so only sidebar scrolls on mobile
            document.body.classList.toggle('sidebar-body-lock', sidebar.classList.contains('active'));
        }

        themeToggleBtn.addEventListener('click', function() {
            var isDark  = document.documentElement.classList.contains('dark');
            var newTheme = isDark ? 'light' : 'dark';
            document.documentElement.classList.toggle('dark',  !isDark);
            document.documentElement.classList.toggle('light',  isDark);
            // Save to both keys so HOD/teacher/other layouts pick it up
            localStorage.setItem('color-theme', newTheme);
            var hodRaw = localStorage.getItem('hodSettings');
            var hod = hodRaw ? JSON.parse(hodRaw) : { theme: 'light', lang: 'ar', fontSize: '16' };
            hod.theme = newTheme;
            localStorage.setItem('hodSettings', JSON.stringify(hod));
        });

        // Language Toggle for Admin
        function toggleAdminLanguage() {}

        // Auto-dismiss alerts after 5 seconds
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

        // Global Anti-Double-Click Form Protection for Admin Portal
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form || form.dataset.submitting === 'true') {
                e.preventDefault();
                return false;
            }
            form.dataset.submitting = 'true';
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                setTimeout(function() {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                }, 20);
            }
        });

        // Notification Header Dropdown functions for Admin
        function markAllHeaderNotificationsAsRead(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            fetch("{{ route('admin.notifications.read_all') }}", {
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
                console.error("Error marking all notifications as read:", err);
            });
        }

        function markSingleHeaderNotifAsRead(e, id, targetUrl) {
            if (e) e.preventDefault();
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            fetch(`/admin/notifications/${id}/read`, {
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
            const dropdown = document.getElementById('adminNotifDropdown');
            if (dropdown && !dropdown.contains(e.target)) {
                dropdown.classList.remove('is-open');
            }
        });

        // Hover grace delay
        (function() {
            const dropdownWrap = document.getElementById('adminNotifDropdown');
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
                    }, 450);
                });
            }
        })();
    </script>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; font-family:'Cairo',sans-serif;">
    <div style="background:#fff; border-radius:20px; padding:2rem; max-width:380px; width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.2); animation:fadeIn 0.2s ease;">
        <div style="width:64px;height:64px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <i class="fa-solid fa-arrow-right-from-bracket" style="font-size:1.5rem;color:#ef4444;"></i>
        </div>
        <h3 style="font-size:1.2rem;font-weight:800;color:#1a2633;margin-bottom:0.5rem;">تسجيل الخروج</h3>
        <p style="color:#888;font-size:0.9rem;margin-bottom:1.5rem;">هل أنت متأكد أنك تريد تسجيل الخروج من حسابك؟</p>
        <div style="display:flex;gap:0.8rem;justify-content:center;">
            <button onclick="closeLogoutModal()" style="flex:1;padding:0.8rem;border-radius:12px;border:2px solid #e5e7eb;background:#fff;font-weight:700;font-size:0.95rem;cursor:pointer;color:#555;font-family:'Cairo',sans-serif;">
                لا، تراجع
            </button>
            <button onclick="confirmLogout()" style="flex:1;padding:0.8rem;border-radius:12px;border:none;background:#ef4444;color:#fff;font-weight:700;font-size:0.95rem;cursor:pointer;font-family:'Cairo',sans-serif;">
                نعم، خروج
            </button>
        </div>
    </div>
</div>

    @include('partials.logout_modal')
    @include('partials.inactivity_logout')
    @include('partials.web_toast_notifications')
</body>
</html>

