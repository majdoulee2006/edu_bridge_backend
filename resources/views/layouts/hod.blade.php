<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Bridge | رئيس القسم</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/hod-style.css') }}">
    
    @stack('styles')
    <script>
        const savedSettings = JSON.parse(localStorage.getItem('hodSettings'));
        if (savedSettings && savedSettings.theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
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
                <a href="{{ url('/hod/leaves') }}" class="nav-item {{ Request::is('hod/leaves') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    طلبات الإجازة
                </a>
                <a href="{{ url('/hod/notifications') }}" class="nav-item {{ Request::is('hod/notifications') ? 'active' : '' }}">
                    <i class="fa-solid fa-bell"></i>
                    الإشعارات
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
                <a href="{{ url('/hod/settings') }}" class="nav-item {{ Request::is('hod/settings') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i>
                    الإعدادات
                </a>
                
                <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-inline: 1rem;">
                    <form action="{{ route('hod.logout') }}" method="POST">
                        @csrf
                        <button type="button" onclick="showLogoutModal(this.closest('form'))" class="nav-item" style="width: 100%; border: none; background: transparent; color: #ef4444; font-weight: 700; cursor: pointer; text-align: right; padding-inline: 0;">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> تسجيل الخروج</button>
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
                <div class="header-actions" style="display: flex; align-items: center; gap: 1rem;">
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
                <div id="hod-success-alert" style="background-color: hsl(120, 70%, 95%); color: hsl(120, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; transition: opacity 0.5s;">
                    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div id="hod-error-alert" style="background-color: hsl(0, 70%, 95%); color: hsl(0, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; transition: opacity 0.5s;">
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

        // Auto-hide alerts after 6 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('hod-success-alert');
            const errorAlert = document.getElementById('hod-error-alert');
            
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    setTimeout(() => successAlert.style.display = 'none', 500);
                }, 6000);
            }
            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.style.opacity = '0';
                    setTimeout(() => errorAlert.style.display = 'none', 500);
                }, 6000);
            }
        });
    </script>
    @stack('scripts')

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

<script>
    var _logoutForm = null;

    function showLogoutModal(form) {
        _logoutForm = form;
        var modal = document.getElementById('logoutModal');
        modal.style.display = 'flex';
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = 'none';
        _logoutForm = null;
    }

    function confirmLogout() {
        if (_logoutForm) _logoutForm.submit();
    }

    // Close on backdrop click
    document.getElementById('logoutModal').addEventListener('click', function(e) {
        if (e.target === this) closeLogoutModal();
    });
</script></body>
</html>

