<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Bridge | ولي الأمر</title>

    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Shared HOD Style -->
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
                <a href="{{ url('/parent/reports') }}" class="nav-item {{ Request::is('parent/reports') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    تقارير الأداء
                </a>
                <a href="{{ url('/parent/messages') }}" class="nav-item {{ Request::is('parent/messages*') ? 'active' : '' }}">
                    <i class="fa-solid fa-comments"></i>
                    الرسائل
                </a>
                <a href="{{ url('/parent/profile') }}" class="nav-item {{ Request::is('parent/profile') ? 'active' : '' }}">
                    <i class="fa-solid fa-user"></i>
                    الملف الشخصي
                </a>

                <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-inline: 1rem;">
                    <form action="{{ route('parent.logout') }}" method="POST">
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
                <div style="background-color: hsl(120, 70%, 95%); color: hsl(120, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div style="background-color: hsl(0, 70%, 95%); color: hsl(0, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div style="background-color: hsl(0, 70%, 95%); color: hsl(0, 50%, 30%); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem;">
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

