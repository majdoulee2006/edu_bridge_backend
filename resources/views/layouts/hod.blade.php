<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Bridge | رئيس القسم</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Google Fonts: Cairo & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/hod-style.css') }}?v={{ filemtime(public_path('css/hod-style.css')) }}">
    
@php
    $sysPrimary = \App\Models\SystemSetting::getSetting('primary_color', '#f2f20d');
@endphp
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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

            <!-- HOD Info -->
            <div style="text-align: center; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <div style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--accent-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; font-size: 1.5rem; font-weight: 800; color: #1a1a1a;">
                    {{ mb_substr(auth()->user()->full_name ?? 'ر', 0, 1) }}
                </div>
                <div style="font-weight: 700; font-size: 0.95rem;">{{ auth()->user()->full_name ?? 'رئيس القسم' }}</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">رئيس القسم{{ auth()->user()->department ? ' ' . auth()->user()->department : '' }}</div>
            </div>
            
            <nav class="nav-menu">
                <a href="{{ url('/hod/dashboard') }}" class="nav-item {{ Request::is('hod/dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i>
                    الرئيسية
                </a>
                <a href="{{ url('/hod/leaves') }}" class="nav-item {{ Request::is('hod/leaves') ? 'active' : '' }}" style="position: relative;">
                    <i class="fa-solid fa-calendar-check"></i>
                    طلبات الإجازة
                    @php
                        $pendingLeavesCount = \Illuminate\Support\Facades\DB::table('absence_requests')->where('status', 'pending_hod')->count();
                    @endphp
                    @if($pendingLeavesCount > 0)
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: #ef4444; color: white; border-radius: 50%; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: bold;">{{ $pendingLeavesCount }}</span>
                    @endif
                </a>
                <a href="{{ url('/hod/notifications') }}" class="nav-item {{ Request::is('hod/notifications') ? 'active' : '' }}" style="position: relative;">
                    <i class="fa-solid fa-bell"></i>
                    الإشعارات
                    @php
                        $unreadHodNotifsCount = \Illuminate\Support\Facades\DB::table('notifications')
                            ->where('user_id', auth()->id())
                            ->where('is_read', 0)
                            ->count();
                    @endphp
                    @if($unreadHodNotifsCount > 0)
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: #ef4444; color: white; border-radius: 50%; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: bold;">{{ $unreadHodNotifsCount }}</span>
                    @endif
                </a>
                <a href="{{ url('/hod/messages') }}" class="nav-item {{ Request::is('hod/messages') ? 'active' : '' }}" style="position: relative;">
                    <i class="fa-solid fa-comments"></i>
                    الرسائل
                    @php $unreadMessages = \App\Models\Message::where('receiver_id', auth()->id())->where('is_read', false)->count(); @endphp
                    @if($unreadMessages > 0)
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: #ef4444; color: white; border-radius: 50%; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: bold;">{{ $unreadMessages }}</span>
                    @endif
                </a>
                <a href="{{ url('/hod/profile') }}" class="nav-item {{ Request::is('hod/profile') ? 'active' : '' }}">
                    <i class="fa-solid fa-user"></i>
                    الملف الشخصي
                </a>
                <a href="{{ url('/hod/organization') }}" class="nav-item {{ Request::is('hod/organization') ? 'active' : '' }}">
                    <i class="fa-solid fa-sitemap"></i>
                    التنظيم
                </a>
                <a href="{{ url('/hod/accounts') }}" class="nav-item {{ Request::is('hod/accounts') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i>
                    الحسابات
                </a>
                <a href="{{ url('/hod/reports') }}" class="nav-item {{ Request::is('hod/reports') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-lines"></i>
                    طلب التقارير
                </a>
                <a href="{{ url('/hod/student-services') }}" class="nav-item {{ Request::is('hod/student-services*') ? 'active' : '' }}">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    الخدمات الطلابية
                </a>
                <a href="{{ url('/hod/appointments') }}" class="nav-item {{ Request::is('hod/appointments*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    المواعيد واللقاءات
                </a>
                <a href="{{ url('/hod/settings') }}" class="nav-item {{ Request::is('hod/settings') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i>
                    الإعدادات
                </a>
                
                <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-inline: 1rem;">
                    <form action="{{ route('hod.logout') }}" method="POST">
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
                    <div>
                        <h1 class="page-title">@yield('title')</h1>
                    @hasSection('subtitle')
                        <p class="page-subtitle" style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 0.25rem;">@yield('subtitle')</p>
                    @endif
                    </div>
                </div>
                <div class="header-actions" style="display: flex; align-items: center; gap: 1rem;">
                    @php
                        $unreadHodNotifs = \Illuminate\Support\Facades\DB::table('notifications')
                            ->where('user_id', auth()->id())
                            ->where('is_read', 0)
                            ->count();
                    @endphp
                    <a href="{{ url('/hod/notifications') }}" style="position: relative; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 50%; width: 40px; height: 40px; cursor: pointer; color: var(--text-secondary); font-size: 1.1rem; display: flex; align-items: center; justify-content: center; text-decoration: none;" title="الإشعارات">
                        <i class="fa-solid fa-bell"></i>
                        @if($unreadHodNotifs > 0)
                            <span style="position: absolute; top: 2px; right: 2px; width: 11px; height: 11px; background-color: #ef4444; border-radius: 50%; border: 2px solid var(--bg-secondary); box-shadow: 0 0 8px #ef4444;"></span>
                        @endif
                    </a>
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
                <div id="hod-success-alert" class="global-alert-box" style="background-color: hsl(120, 70%, 95%); color: hsl(120, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; transition: all 0.5s ease;">
                    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div id="hod-error-alert" class="global-alert-box" style="background-color: hsl(0, 70%, 95%); color: hsl(0, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; transition: all 0.5s ease;">
                    <i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}
                </div>
            @endif

            <div class="content-body">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Custom JS -->
    <script src="{{ asset('js/hod-settings.js') }}"></script>
    <script>
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('mobile-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Auto-hide alerts after 5 seconds
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
    </script>
    @stack('scripts')

    @include('partials.logout_modal')
    @include('partials.inactivity_logout')
    @include('partials.web_toast_notifications')
</body>
</html>

