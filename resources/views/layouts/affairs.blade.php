<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Bridge | موظف الشؤون</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Shared HOD Style -->
    <link rel="stylesheet" href="{{ asset('css/hod-style.css') }}">

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
            .main-content {
                margin-right: 0 !important;
                margin-left: 0 !important;
                padding: 1rem;
                padding-bottom: 80px; /* space for bottom nav */
            }
            .bottom-nav {
                display: flex;
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
    </style>

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
                <a href="{{ url('/affairs/calendar') }}" class="nav-item {{ Request::is('affairs/calendar') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i> التقويم
                </a>
                <a href="{{ url('/affairs/activities') }}" class="nav-item {{ Request::is('affairs/activities') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-list"></i> الأنشطة
                </a>
                <a href="{{ url('/affairs/accounts') }}" class="nav-item {{ Request::is('affairs/accounts') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i> الحسابات
                </a>
                <a href="{{ url('/affairs/university-ids') }}" class="nav-item {{ Request::is('affairs/university-ids') ? 'active' : '' }}">
                    <i class="fa-solid fa-id-card"></i> الأرقام الجامعية
                </a>
                <a href="{{ url('/affairs/pending-accounts') }}" class="nav-item {{ Request::is('affairs/pending-accounts') ? 'active' : '' }}" style="display:flex; align-items:center; justify-content:space-between;">
                    <span><i class="fa-solid fa-clock"></i> طلبات التسجيل</span>
                    @php $pc = \App\Models\User::whereIn('role_id',[3,4])->where('status','inactive')->count(); @endphp
                    @if($pc > 0)
                        <span style="background:#ef4444; color:white; border-radius:2rem; padding:0.1rem 0.55rem; font-size:0.75rem; font-weight:800;">{{ $pc }}</span>
                    @endif
                </a>
                <a href="{{ url('/affairs/leaves') }}" class="nav-item {{ Request::is('affairs/leaves') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-signature"></i> طلبات الإجازة
                </a>
                <a href="{{ url('/affairs/reports') }}" class="nav-item {{ Request::is('affairs/reports') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-lines"></i> التقارير
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
                    <!-- Settings button for top left usually, but we are RTL so top right depending on layout -->
                    <a href="{{ route('affairs.settings') }}" class="settings-btn" title="الإعدادات">
                        <i class="fa-solid fa-gear"></i>
                    </a>
                    <div>
                        <h1 class="page-title">@yield('title')</h1>
                    </div>
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
            
            // Remove any inline styles that might conflict with our CSS classes
            sidebar.style.display = '';
            sidebar.style.position = '';
            sidebar.style.width = '';
            sidebar.style.zIndex = '';
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
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

