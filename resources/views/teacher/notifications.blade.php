<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu Bridge | الإشعارات</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --main-yellow: #f9f21a; --bg-cream: #fcfcf3; --white: #ffffff; }
        * { box-sizing: border-box; margin: 0; padding: 0; transition: 0.3s; }
        body { font-family: 'Cairo', sans-serif; background-color: var(--bg-cream); display: flex; height: 100vh; overflow: hidden; }

        aside {
            width: 280px; background: var(--white); height: 100vh;
            display: flex; flex-direction: column; padding: 30px 20px;
            border-left: 1px solid #eee; position: fixed; right: 0; top: 0;
        }
        .logo { font-weight: 900; font-size: 1.8rem; text-align: center; margin-bottom: 40px; }
        .logo span { color: var(--main-yellow); text-shadow: 1px 1px 0 #000; }
        .nav-link {
            display: flex; align-items: center; gap: 15px; padding: 12px 18px;
            text-decoration: none; color: #636e72; font-weight: 700; border-radius: 15px; margin-bottom: 8px;
        }
        .nav-link.active { background: var(--main-yellow); color: #000; box-shadow: 0 4px 12px rgba(249, 242, 26, 0.3); }

        main { margin-right: 280px; flex: 1; height: 100vh; padding: 40px; overflow-y: auto; }

        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }

        .notif-card {
            background: #fff; padding: 25px; border-radius: 30px;
            display: flex; gap: 20px; align-items: flex-start;
            margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.01);
            position: relative; border: 1px solid transparent;
        }
        .notif-card.unread { border-right: 5px solid var(--main-yellow); }
        .notif-card.unread::before {
            content: ''; position: absolute; top: 25px; right: 10px;
            width: 10px; height: 10px; background: var(--main-yellow); border-radius: 50%;
        }

        .icon-wrapper {
            width: 60px; height: 60px; border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; flex-shrink: 0;
        }
        
        .bg-academic { background: #fffdf0; color: #000; }
        .bg-query { background: #e3f2fd; color: #2196f3; }
        .bg-admin { background: #f3e5f5; color: #9c27b0; }
        .bg-grades { background: #f0f4f8; color: #607d8b; }

        .notif-body { flex: 1; }
        .notif-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .notif-type { font-weight: 800; font-size: 0.8rem; }
        .notif-time { font-size: 0.75rem; color: #aaa; font-weight: 700; }

        .notif-title { font-weight: 900; font-size: 1.1rem; margin-bottom: 5px; color: #333; }
        .notif-desc { color: #777; font-size: 0.9rem; line-height: 1.6; font-weight: 600; }

        .empty-state { text-align: center; padding: 60px; color: #888; }
        .empty-state i { font-size: 3rem; margin-bottom: 20px; color: #ddd; }

    </style>
</head>
<body>

    <aside>
        <div class="logo">EDU<span>BRIDGE</span></div>
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-link"><i class="fa-solid fa-house"></i> الرئيسية</a>
            <a href="{{ route('profile') }}" class="nav-link"><i class="fa-solid fa-user"></i> الملف الشخصي</a>
            <a href="{{ route('messages') }}" class="nav-link"><i class="fa-solid fa-comment"></i> المراسلة</a>
            <a href="{{ route('notifications') }}" class="nav-link active"><i class="fa-solid fa-bell"></i> الإشعارات</a>
            <a href="{{ route('schedule') }}" class="nav-link"><i class="fa-solid fa-calendar"></i> الجداول</a>
            <a href="{{ route('assignments') }}" class="nav-link"><i class="fa-solid fa-file-pen"></i> الواجبات</a>
            <a href="{{ route('attendance') }}" class="nav-link"><i class="fa-solid fa-check"></i> الحضور</a>
            <a href="{{ route('lectures') }}" class="nav-link"><i class="fa-solid fa-play"></i> المحاضرات</a>
        </nav>
    </aside>

    <main>
        <header>
            <h1 style="font-weight: 900;">الإشعارات</h1>
            <a href="{{ route('settings') }}" style="text-decoration: none;">
                <i class="fa-solid fa-gear" style="font-size: 1.2rem; color: #666; cursor: pointer;"></i>
            </a>
        </header>

        <div id="notificationsList">
            @forelse($notifications as $notification)
                <div class="notif-card {{ $notification->is_read ? '' : 'unread' }}" 
                     onclick="markAsRead({{ $notification->id }})"
                     id="notif-{{ $notification->id }}">
                    
                    @php
                        $iconClass = match($notification->type) {
                            'academic' => 'bg-academic fa-book-open',
                            'query' => 'bg-query fa-comment-dots',
                            'admin' => 'bg-admin fa-calendar-check',
                            'grades' => 'bg-grades fa-clipboard-check',
                            default => 'bg-admin fa-bell'
                        };
                        $typeColor = match($notification->type) {
                            'academic' => '#b8860b',
                            'query' => '#2196f3',
                            'admin' => '#9c27b0',
                            'grades' => '#607d8b',
                            default => '#666'
                        };
                        $typeName = match($notification->type) {
                            'academic' => 'واجب أكاديمي',
                            'query' => 'استفسار',
                            'admin' => 'إشعار إداري',
                            'grades' => 'تحديث الدرجات',
                            default => 'إشعار عام'
                        };
                    @endphp

                    <div class="icon-wrapper {{ explode(' ', $iconClass)[0] }}">
                        <i class="fa-solid {{ explode(' ', $iconClass)[1] }}"></i>
                    </div>
                    
                    <div class="notif-body">
                        <div class="notif-header">
                            <span class="notif-type" style="color: {{ $typeColor }};">{{ $typeName }}</span>
                            <span class="notif-time">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <h3 class="notif-title">{{ $notification->title }}</h3>
                        <p class="notif-desc">{{ $notification->message }}</p>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fa-solid fa-bell-slash"></i>
                    <h3>لا توجد إشعارات</h3>
                    <p>ستظهر الإشعارات الجديدة هنا عند وصولها</p>
                </div>
            @endforelse
        </div>
    </main>

    <script>
        async function markAsRead(id) {
            try {
                const response = await fetch(`/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if(result.success) {
                    const card = document.getElementById(`notif-${id}`);
                    card.classList.remove('unread');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    </script>
</body>
</html>