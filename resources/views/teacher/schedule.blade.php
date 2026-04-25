<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu Bridge | جداولي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --main-yellow: #f9f21a; --bg-cream: #fcfcf3; --white: #ffffff; }
        * { box-sizing: border-box; margin: 0; padding: 0; transition: 0.3s; }
        body { font-family: 'Cairo', sans-serif; background-color: var(--bg-cream); display: flex; height: 100vh; overflow: hidden; }

        /* --- Sidebar --- */
        aside {
            width: 280px; background: var(--white); height: 100vh;
            display: flex; flex-direction: column; padding: 30px 20px;
            border-left: 1px solid #eee; position: fixed; right: 0; top: 0;
            z-index: 100;
        }
        .logo { font-weight: 900; font-size: 1.8rem; text-align: center; margin-bottom: 40px; }
        .logo span { color: var(--main-yellow); text-shadow: 1px 1px 0 #000; }
        .nav-link {
            display: flex; align-items: center; gap: 15px; padding: 12px 18px;
            text-decoration: none; color: #636e72; font-weight: 700; border-radius: 15px; margin-bottom: 8px;
        }
        .nav-link.active { background: var(--main-yellow); color: #000; box-shadow: 0 4px 12px rgba(249, 242, 26, 0.3); }

        /* --- Main Content --- */
        main { margin-right: 280px; flex: 1; height: 100vh; padding: 40px; overflow-y: auto; }

        /* Calendar Strip */
        .calendar-strip {
            display: flex; gap: 15px; justify-content: flex-start; margin-bottom: 35px;
            overflow-x: auto; padding: 10px 5px;
        }
        .day-card {
            background: #fff; min-width: 80px; height: 100px; border-radius: 25px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        .day-card.active { background: var(--main-yellow); box-shadow: 0 8px 20px rgba(249, 242, 26, 0.4); }
        .day-name { font-size: 0.85rem; font-weight: 800; color: #aaa; margin-bottom: 5px; }
        .day-card.active .day-name { color: #000; }
        .day-num { font-size: 1.4rem; font-weight: 900; }

        .section-label { font-weight: 900; font-size: 1.5rem; margin-bottom: 25px; color: #2d3436; }

        /* Schedule Cards */
        .session-card {
            background: #fff; border-radius: 35px; padding: 25px;
            margin-bottom: 20px; display: flex; flex-direction: column;
            box-shadow: 0 5px 20px rgba(0,0,0,0.02); border: 2px solid transparent;
            position: relative;
        }
        .session-card.active-now { border-color: var(--main-yellow); background: #fffdf8; }

        .session-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .session-main { display: flex; align-items: center; gap: 20px; }
        
        .icon-box { 
            width: 60px; height: 60px; border-radius: 20px; 
            background: #fffdf0; display: flex; align-items: center; 
            justify-content: center; font-size: 1.5rem; color: #333; 
        }
        
        .badge-live { 
            background: var(--main-yellow); padding: 4px 12px; 
            border-radius: 10px; font-weight: 900; font-size: 0.75rem; 
            display: inline-block; margin-bottom: 8px; 
        }
        
        .session-text h3 { font-weight: 900; font-size: 1.25rem; color: #2d3436; }
        .session-text p { color: #888; font-weight: 700; font-size: 0.9rem; }

        .session-bottom { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-top: 25px; padding-top: 20px; border-top: 2px solid #fcfcfc; 
        }
        
        .time-info { display: flex; align-items: center; gap: 10px; font-weight: 800; color: #555; }
        
        .avatar-group { display: flex; align-items: center; }
        .avatar-group img { 
            width: 35px; height: 35px; border-radius: 50%; 
            border: 3px solid #fff; margin-left: -12px; 
        }
        .avatar-more { 
            background: #eee; width: 35px; height: 35px; border-radius: 50%; 
            font-size: 0.75rem; display: flex; align-items: center; 
            justify-content: center; font-weight: 900; border: 3px solid #fff;
        }

        .btn-action { 
            background: #f5f6f7; border: none; padding: 10px 20px; 
            border-radius: 15px; font-weight: 800; color: #444; cursor: pointer;
        }
        .btn-action:hover { background: #000; color: #fff; }

        /* إخفاء الجداول غير النشطة */
        .day-schedule { display: none; }
        .day-schedule.active { display: block; }
    </style>
</head>
<body>

    <aside>
        <div class="logo">EDU<span>BRIDGE</span></div>
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-link"><i class="fa-solid fa-house"></i> الرئيسية</a>
            <a href="{{ route('profile') }}" class="nav-link"><i class="fa-solid fa-user"></i> الملف الشخصي</a>
            <a href="{{ route('messages') }}" class="nav-link"><i class="fa-solid fa-comment"></i> المراسلة</a>
            <a href="{{ route('notifications') }}" class="nav-link"><i class="fa-solid fa-bell"></i> الإشعارات</a>
            <a href="{{ route('schedule') }}" class="nav-link active"><i class="fa-solid fa-calendar"></i> الجداول</a>
            <a href="{{ route('assignments') }}" class="nav-link"><i class="fa-solid fa-file-pen"></i> الواجبات</a>
            <a href="{{ route('attendance') }}" class="nav-link"><i class="fa-solid fa-check"></i> الحضور</a>
            <a href="{{ route('lectures') }}" class="nav-link"><i class="fa-solid fa-play"></i> المحاضرات</a>
        </nav>
    </aside>

    <main>
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="font-weight: 900;">جداولي</h1>
            <div style="background: var(--main-yellow); padding: 12px 25px; border-radius: 18px; font-weight: 900; font-size: 0.9rem;">
                أكتوبر 2026
            </div>
        </header>

        <div class="calendar-strip">
            <div class="day-card" onclick="showSchedule('day-11', this)"><span class="day-name">السبت</span><span class="day-num">11</span></div>
            <div class="day-card" onclick="showSchedule('day-12', this)"><span class="day-name">الأحد</span><span class="day-num">12</span></div>
            <div class="day-card active" onclick="showSchedule('day-13', this)"><span class="day-name">الاثنين</span><span class="day-num">13</span></div>
            <div class="day-card" onclick="showSchedule('day-14', this)"><span class="day-name">الثلاثاء</span><span class="day-num">14</span></div>
            <div class="day-card" onclick="showSchedule('day-15', this)"><span class="day-name">الأربعاء</span><span class="day-num">15</span></div>
            <div class="day-card" onclick="showSchedule('day-16', this)"><span class="day-name">الخميس</span><span class="day-num">16</span></div>
        </div>

        <h2 class="section-label">جدول اليوم</h2>

        <div id="day-11" class="day-schedule">
            @if(isset($schedules['السبت']))
                @foreach($schedules['السبت'] as $session)
                    <div class="session-card">
                        <div class="session-top">
                            <div class="session-main">
                                <div class="icon-box"><i class="fa-solid fa-book"></i></div>
                                <div class="session-text">
                                    <h3>{{ $session->course->title }}</h3>
                                    <p>قاعة {{ $session->room }}</p>
                                </div>
                            </div>
                            <button class="btn-action">رصد الحضور</button>
                        </div>
                        <div class="session-bottom">
                            <div style="font-weight: 800; color: #555;"><i class="fa-regular fa-clock"></i> {{ $session->start_time }} - {{ $session->end_time }}</div>
                        </div>
                    </div>
                @endforeach
            @else
                <p style="text-align: center; color: #888; padding: 40px; font-weight: 700;">لا توجد محاضرات مجدولة لهذا اليوم</p>
            @endif
        </div>

        <div id="day-12" class="day-schedule">
            @if(isset($schedules['الأحد']))
                @foreach($schedules['الأحد'] as $session)
                    <div class="session-card">
                        <div class="session-top">
                            <div class="session-main">
                                <div class="icon-box"><i class="fa-solid fa-laptop-code"></i></div>
                                <div class="session-text">
                                    <h3>{{ $session->course->title }}</h3>
                                    <p>قاعة {{ $session->room }}</p>
                                </div>
                            </div>
                            <button class="btn-action">رصد الحضور</button>
                        </div>
                        <div class="session-bottom">
                            <div style="font-weight: 800; color: #555;"><i class="fa-regular fa-clock"></i> {{ $session->start_time }} - {{ $session->end_time }}</div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div id="day-13" class="day-schedule active">
            @if(isset($schedules['الاثنين']))
                @foreach($schedules['الاثنين'] as $session)
                    <div class="session-card {{ $loop->first ? 'active-now' : '' }}">
                        <div class="session-top">
                            <div class="session-main">
                                <div class="icon-box"><i class="fa-solid fa-code"></i></div>
                                <div class="session-text">
                                    @if($loop->first) <span class="badge-live">جارية الآن</span> @endif
                                    <h3>{{ $session->course->title }}</h3>
                                    <p>المختبر البرمجي {{ $session->room }}</p>
                                </div>
                            </div>
                            <button class="btn-action">رصد الحضور</button>
                        </div>
                        <div class="session-bottom">
                            <div class="avatar-group">
                                <img src="https://via.placeholder.com/40"><img src="https://via.placeholder.com/40"><img src="https://via.placeholder.com/40">
                                <div class="avatar-more">+32</div>
                            </div>
                            <div class="time-info">
                                <i class="fa-regular fa-clock"></i>
                                <span>{{ $session->start_time }} - {{ $session->end_time }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div id="day-14" class="day-schedule">
            @if(isset($schedules['الثلاثاء']))
                @foreach($schedules['الثلاثاء'] as $session)
                    <div class="session-card">
                        <div class="session-top">
                            <div class="session-main">
                                <div class="icon-box"><i class="fa-solid fa-microchip"></i></div>
                                <div class="session-text">
                                    <h3>{{ $session->course->title }}</h3>
                                    <p>قاعة {{ $session->room }}</p>
                                </div>
                            </div>
                            <button class="btn-action">رصد الحضور</button>
                        </div>
                        <div class="session-bottom">
                            <div class="time-info">
                                <i class="fa-regular fa-clock"></i>
                                <span>{{ $session->start_time }} - {{ $session->end_time }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div id="day-15" class="day-schedule">
            @if(isset($schedules['الأربعاء']))
                @foreach($schedules['الأربعاء'] as $session)
                    <div class="session-card">
                        <div class="session-top">
                            <div class="session-main">
                                <div class="icon-box"><i class="fa-solid fa-network-wired"></i></div>
                                <div class="session-text">
                                    <h3>{{ $session->course->title }}</h3>
                                    <p>قاعة {{ $session->room }}</p>
                                </div>
                            </div>
                            <button class="btn-action">رصد الحضور</button>
                        </div>
                        <div class="session-bottom">
                            <div class="time-info">
                                <i class="fa-regular fa-clock"></i>
                                <span>{{ $session->start_time }} - {{ $session->end_time }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div id="day-16" class="day-schedule">
            @if(isset($schedules['الخميس']))
                @foreach($schedules['الخميس'] as $session)
                    <div class="session-card">
                        <div class="session-top">
                            <div class="session-main">
                                <div class="icon-box"><i class="fa-solid fa-brain"></i></div>
                                <div class="session-text">
                                    <h3>{{ $session->course->title }}</h3>
                                    <p>قاعة {{ $session->room }}</p>
                                </div>
                            </div>
                            <button class="btn-action">رصد الحضور</button>
                        </div>
                        <div class="session-bottom">
                            <div class="time-info">
                                <i class="fa-regular fa-clock"></i>
                                <span>{{ $session->start_time }} - {{ $session->end_time }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

    </main>

    <script>
        function showSchedule(dayId, element) {
            document.querySelectorAll('.day-card').forEach(card => { card.classList.remove('active'); });
            element.classList.add('active');
            document.querySelectorAll('.day-schedule').forEach(sched => { sched.classList.remove('active'); });
            document.getElementById(dayId).classList.add('active');
        }
    </script>
</body>
</html>