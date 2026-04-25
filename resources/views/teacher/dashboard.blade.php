<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu Bridge | الرئيسية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --main-yellow: #f9f21a; 
            --bg-cream: #fcfcf3; 
            --white: #ffffff; 
            --text-gray: #636e72;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; transition: 0.3s; }
        
        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: var(--bg-cream); 
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
        }

        /* القائمة الجانبية */
        aside {
            width: 280px; background: var(--white); height: 100vh;
            display: flex; flex-direction: column; padding: 30px 20px;
            border-left: 1px solid #eee; position: fixed; right: 0; top: 0;
            z-index: 100;
        }
        .logo { font-weight: 900; font-size: 1.8rem; text-align: center; margin-bottom: 40px; letter-spacing: -1px; }
        .logo span { color: var(--main-yellow); text-shadow: 1px 1px 0 #000; }
        
        .nav-menu { list-style: none; flex: 1; }
        .nav-link {
            display: flex; align-items: center; gap: 15px; padding: 12px 18px;
            text-decoration: none; color: var(--text-gray); font-weight: 700;
            border-radius: 15px; margin-bottom: 8px; font-size: 0.95rem;
        }
        .nav-link.active { background: var(--main-yellow); color: #000; box-shadow: 0 4px 12px rgba(249, 242, 26, 0.3); }
        .nav-link:hover:not(.active) { background: #f9f9f9; transform: translateX(-5px); }

        /* المحتوى الرئيسي */
        main { 
            margin-right: 280px; 
            flex: 1; 
            height: 100vh;
            padding: 40px; 
            overflow-y: auto; 
            display: flex;
            flex-direction: column;
        }

        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .user-pill { display: flex; align-items: center; gap: 12px; background: #fff; padding: 8px 15px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .user-pill img { width: 40px; height: 40px; border-radius: 10px; border: 2px solid var(--main-yellow); }

        .news-container { display: flex; flex-direction: column; gap: 20px; padding-bottom: 40px; }
        
        /* بطاقة الإعلان */
        .news-card {
            background: #fff; padding: 25px; border-radius: 25px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.02); border-right: 6px solid #ddd;
        }
        .news-content { display: flex; gap: 20px; align-items: center; flex: 1; }
        .icon-box { width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        
        .general { border-right-color: var(--main-yellow); } 
        .general .icon-box { background: #fffdf0; color: #f1c40f; }
        
        .course_specific { border-right-color: #2ecc71; } 
        .course_specific .icon-box { background: #e3f9e5; color: #2ecc71; }

        .btn-action { background: #000; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; border: none; cursor: pointer; }
        .btn-action.yellow { background: var(--main-yellow); color: #000; }

        /* ستايل المودال (النافذة المنبثقة) */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.5);
            align-items: center; justify-content: center;
        }
        .modal-content {
            background-color: #fff; padding: 40px; border-radius: 30px;
            width: 90%; max-width: 550px; text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            position: relative;
        }

        main::-webkit-scrollbar { width: 6px; }
        main::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }
    </style>
</head>
<body>

    <aside>
        <div class="logo">EDU<span>BRIDGE</span></div>
        <nav class="nav-menu">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i> الرئيسية
            </a>
            <a href="{{ route('profile') }}" class="nav-link {{ request()->is('profile') ? 'active' : '' }}">
                <i class="fa-solid fa-user"></i> الملف الشخصي
            </a>
            <a href="{{ route('messages') }}" class="nav-link {{ request()->is('messages*') ? 'active' : '' }}">
                <i class="fa-solid fa-comment"></i> المراسلة
            </a>
            <a href="{{ route('notifications') }}" class="nav-link {{ request()->is('notifications*') ? 'active' : '' }}">
                <i class="fa-solid fa-bell"></i> الإشعارات
            </a>
            <a href="{{ route('schedule') }}" class="nav-link {{ request()->is('schedule*') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar"></i> الجداول
            </a>
            <a href="{{ route('assignments') }}" class="nav-link {{ request()->is('assignments*') ? 'active' : '' }}">
                <i class="fa-solid fa-file"></i> الواجبات
            </a>
            <a href="{{ route('attendance') }}" class="nav-link {{ request()->is('attendance*') ? 'active' : '' }}">
                <i class="fa-solid fa-check"></i> الحضور
            </a>
            <a href="{{ route('lectures') }}" class="nav-link {{ request()->is('lectures*') ? 'active' : '' }}">
                <i class="fa-solid fa-play"></i> المحاضرات
            </a>

            <form action="{{ route('logout') }}" method="POST" style="margin-top: auto;">
                @csrf
                <button type="submit" class="nav-link" style="width: 100%; border: none; background: none; text-align: right; cursor: pointer; outline: none;">
                    <i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج
                </button>
            </form>
        </nav>
    </aside>

    <main>
        <header>
            <div>
                <h1 style="font-weight: 900; font-size: 2rem; color: #1e272e;">آخر التحديثات</h1>
                <p style="color: #888; font-weight: 600;">أهلاً {{ Auth::user()->full_name }}، اطلعي على الإعلانات والفرص المتاحة اليوم.</p>
            </div>
            <div class="user-pill">
                <div style="text-align: left; font-weight: 900; font-size: 0.85rem; margin-left: 10px;">{{ Auth::user()->username }}</div>
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->full_name) }}&background=f9f21a&color=000">
            </div>
        </header>

        <div class="news-container">
            @if($announcement)
                <div class="news-card {{ $announcement->type }}">
                    <div class="news-content">
                        <div style="width: 120px; height: 85px; overflow: hidden; border-radius: 15px; flex-shrink: 0; margin-left: 10px;">
                            <img src="https://img.freepik.com/free-vector/important-announcement-concept-illustration_114360-1252.jpg" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </div>

                        <div class="icon-box">
                            <i class="fa-solid {{ $announcement->type == 'general' ? 'fa-bullhorn' : 'fa-book-open' }}"></i>
                        </div>
                        <div>
                            <h3 style="font-weight: 900; color: #2d3436; margin-bottom: 5px;">{{ $announcement->title }}</h3>
                            <p style="color: #636e72; font-size: 0.95rem; line-height: 1.6;">{{ \Illuminate\Support\Str::limit($announcement->content, 80) }}</p>
                        </div>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; min-width: 110px;">
                        <span style="font-size: 0.75rem; color: #b2bec3; font-weight: 700;">
                            <i class="fa-regular fa-clock"></i> {{ $announcement->created_at->diffForHumans() }}
                        </span>
                        <button onclick="showDetails()" class="btn-action">عرض التفاصيل</button>
                    </div>
                </div>
            @else
                <div style="text-align: center; padding: 40px; background: #fff; border-radius: 25px; border: 2px dashed #eee;">
                    <p style="color: #aaa; font-weight: 700;">لا توجد إعلانات نشطة حالياً في النظام.</p>
                </div>
            @endif
        </div>
    </main>

    @if($announcement)
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="icon-box" style="margin: 0 auto 20px auto; background: var(--bg-cream); width: 80px; height: 80px; font-size: 2rem;">
                 <i class="fa-solid {{ $announcement->type == 'general' ? 'fa-bullhorn' : 'fa-book-open' }}" style="color: {{ $announcement->type == 'general' ? '#f1c40f' : '#2ecc71' }}"></i>
            </div>
            <h2 style="font-weight: 900; margin-bottom: 15px; font-size: 1.5rem;">{{ $announcement->title }}</h2>
            <p style="color: #636e72; line-height: 1.8; margin-bottom: 25px; text-align: justify;">
                {{ $announcement->content }}
            </p>
            <button onclick="closeDetails()" class="btn-action yellow" style="width: 100%;">إغلاق النافذة</button>
        </div>
    </div>
    @endif

    <script>
        function showDetails() {
            const modal = document.getElementById('detailsModal');
            if(modal) {
                modal.style.display = 'flex';
            }
        }

        function closeDetails() {
            const modal = document.getElementById('detailsModal');
            if(modal) {
                modal.style.display = 'none';
            }
        }

        window.onclick = function(event) {
            let modal = document.getElementById('detailsModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>