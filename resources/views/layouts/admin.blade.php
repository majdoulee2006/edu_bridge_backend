<!DOCTYPE html>
<html class="light" dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edu-Bridge | @yield('title', 'الإدارة')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Google Fonts: Cairo -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
                    "background-light": "#f6f7f8",
                    "background-dark": "#101922",
                    "surface-light": "#ffffff",
                    "surface-dark": "#1a2633",
                    "card-dark": "#1e2d3d"
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

    <script>
        // Immediately set the theme to avoid flicker
        // Resolve theme: check both keys so all layouts share the preference
        (function() {
            var colorTheme  = localStorage.getItem('color-theme');
            var hodRaw      = localStorage.getItem('hodSettings');
            var hodTheme    = hodRaw ? (JSON.parse(hodRaw).theme || null) : null;
            // hodSettings wins if color-theme is absent
            var resolved    = colorTheme || hodTheme;
            var isDark      = resolved === 'dark' || (!resolved && window.matchMedia('(prefers-color-scheme: dark)').matches);
            var theme       = isDark ? 'dark' : 'light';
            // Ensure both keys agree
            localStorage.setItem('color-theme', theme);
            if (hodRaw) {
                var h = JSON.parse(hodRaw); h.theme = theme;
                localStorage.setItem('hodSettings', JSON.stringify(h));
            }
            document.documentElement.classList.toggle('dark',  isDark);
            document.documentElement.classList.toggle('light', !isDark);
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
    </style>
    @stack('styles')
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-100 min-h-screen overflow-x-hidden selection:bg-primary selection:text-primary-content font-display transition-colors duration-300">

    <div class="flex min-h-screen">
        <!-- Mobile Sidebar Overlay -->
        <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

        {{-- ======= SIDEBAR ======= --}}
        <aside id="sidebar" class="w-72 bg-white dark:bg-[#101924] text-slate-800 dark:text-white flex flex-col fixed top-0 bottom-0 right-0 z-50 transition-transform duration-300 border-l border-slate-200 dark:border-slate-800 translate-x-full md:translate-x-0">
            <!-- Brand Logo -->
            <div class="pt-8 pb-4 px-6 flex items-center justify-center gap-2">
                <span class="text-2xl font-black text-slate-800 dark:text-white tracking-wide">Edu-Bridge</span>
                <i class="fa-solid fa-graduation-cap text-[#f2f20d] text-2xl"></i>
            </div>

            <!-- Profile Info Header (Screenshot style) -->
            <div class="flex flex-col items-center my-4 px-4 text-center">
                <!-- Yellow circle avatar -->
                <div class="w-20 h-20 rounded-full bg-[#f2f20d] flex items-center justify-center text-[#101924] font-black text-3xl mb-3 shadow-glow select-none">
                    إ
                </div>
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white leading-tight">إدارة المعهد التقني</h3>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1">المدير العام</span>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 min-h-0 px-4 py-3 flex flex-col gap-1 overflow-y-auto hide-scrollbar">
                {{-- Dashboard --}}
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm transition-all {{ Request::is('admin/dashboard') ? 'bg-[#f2f20d] text-[#101924] shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-[#f2f20d]' }}">
                    <i class="fa-solid fa-house text-base"></i>
                    الرئيسية
                </a>

                {{-- Accounts --}}
                <a href="{{ route('admin.accounts') }}" class="flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm transition-all {{ Request::is('admin/accounts*') ? 'bg-[#f2f20d] text-[#101924] shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-[#f2f20d]' }}">
                    <i class="fa-solid fa-address-card text-base"></i>
                    الحسابات
                </a>

                {{-- Courses --}}
                <a href="{{ route('admin.courses') }}" class="flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm transition-all {{ Request::is('admin/courses*') ? 'bg-[#f2f20d] text-[#101924] shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-[#f2f20d]' }}">
                    <i class="fa-solid fa-graduation-cap text-base"></i>
                    الدورات
                </a>

                {{-- Semesters --}}
                <a href="{{ route('admin.semesters-subjects') }}" class="flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm transition-all {{ Request::is('admin/semesters-subjects*') ? 'bg-[#f2f20d] text-[#101924] shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-[#f2f20d]' }}">
                    <i class="fa-solid fa-book-bookmark text-base"></i>
                    الفصول والمواد
                </a>

                {{-- Reports --}}
                <a href="{{ route('admin.reports') }}" class="flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm transition-all {{ Request::is('admin/reports*') ? 'bg-[#f2f20d] text-[#101924] shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-[#f2f20d]' }}">
                    <i class="fa-solid fa-chart-simple text-base"></i>
                    التقارير
                </a>

                {{-- Student Services --}}
                <a href="{{ route('admin.student_services') }}" class="flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm transition-all {{ Request::is('admin/student-services*') ? 'bg-[#f2f20d] text-[#101924] shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-[#f2f20d]' }}">
                    <i class="fa-solid fa-boxes-stacked text-base"></i>
                    الخدمات الطلابية
                </a>

                {{-- Notifications --}}
                <a href="{{ route('admin.notifications') }}" class="flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm transition-all {{ Request::is('admin/notifications*') ? 'bg-[#f2f20d] text-[#101924] shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-[#f2f20d]' }}">
                    <i class="fa-solid fa-bell text-base"></i>
                    الإشعارات
                    @php $unread = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count(); @endphp
                    @if($unread > 0)
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-white text-[10px] font-black">{{ $unread }}</span>
                    @endif
                </a>

                {{-- Messages --}}
                <a href="{{ route('admin.messages') }}" class="flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm transition-all {{ Request::is('admin/messages*') ? 'bg-[#f2f20d] text-[#101924] shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-[#f2f20d]' }}">
                    <i class="fa-solid fa-comments text-base"></i>
                    الرسائل
                    @php $unreadMessages = \App\Models\Message::where('receiver_id', auth()->id())->where('is_read', false)->count(); @endphp
                    @if($unreadMessages > 0)
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-white text-[10px] font-black">{{ $unreadMessages }}</span>
                    @endif
                </a>

                {{-- Profile --}}
                <a href="{{ route('admin.profile') }}" class="flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm transition-all {{ Request::is('admin/profile*') ? 'bg-[#f2f20d] text-[#101924] shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-[#f2f20d]' }}">
                    <i class="fa-solid fa-user text-base"></i>
                    الملف الشخصي
                </a>

                {{-- Settings --}}
                <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm transition-all {{ Request::is('admin/settings*') ? 'bg-[#f2f20d] text-[#101924] shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-[#f2f20d]' }}">
                    <i class="fa-solid fa-gear text-base"></i>
                    الاعدادات
                </a>

                <!-- Logout Button -->
                <div class="mt-auto pt-4 border-t border-slate-200 dark:border-slate-800">
                    <form id="admin-logout-form" action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="button" onclick="confirmLogout()"
                                class="w-full flex items-center gap-3 px-4 py-3 rounded-full font-bold text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition-all text-right">
                            <i class="fa-solid fa-arrow-right-from-bracket text-base"></i>
                            تسجيل الخروج
                        </button>
                    </form>
                </div>

                <!-- Logout Confirm Modal -->
                <div id="logout-confirm-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl p-6 w-full max-w-sm text-center" dir="rtl">
                        <div class="w-14 h-14 rounded-full bg-red-50 dark:bg-red-950/30 flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-arrow-right-from-bracket text-2xl text-red-500"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1">تسجيل الخروج</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">هل تريد تسجيل الخروج من لوحة التحكم؟</p>
                        <div class="flex gap-3">
                            <button onclick="document.getElementById('admin-logout-form').submit()"
                                    class="flex-1 py-3 rounded-2xl bg-red-500 hover:bg-red-600 text-white font-bold text-sm transition-all active:scale-95">
                                نعم، خروج
                            </button>
                            <button onclick="document.getElementById('logout-confirm-modal').classList.add('hidden')"
                                    class="flex-1 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-sm transition-all active:scale-95">
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            </nav>
        </aside>

        {{-- ======= MAIN CONTENT AREA ======= --}}
        <div class="flex-1 mr-0 md:mr-72 flex flex-col min-h-screen transition-all duration-300">
            <!-- Top Bar Header -->
            <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-4 md:px-8 py-4 flex items-center justify-between sticky top-0 z-20 transition-colors">
                <div class="flex items-center gap-3">
                    <button id="mobile-menu-btn" class="md:hidden flex items-center justify-center w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-600 transition-all">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <h1 class="text-base md:text-lg font-bold text-slate-800 dark:text-white truncate max-w-[200px] md:max-w-none">
                        أهلاً، {{ Auth::user()->full_name ?? 'المدير العام' }} 👋
                    </h1>
                </div>
                
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle Switch -->
                    <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-600 transition-all">
                        <i class="fa-solid fa-moon text-base dark:hidden"></i>
                        <i class="fa-solid fa-sun text-base hidden dark:inline"></i>
                    </button>

                    <!-- Language Toggle -->
                    <button onclick="toggleAdminLanguage()" class="h-10 px-3 rounded-full bg-slate-50 dark:bg-slate-700 flex items-center gap-1.5 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-600 transition-all text-xs font-bold">
                        <i class="fa-solid fa-globe text-sm"></i>
                        <span id="admin-lang-btn-text">EN</span>
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
                <div class="px-8 pt-4 alert-toast">
                    <div class="flex items-center gap-3 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                        <i class="fa-solid fa-circle-check text-lg"></i>
                        <span class="text-sm font-semibold">{{ session('success') }}</span>
                    </div>
                </div>
            @endif
            @if (session('error'))
                <div class="px-8 pt-4 alert-toast">
                    <div class="flex items-center gap-3 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/30 text-rose-700 dark:text-rose-400">
                        <i class="fa-solid fa-circle-xmark text-lg"></i>
                        <span class="text-sm font-semibold">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Body Grid Container -->
            <main class="flex-1 p-8 flex flex-col gap-6 w-full mx-auto">
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

    @stack('scripts')

    <script>
        function confirmLogout() {
            document.getElementById('logout-confirm-modal').classList.remove('hidden');
        }

        // Theme toggle logic
        const themeToggleBtn = document.getElementById('theme-toggle');

        // Mobile Sidebar Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');

        if(mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.remove('translate-x-full');
                mobileOverlay.classList.remove('hidden');
            });
        }

        if(mobileOverlay) {
            mobileOverlay.addEventListener('click', () => {
                sidebar.classList.add('translate-x-full');
                mobileOverlay.classList.add('hidden');
            });
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
        function toggleAdminLanguage() {
            const currentLang = localStorage.getItem('app-lang') || 'ar';
            const newLang = currentLang === 'ar' ? 'en' : 'ar';
            localStorage.setItem('app-lang', newLang);
            document.documentElement.setAttribute('dir', newLang === 'ar' ? 'rtl' : 'ltr');
            document.documentElement.setAttribute('lang', newLang);
            const btn = document.getElementById('admin-lang-btn-text');
            if (btn) btn.textContent = newLang === 'ar' ? 'EN' : 'عر';
        }

        // Restore language state on load
        (function() {
            const savedLang = localStorage.getItem('app-lang') || 'ar';
            document.documentElement.setAttribute('dir', savedLang === 'ar' ? 'rtl' : 'ltr');
            document.documentElement.setAttribute('lang', savedLang);
            const btn = document.getElementById('admin-lang-btn-text');
            if (btn) btn.textContent = savedLang === 'ar' ? 'EN' : 'عر';
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

