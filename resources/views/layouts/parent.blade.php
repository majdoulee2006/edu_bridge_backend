<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Bridge | ولي الأمر</title>

    <!-- Google Fonts: Cairo & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Shared HOD Style -->
    <link rel="stylesheet" href="{{ asset('css/hod-style.css') }}?v={{ filemtime(public_path('css/hod-style.css')) }}">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#f2f20d",
                    "primary-dark": "#d9d905",
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

            <!-- Parent Info -->
            <div style="text-align: center; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <div style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--accent-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; font-size: 1.5rem; font-weight: 800; color: #1a1a1a;">
                    {{ mb_substr(auth()->user()->full_name ?? 'و', 0, 1) }}
                </div>
                <div style="font-weight: 700; font-size: 0.95rem;">{{ auth()->user()->full_name ?? 'ولي الأمر' }}</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">ولي أمر</div>
            </div>
            <nav class="nav-menu" style="display: flex; flex-direction: column; flex: 1;">
                <a href="{{ url('/parent/dashboard') }}" class="nav-item {{ Request::is('parent/dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i>
                    الرئيسية
                </a>
                <a href="{{ url('/parent/schedule') }}" class="nav-item {{ Request::is('parent/schedule') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    الجدول الدراسي
                </a>
                <a href="{{ url('/parent/assignments') }}" class="nav-item {{ Request::is('parent/assignments') ? 'active' : '' }}">
                    <i class="fa-solid fa-book-open"></i>
                    الواجبات
                </a>
                <a href="{{ url('/parent/grades') }}" class="nav-item {{ Request::is('parent/grades') ? 'active' : '' }}">
                    <i class="fa-solid fa-graduation-cap"></i>
                    الدرجات والتقييمات
                </a>
                <a href="{{ url('/parent/permissions') }}" class="nav-item {{ Request::is('parent/permissions') ? 'active' : '' }}">
                    <i class="fa-solid fa-envelope-open-text"></i>
                    الأذونات والطلبات
                </a>
                <a href="{{ url('/parent/appointments') }}" class="nav-item {{ Request::is('parent/appointments*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    المواعيد والاستدعاءات
                </a>
                <a href="{{ url('/parent/reports') }}" class="nav-item {{ Request::is('parent/reports') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    تقارير الأداء
                </a>
                <a href="{{ url('/parent/messages') }}" class="nav-item {{ Request::is('parent/messages*') ? 'active' : '' }}">
                    <i class="fa-solid fa-comments"></i>
                    الرسائل
                </a>
                <a href="{{ url('/parent/notifications') }}" class="nav-item {{ Request::is('parent/notifications*') ? 'active' : '' }}" style="position: relative;">
                    <i class="fa-solid fa-bell"></i>
                    الإشعارات
                    @php
                        $unreadParentNotifs = \Illuminate\Support\Facades\DB::table('notifications')
                            ->where('user_id', auth()->id())
                            ->where('is_read', 0)
                            ->count();
                    @endphp
                    @if($unreadParentNotifs > 0)
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: #ef4444; color: white; border-radius: 50%; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: bold;">{{ $unreadParentNotifs }}</span>
                    @endif
                </a>
                <a href="{{ url('/parent/profile') }}" class="nav-item {{ Request::is('parent/profile') ? 'active' : '' }}">
                    <i class="fa-solid fa-user"></i>
                    الملف الشخصي
                </a>

                <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-inline: 1rem;">
                    <form action="{{ route('parent.logout') }}" method="POST">
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
                    @if(isset($parent_children) && $parent_children->isNotEmpty())
                        <form action="{{ route('parent.select_child') }}" method="POST" id="select-child-form" style="margin: 0;">
                            @csrf
                            <div style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg-secondary); border: 1px solid var(--border-color); padding: 0.25rem 0.75rem; border-radius: 2rem;">
                                <i class="fa-solid fa-child" style="color: var(--accent-color);"></i>
                                <select name="student_id" onchange="document.getElementById('select-child-form').submit()" style="background: transparent; color: var(--text-primary); border: none; font-family: inherit; font-size: 0.85rem; font-weight: 700; outline: none; cursor: pointer; padding-left: 0.5rem;">
                                    @foreach($parent_children as $c)
                                        <option value="{{ $c->student_id }}" {{ $selected_child_id == $c->student_id ? 'selected' : '' }}>
                                            {{ $c->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    @endif

                    <a href="{{ url('/parent/notifications') }}" style="position: relative; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 50%; width: 40px; height: 40px; cursor: pointer; color: var(--text-secondary); font-size: 1.1rem; display: flex; align-items: center; justify-content: center; text-decoration: none;" title="الإشعارات">
                        <i class="fa-solid fa-bell"></i>
                        @if(isset($unread_notifications_count) && $unread_notifications_count > 0)
                            <span style="position: absolute; top: 2px; right: 2px; width: 11px; height: 11px; background-color: #ef4444; border-radius: 50%; border: 2px solid var(--bg-secondary); box-shadow: 0 0 8px #ef4444;"></span>
                        @endif
                    </a>

                    <button onclick="toggleDarkMode()" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 50%; width: 40px; height: 40px; cursor: pointer; color: var(--text-secondary); font-size: 1.1rem; display: flex; align-items: center; justify-content: center;" title="تبديل الوضع">
                        <i class="fa-solid fa-moon" id="dark-mode-icon"></i>
                    </button>
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
            @if($errors->any())
                <div class="global-alert-box" style="background-color: hsl(0, 70%, 95%); color: hsl(0, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; transition: all 0.5s ease;">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="content-body">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Shared JS -->
    <script src="{{ asset('js/hod-settings.js') }}"></script>
    <script>
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('mobile-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
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
    </script>
    @stack('scripts')

    @include('partials.logout_modal')
    @include('partials.inactivity_logout')
    @include('partials.web_toast_notifications')
</body>
</html>

