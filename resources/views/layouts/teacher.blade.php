<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Bridge | المعلم</title>
    
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

            <!-- Teacher Info -->
            <div style="text-align: center; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <div style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--accent-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; font-size: 1.5rem; font-weight: 800; color: #1a1a1a;">
                    {{ mb_substr(auth()->user()->full_name ?? 'م', 0, 1) }}
                </div>
                <div style="font-weight: 700; font-size: 0.95rem;">{{ auth()->user()->full_name ?? 'المعلم' }}</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">أستاذ</div>
            </div>
            
            <nav class="nav-menu" style="display: flex; flex-direction: column; flex: 1;">
                <a href="{{ url('/teacher/dashboard') }}" class="nav-item {{ Request::is('teacher/dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i>
                    الرئيسية
                </a>
                <a href="{{ url('/teacher/schedule') }}" class="nav-item {{ Request::is('teacher/schedule') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    جداولي
                </a>
                <a href="{{ url('/teacher/attendance') }}" class="nav-item {{ Request::is('teacher/attendance') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-user"></i>
                    الحضور والغياب
                </a>
                <a href="{{ url('/teacher/assignments') }}" class="nav-item {{ Request::is('teacher/assignments') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-pen"></i>
                    الواجبات والمشاريع
                </a>
                <a href="{{ url('/teacher/lectures') }}" class="nav-item {{ Request::is('teacher/lectures') ? 'active' : '' }}">
                    <i class="fa-solid fa-chalkboard-teacher"></i>
                    المحاضرات
                </a>
                <a href="{{ url('/teacher/reports') }}" class="nav-item {{ Request::is('teacher/reports') ? 'active' : '' }}" style="position:relative;">
                    <i class="fa-solid fa-file-lines"></i> التقارير
                    @php $pendingReports = \Illuminate\Support\Facades\DB::table('report_requests')->join('teachers','report_requests.teacher_id','=','teachers.teacher_id')->where('teachers.user_id', auth()->id())->where('report_requests.status','pending')->count(); @endphp
                    @if($pendingReports > 0)
                        <span style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);background:#ef4444;color:white;border-radius:50%;padding:0.1rem 0.5rem;font-size:0.75rem;font-weight:bold;">{{ $pendingReports }}</span>
                    @endif
                </a>
                <a href="{{ url('/teacher/notifications') }}" class="nav-item {{ Request::is('teacher/notifications') ? 'active' : '' }}" style="position: relative;">
                    <i class="fa-solid fa-bell"></i> الإشعارات
                    @php $unreadCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count(); @endphp
                    @if($unreadCount > 0)
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: #ef4444; color: white; border-radius: 50%; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: bold;">{{ $unreadCount }}</span>
                    @endif
                </a>
                <a href="{{ url('/teacher/profile') }}" class="nav-item {{ Request::is('teacher/profile') ? 'active' : '' }}">
                    <i class="fa-solid fa-user"></i>
                    الملف الشخصي
                </a>
                
                <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-inline: 1rem;">
                    <form action="{{ route('teacher.logout') }}" method="POST">
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

    <!-- Shared JS -->
    <script src="{{ asset('js/hod-settings.js') }}"></script>
    @stack('scripts')
</body>
</html>
