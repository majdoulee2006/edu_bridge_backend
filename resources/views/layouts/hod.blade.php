<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Bridge | رئيس القسم</title>
    
    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/hod-style.css') }}">
    
    @stack('styles')
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
                <div style="font-size: 0.8rem; color: var(--text-secondary);">رئيس القسم</div>
            </div>
            
            <nav class="nav-menu">
                <a href="{{ url('/hod/dashboard') }}" class="nav-item {{ Request::is('hod/dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i>
                    الرئيسية
                </a>
                <a href="{{ url('/hod/leaves') }}" class="nav-item {{ Request::is('hod/leaves') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    طلبات الإجازة
                </a>
                <a href="{{ url('/hod/notifications') }}" class="nav-item {{ Request::is('hod/notifications') ? 'active' : '' }}">
                    <i class="fa-solid fa-bell"></i>
                    الإشعارات
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
                <a href="{{ url('/hod/messages') }}" class="nav-item {{ Request::is('hod/messages') ? 'active' : '' }}">
                    <i class="fa-solid fa-envelope"></i>
                    الرسائل
                </a>
                <a href="{{ url('/hod/reports') }}" class="nav-item {{ Request::is('hod/reports') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-lines"></i>
                    طلب التقارير
                </a>
                <a href="{{ url('/hod/settings') }}" class="nav-item {{ Request::is('hod/settings') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i>
                    الإعدادات
                </a>
                
                <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-inline: 1rem;">
                    <form action="{{ route('hod.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="nav-item" style="width: 100%; border: none; background: transparent; color: #ef4444; font-weight: 700; cursor: pointer; text-align: right; padding-inline: 0;">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            تسجيل الخروج
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div>
                    <h1 class="page-title">@yield('title')</h1>
                    @hasSection('subtitle')
                        <p class="page-subtitle" style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 0.25rem;">@yield('subtitle')</p>
                    @endif
                </div>
                <div class="header-actions" style="display: flex; align-items: center; gap: 1rem;">
                    <!-- Dark Mode Toggle -->
                    <button onclick="toggleDarkMode()" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 50%; width: 40px; height: 40px; cursor: pointer; color: var(--text-secondary); font-size: 1.1rem; display: flex; align-items: center; justify-content: center;" title="تبديل الوضع">
                        <i class="fa-solid fa-moon" id="dark-mode-icon"></i>
                    </button>
                </div>
            </header>

            @if (session('success'))
                <div style="background-color: hsl(120, 70%, 95%); color: hsl(120, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div style="background-color: hsl(0, 70%, 95%); color: hsl(0, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
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
    @stack('scripts')
</body>
</html>
