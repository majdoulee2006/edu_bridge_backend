<!DOCTYPE html>
<html class="light" dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edu-Bridge | @yield('title', 'الإدارة')</title>
    
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f2f20d",
                        "primary-dark": "#d9d905",
                        "primary-content": "#1a2633",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                        "surface-light": "#ffffff",
                        "surface-dark": "#1a2633",
                        "card-dark": "#1a231f",
                        "border-dark": "#3f3f46"
                    },
                    fontFamily: {
                        "display": ["Cairo", "Lexend", "sans-serif"],
                        "body": ["Cairo", "Lexend", "sans-serif"],
                        "arabic": ["Cairo", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "1rem", 
                        "lg": "1.5rem", 
                        "xl": "2rem", 
                        "2xl": "2.5rem",
                        "full": "9999px"
                    },
                    boxShadow: {
                        "soft": "0 4px 20px -2px rgba(0, 0, 0, 0.05)",
                        "glow": "0 0 15px rgba(242, 242, 13, 0.4)",
                        "fab": "0 4px 20px rgba(0,0,0,0.4)"
                    }
                },
            },
        }
    </script>
    
    <style>
        body {
            font-family: 'Cairo', 'Lexend', sans-serif;
            min-height: max(884px, 100dvh);
        }
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        /* CSS للقائمة الشعاعية */
        #menu-toggle:checked ~ .radial-overlay {
            opacity: 1;
            pointer-events: auto;
        }
        .semi-circle-menu {
            transform: scale(0.5) translateY(120%);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform-origin: bottom center;
        }
        #menu-toggle:checked ~ .radial-overlay .semi-circle-menu {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
        #menu-toggle:checked ~ div .fab-icon {
            transform: rotate(45deg);
        }
        .slice-container {
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 200px;
            height: 200px;
            transform-origin: bottom center;
            overflow: hidden;
            clip-path: polygon(50% 100%, 0 0, 100% 0);
            pointer-events: auto;
            transition: all 0.3s ease;
        }
        .slice-container::after {
            content: '';
            position: absolute;
            inset: 0;
            background-color: rgba(242, 242, 13, 0.02);
            transition: background-color 0.3s;
            z-index: -1;
        }
        .slice-container:hover::after {
            background-color: rgba(242, 242, 13, 0.15);
        }
        .dark .slice-container:hover::after {
            background-color: rgba(242, 242, 13, 0.2);
        }
        .slice-content {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 25px;
            pointer-events: none;
            position: relative;
        }
        .slice-1 { transform: translateX(-50%) rotate(67.5deg); }
        .slice-2 { transform: translateX(-50%) rotate(22.5deg); }
        .slice-3 { transform: translateX(-50%) rotate(-22.5deg); }
        .slice-4 { transform: translateX(-50%) rotate(-67.5deg); }
        .slice-1 .slice-inner { transform: rotate(-67.5deg); margin-top: 15px; margin-left: -15px; }
        .slice-2 .slice-inner { transform: rotate(-22.5deg); margin-top: 5px; margin-left: -5px; }
        .slice-3 .slice-inner { transform: rotate(22.5deg); margin-top: 5px; margin-right: -5px; }
        .slice-4 .slice-inner { transform: rotate(67.5deg); margin-top: 15px; margin-right: -15px; }
        .icon-wrapper {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .slice-container:hover .icon-wrapper {
            transform: scale(1.1);
            background-color: #f2f20d;
            color: #111714;
            box-shadow: 0 8px 25px rgba(242, 242, 13, 0.4);
            border-color: #f2f20d;
        }
        .dark .slice-container:hover .icon-wrapper {
            color: #000;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar-desktop {
                display: none;
            }
            .main-content-wrapper {
                margin-right: 0 !important;
                padding-bottom: 120px !important;
            }
        }
        @media (min-width: 769px) {
            .bottom-nav-mobile {
                display: none !important;
            }
            .floating-add-btn {
                bottom: 2rem !important;
                right: calc(260px + 2rem) !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-100 min-h-screen relative overflow-x-hidden selection:bg-primary selection:text-primary-content transition-colors duration-300">

    <div class="flex min-h-screen">
        
        <!-- ================= DESKTOP SIDEBAR ================= -->
        <aside class="sidebar-desktop w-[260px] bg-surface-light dark:bg-surface-dark border-l border-slate-100 dark:border-slate-800 fixed right-0 top-0 bottom-0 z-30 flex flex-col p-6 transition-colors duration-300">
            <div class="brand flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-white leading-tight mb-8">
                <i class="fa-solid fa-graduation-cap text-primary"></i>
                Edu-Bridge
            </div>

            <!-- Admin Profile Header Snippet -->
            @auth
            <div class="flex flex-col items-center text-center pb-6 mb-6 border-b border-slate-100 dark:border-slate-800">
                <div class="w-16 h-16 rounded-full bg-primary text-primary-content flex items-center justify-center font-bold text-2xl shadow-glow mb-3">
                    {{ mb_substr(Auth::user()->full_name ?? 'م', 0, 1) }}
                </div>
                <h3 class="font-bold text-slate-800 dark:text-white">{{ Auth::user()->full_name }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">المدير العام</p>
            </div>
            @endauth

            <nav class="flex flex-col gap-2 flex-grow overflow-y-auto hide-scrollbar">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all duration-200 {{ Request::is('admin/dashboard') ? 'bg-primary text-primary-content shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined">home</span>
                    الرئيسية
                </a>
                <a href="{{ route('admin.accounts') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all duration-200 {{ Request::is('admin/accounts') ? 'bg-primary text-primary-content shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined">account_balance_wallet</span>
                    الحسابات
                </a>
                <a href="{{ route('admin.courses') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all duration-200 {{ Request::is('admin/courses') ? 'bg-primary text-primary-content shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined">school</span>
                    الدورات
                </a>
                <a href="{{ route('admin.semesters-subjects') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all duration-200 {{ Request::is('admin/semesters-subjects') ? 'bg-primary text-primary-content shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined">class</span>
                    الفصول والمواد
                </a>
                <a href="{{ route('admin.reports') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all duration-200 {{ Request::is('admin/reports') ? 'bg-primary text-primary-content shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined">analytics</span>
                    التقارير
                </a>
                <a href="{{ route('admin.messages') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all duration-200 {{ Request::is('admin/messages') ? 'bg-primary text-primary-content shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined">chat_bubble</span>
                    الرسائل
                </a>
                <a href="{{ route('admin.notifications') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all duration-200 {{ Request::is('admin/notifications') ? 'bg-primary text-primary-content shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined">notifications</span>
                    الإشعارات
                </a>
                <a href="{{ route('admin.profile') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all duration-200 {{ Request::is('admin/profile') ? 'bg-primary text-primary-content shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined">person</span>
                    الملف الشخصي
                </a>
                <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all duration-200 {{ Request::is('admin/settings') ? 'bg-primary text-primary-content shadow-glow' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined">settings</span>
                    الإعدادات
                </a>
            </nav>

            <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-800">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-3 rounded-2xl font-bold text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition-all duration-200">
                        <span class="material-symbols-outlined">logout</span>
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </aside>

        <!-- ================= MAIN CONTENT CONTAINER ================= -->
        <div class="main-content-wrapper flex-grow mr-0 md:mr-[260px] min-h-screen transition-all duration-300">
            
            <!-- Sticky Header -->
            <header class="sticky top-0 z-40 w-full bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-sm pt-6 pb-4 px-6 shadow-[0_1px_0_rgba(0,0,0,0.05)] dark:shadow-[0_1px_0_rgba(255,255,255,0.05)] transition-colors duration-300">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @hasSection('back-button')
                            @yield('back-button')
                        @endif
                        <div class="flex flex-col">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white leading-tight">
                                @yield('header-title', 'Edu-Bridge')
                            </h1>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-0.5">
                                @yield('header-subtitle', 'لوحة تحكم المدير')
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <!-- Dark Mode Toggle Button -->
                        <button onclick="toggleDarkMode()" class="w-10 h-10 rounded-full bg-surface-light dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 shadow-sm hover:scale-105 active:scale-95 transition-all" title="تبديل المظهر">
                            <span class="material-symbols-outlined text-[24px]" id="dark-mode-icon">dark_mode</span>
                        </button>
                        
                        <a href="{{ route('admin.settings') }}" class="group flex items-center justify-center p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined text-slate-600 dark:text-slate-300 text-[28px] group-hover:rotate-45 transition-transform duration-500">
                                settings
                            </span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Alerts for Success & Error -->
            @if (session('success'))
                <div class="max-w-lg md:max-w-4xl mx-auto px-5 mt-4">
                    <div class="bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-400 p-4 rounded-2xl border border-emerald-100 dark:border-emerald-900/30 flex items-center gap-3 shadow-soft">
                        <span class="material-symbols-outlined text-xl">check_circle</span>
                        <span class="text-sm font-semibold">{{ session('success') }}</span>
                    </div>
                </div>
            @endif
            @if (session('error'))
                <div class="max-w-lg md:max-w-4xl mx-auto px-5 mt-4">
                    <div class="bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-400 p-4 rounded-2xl border border-rose-100 dark:border-rose-900/30 flex items-center gap-3 shadow-soft">
                        <span class="material-symbols-outlined text-xl">error</span>
                        <span class="text-sm font-semibold">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Main Body -->
            <main class="w-full max-w-lg md:max-w-4xl mx-auto px-5 mt-6 flex flex-col gap-6">
                @yield('content')
            </main>

        </div>

    </div>

    <!-- ================= MOBILE BOTTOM NAVIGATION ================= -->
    <div class="bottom-nav-mobile fixed bottom-6 inset-x-4 z-50 transition-all duration-300">
        <input class="peer hidden" id="menu-toggle" type="checkbox"/>
        
        <!-- Semi-circular radial menu (pop up when checking fab) -->
        <div class="radial-overlay fixed inset-0 z-40 bg-white/60 dark:bg-black/60 backdrop-blur-md opacity-0 pointer-events-none transition-opacity duration-300 flex flex-col items-center justify-end">
            <label class="absolute inset-0 w-full h-full cursor-pointer" for="menu-toggle"></label>
            <div class="semi-circle-menu relative w-[400px] h-[200px] mb-[54px] z-50 pointer-events-none">
                <div class="absolute inset-0 bg-surface-light dark:bg-surface-dark shadow-[0_-8px_30px_rgba(0,0,0,0.12)] rounded-t-[200px] overflow-hidden border border-slate-100 dark:border-slate-700/50">
                    <div class="absolute bottom-0 left-1/2 w-[1px] h-full bg-slate-200 dark:bg-white/5 origin-bottom rotate-[45deg] -translate-x-1/2 z-10"></div>
                    <div class="absolute bottom-0 left-1/2 w-[1px] h-full bg-slate-200 dark:bg-white/5 origin-bottom rotate-[-45deg] -translate-x-1/2 z-10"></div>
                    <div class="absolute bottom-0 left-1/2 w-[1px] h-full bg-slate-200 dark:bg-white/5 origin-bottom z-10"></div>
                </div>
                
                <!-- Accounts slice -->
                <a href="{{ route('admin.accounts') }}">
                    <div class="slice-container slice-4 group cursor-pointer">
                        <div class="slice-content transition-colors">
                            <div class="slice-inner flex flex-col items-center gap-2">
                                <div class="icon-wrapper w-14 h-14 rounded-full bg-surface-light dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center mb-0.5 text-slate-800 dark:text-white shadow-sm {{ Request::is('admin/accounts') ? 'ring-2 ring-primary bg-primary/20 text-yellow-700' : '' }}">
                                    <span class="material-symbols-outlined text-[30px]">account_balance_wallet</span>
                                </div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 group-hover:text-black dark:group-hover:text-primary transition-colors">الحسابات</span>
                            </div>
                        </div>
                    </div>
                </a>
                
                <!-- Courses slice -->
                <a href="{{ route('admin.courses') }}">
                    <div class="slice-container slice-3 group cursor-pointer">
                        <div class="slice-content transition-colors">
                            <div class="slice-inner flex flex-col items-center gap-2">
                                <div class="icon-wrapper w-14 h-14 rounded-full bg-surface-light dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center mb-0.5 text-slate-800 dark:text-white shadow-sm {{ Request::is('admin/courses') ? 'ring-2 ring-primary bg-primary/20 text-yellow-700' : '' }}">
                                    <span class="material-symbols-outlined text-[30px]">school</span>
                                </div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 group-hover:text-black dark:group-hover:text-primary transition-colors">الدورات</span>
                            </div>
                        </div>
                    </div>
                </a>
                
                <!-- Semesters & subjects slice -->
                <a href="{{ route('admin.semesters-subjects') }}">
                    <div class="slice-container slice-2 group cursor-pointer">
                        <div class="slice-content transition-colors">
                            <div class="slice-inner flex flex-col items-center gap-2">
                                <div class="icon-wrapper w-14 h-14 rounded-full bg-surface-light dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center mb-0.5 text-slate-800 dark:text-white shadow-sm {{ Request::is('admin/semesters-subjects') ? 'ring-2 ring-primary bg-primary/20 text-yellow-700' : '' }}">
                                    <span class="material-symbols-outlined text-[30px]">class</span>
                                </div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 group-hover:text-black dark:group-hover:text-primary transition-colors">الفصول والمواد</span>
                            </div>
                        </div>
                    </div>
                </a>
                
                <!-- Reports slice -->
                <a href="{{ route('admin.reports') }}">
                    <div class="slice-container slice-1 group cursor-pointer">
                        <div class="slice-content transition-colors">
                            <div class="slice-inner flex flex-col items-center gap-2">
                                <div class="icon-wrapper w-14 h-14 rounded-full bg-surface-light dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center mb-0.5 text-slate-800 dark:text-white shadow-sm {{ Request::is('admin/reports') ? 'ring-2 ring-primary bg-primary/20 text-yellow-700' : '' }}">
                                    <span class="material-symbols-outlined text-[30px]">analytics</span>
                                </div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 group-hover:text-black dark:group-hover:text-primary transition-colors">التقارير</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Static bar with central circular grid button -->
        <div class="relative w-full bg-surface-light dark:bg-surface-dark rounded-full border border-slate-200 dark:border-slate-700/50 pb-2 pt-2 px-3 shadow-[0_8px_30px_rgba(0,0,0,0.12)]">
            <div class="flex items-center justify-between relative h-[60px]">
                
                <!-- Home -->
                <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center justify-center w-[60px] h-full transition-colors group {{ Request::is('admin/dashboard') ? 'text-primary' : 'text-slate-500 dark:text-slate-400 hover:text-primary' }}">
                    <span class="material-symbols-outlined {{ Request::is('admin/dashboard') ? 'filled' : '' }} text-[24px] mb-0.5" style="{{ Request::is('admin/dashboard') ? "font-variation-settings: 'FILL' 1;" : '' }}">home</span>
                    <span class="text-[10px] font-medium opacity-100">الرئيسية</span>
                </a>
                
                <!-- Profile -->
                <a href="{{ route('admin.profile') }}" class="flex flex-col items-center justify-center w-[60px] h-full transition-colors group {{ Request::is('admin/profile') ? 'text-primary' : 'text-slate-500 dark:text-slate-400 hover:text-primary' }}">
                    <span class="material-symbols-outlined {{ Request::is('admin/profile') ? 'filled' : '' }} text-[24px] mb-0.5" style="{{ Request::is('admin/profile') ? "font-variation-settings: 'FILL' 1;" : '' }}">person</span>
                    <span class="text-[10px] font-medium opacity-100">الملف</span>
                </a>
                
                <!-- Spacer for central FAB button -->
                <div class="w-16 h-full"></div>
                
                <!-- Notifications -->
                <a href="{{ route('admin.notifications') }}" class="flex flex-col items-center justify-center w-[60px] h-full transition-colors group relative {{ Request::is('admin/notifications') ? 'text-primary' : 'text-slate-500 dark:text-slate-400 hover:text-primary' }}">
                    @php $unreadCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count(); @endphp
                    @if($unreadCount > 0)
                        <div class="absolute top-1 right-3 w-2 h-2 bg-red-500 rounded-full border-2 border-white dark:border-surface-dark"></div>
                    @endif
                    <span class="material-symbols-outlined {{ Request::is('admin/notifications') ? 'filled' : '' }} text-[24px] mb-0.5" style="{{ Request::is('admin/notifications') ? "font-variation-settings: 'FILL' 1;" : '' }}">notifications</span>
                    <span class="text-[10px] font-medium opacity-100">الإشعارات</span>
                </a>
                
                <!-- Messages -->
                <a href="{{ route('admin.messages') }}" class="flex flex-col items-center justify-center w-[60px] h-full transition-colors group {{ Request::is('admin/messages') ? 'text-primary' : 'text-slate-500 dark:text-slate-400 hover:text-primary' }}">
                    <span class="material-symbols-outlined {{ Request::is('admin/messages') ? 'filled' : '' }} text-[24px] mb-0.5" style="{{ Request::is('admin/messages') ? "font-variation-settings: 'FILL' 1;" : '' }}">chat_bubble</span>
                    <span class="text-[10px] font-medium opacity-100">الرسائل</span>
                </a>
            </div>
            
            <!-- Radial Menu Button FAB -->
            <div class="absolute left-1/2 -translate-x-1/2 -top-8 size-16 z-50">
                <div class="absolute inset-0 rounded-full bg-primary/20 animate-pulse"></div>
                <label class="relative flex h-full w-full items-center justify-center rounded-full bg-primary hover:bg-primary-dark text-primary-content shadow-fab cursor-pointer transition-transform active:scale-95 border-4 border-white dark:border-background-dark" for="menu-toggle">
                    <span class="material-symbols-outlined fab-icon transition-transform duration-300" style="font-size: 32px;">grid_view</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Theme management script -->
    <script>
        function initTheme() {
            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
                document.getElementById('dark-mode-icon').textContent = 'light_mode';
            } else {
                document.documentElement.classList.remove('dark');
                document.getElementById('dark-mode-icon').textContent = 'dark_mode';
            }
        }
        
        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                document.getElementById('dark-mode-icon').textContent = 'dark_mode';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                document.getElementById('dark-mode-icon').textContent = 'light_mode';
            }
        }
        
        // Run on load
        initTheme();
    </script>
    @stack('scripts')
</body>
</html>
