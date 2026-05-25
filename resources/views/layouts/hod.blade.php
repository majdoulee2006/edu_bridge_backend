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
                <h1 class="page-title">@yield('title')</h1>
                <div class="header-actions">
                    <!-- Profile snippet or anything else can go here -->
                </div>
            </header>

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
