<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu Bridge | المحاضرات</title>
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

        aside {
            width: 280px; background: var(--white); height: 100vh;
            display: flex; flex-direction: column; padding: 30px 20px;
            border-left: 1px solid #eee; position: fixed; right: 0; top: 0;
            z-index: 100;
        }
        .logo { font-weight: 900; font-size: 1.8rem; text-align: center; margin-bottom: 40px; }
        .logo span { color: var(--main-yellow); text-shadow: 1px 1px 0 #000; }
        
        .nav-menu { list-style: none; flex: 1; }
        .nav-link {
            display: flex; align-items: center; gap: 15px; padding: 12px 18px;
            text-decoration: none; color: var(--text-gray); font-weight: 700;
            border-radius: 15px; margin-bottom: 8px; font-size: 0.95rem;
        }
        .nav-link.active { background: var(--main-yellow); color: #000; box-shadow: 0 4px 12px rgba(249, 242, 26, 0.3); }
        .nav-link:hover:not(.active) { background: #f9f9f9; transform: translateX(-5px); }

        main { 
            margin-right: 280px; 
            flex: 1; 
            height: 100vh;
            padding: 40px; 
            overflow-y: auto; 
            display: flex;
            flex-direction: column;
            align-items: center; 
        }

        header { width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .header-title h1 { font-weight: 900; font-size: 2.2rem; }
        .header-title p { color: #888; font-weight: 700; font-size: 1.1rem; }

        .user-pill { display: flex; align-items: center; gap: 12px; background: #fff; padding: 8px 15px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .user-pill img { width: 40px; height: 40px; border-radius: 10px; border: 2px solid var(--main-yellow); }

        .lecture-card {
            background: #fff; 
            width: 100%; 
            max-width: 550px; 
            border-radius: 40px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            border-right: 8px solid var(--main-yellow);
        }

        .setup-title { text-align: right; color: #b8860b; font-weight: 900; margin-bottom: 25px; font-size: 1.3rem; }
        
        .input-group { text-align: right; margin-bottom: 25px; }
        .input-group label { display: block; font-weight: 900; margin-bottom: 12px; font-size: 1.1rem; color: #333; }
        
        .custom-input, .ui-select {
            width: 100%; padding: 18px; border-radius: 20px; background: #f9f9f9;
            border: 1px solid #eee; font-family: 'Cairo'; font-weight: 700; color: #555; font-size: 1rem;
            outline: none;
        }

        .row-group { display: flex; gap: 20px; margin-bottom: 25px; }
        .row-group .input-group { flex: 1; margin-bottom: 0; }

        .upload-box {
            border: 3px dashed #eee;
            padding: 30px;
            border-radius: 25px;
            text-align: center;
            margin-bottom: 30px;
            cursor: pointer;
            color: #888;
            font-weight: 800;
        }
        .upload-box i { font-size: 2rem; color: var(--main-yellow); margin-bottom: 10px; display: block; }
        .upload-box:hover { border-color: var(--main-yellow); background: #fffdf0; }

        .btn-add {
            background: #000; color: #fff; border: none; width: 100%; padding: 20px;
            border-radius: 20px; font-weight: 900; font-size: 1.2rem; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 12px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .btn-add:hover { background: var(--main-yellow); color: #000; }

        .alert {
            width: 100%; max-width: 550px; padding: 15px 20px; border-radius: 15px;
            margin-bottom: 20px; font-weight: 700; text-align: right;
        }
        .alert-success { background: #d4edda; color: #155724; border-right: 5px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-right: 5px solid #dc3545; }

        .lectures-list {
            width: 100%; max-width: 550px; margin-top: 30px;
        }
        .lecture-item {
            background: #fff; border-radius: 20px; padding: 20px 25px;
            margin-bottom: 15px; display: flex; align-items: center; gap: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-right: 5px solid var(--main-yellow);
        }
        .lecture-item i { font-size: 1.5rem; color: var(--main-yellow); }
        .lecture-info { flex: 1; text-align: right; }
        .lecture-info h4 { font-weight: 900; font-size: 1rem; margin-bottom: 4px; }
        .lecture-info p { color: #888; font-size: 0.85rem; font-weight: 600; }
        .lecture-date { color: #aaa; font-size: 0.8rem; font-weight: 700; }

    </style>
</head>
<body>

    <aside>
        <div class="logo">EDU<span>BRIDGE</span></div>
        <nav class="nav-menu">
            <a href="{{ route('dashboard') }}" class="nav-link"><i class="fa-solid fa-house"></i> الرئيسية</a>
            <a href="{{ route('profile') }}" class="nav-link"><i class="fa-solid fa-user"></i> الملف الشخصي</a>
            <a href="{{ route('messages') }}" class="nav-link"><i class="fa-solid fa-comment"></i> المراسلة</a>
            <a href="{{ route('notifications') }}" class="nav-link"><i class="fa-solid fa-bell"></i> الإشعارات</a>
            <a href="{{ route('schedule') }}" class="nav-link"><i class="fa-solid fa-calendar"></i> الجداول</a>
            <a href="{{ route('assignments') }}" class="nav-link"><i class="fa-solid fa-file"></i> الواجبات</a>
            <a href="{{ route('attendance') }}" class="nav-link"><i class="fa-solid fa-check"></i> الحضور</a>
            <a href="{{ route('lectures') }}" class="nav-link active"><i class="fa-solid fa-play"></i> المحاضرات</a>
        </nav>
    </aside>

    <main>
        <header>
            <div class="header-title">
                <h1>إضافة محاضرة</h1>
                <p>قم بتعبئة بيانات المحاضرة الجديدة لرفعها للطلاب.</p>
            </div>
            <div class="user-pill">
                <div style="text-align: left; font-weight: 900; font-size: 0.9rem;">{{ Auth::user()->full_name ?? 'المعلم' }}</div>
                <img src="https://via.placeholder.com/100">
            </div>
        </header>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <form class="lecture-card" action="{{ route('lectures.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <p class="setup-title">تفاصيل المحاضرة</p>
            
            <div class="input-group">
                <label>عنوان المحاضرة</label>
                <input type="text" name="title" class="custom-input" placeholder="مثلاً: مقدمة في Flutter UI" required>
            </div>

            <div class="row-group">
                <div class="input-group">
                    <label>المادة</label>
                    <select name="course_id" class="ui-select" required>
                        <option value="">اختر المادة</option>
                        @forelse($courses as $course)
                            <option value="{{ $course->course_id }}">{{ $course->title }}</option>
                        @empty
                            <option value="" disabled>لا توجد مواد متاحة</option>
                        @endforelse
                    </select>
                </div>
                <div class="input-group">
                    <label>القسم / الصف</label>
                    <select name="department_id" class="ui-select" required>
                        <option value="">اختر الصف</option>
                        @forelse($departments as $department)
                            <option value="{{ $department->department_id }}">{{ $department->name }}</option>
                        @empty
                            <option value="" disabled>لا توجد أقسام متاحة</option>
                        @endforelse
                    </select>
                </div>

            <label style="display: block; text-align: right; font-weight: 900; margin-bottom: 12px; font-size: 1.1rem;">ملف المحاضرة (PDF/Video)</label>
            <div class="upload-box" onclick="document.getElementById('file-input').click()">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <span id="file-label">اسحب الملف هنا أو انقر للإرفاق</span>
                <input type="file" id="file-input" name="content_file" accept=".pdf,.mp4,.mov,.avi,.mkv" required style="display:none" onchange="document.getElementById('file-label').textContent = this.files[0].name">
            </div>

            <button type="submit" class="btn-add">
                رفع المحاضرة الآن <i class="fa-solid fa-check-circle"></i>
            </button>
        </form>

        @if(isset($lessons) && $lessons->count() > 0)
        <div class="lectures-list">
            <p class="setup-title" style="text-align:center; margin-bottom: 20px;">📚 آخر المحاضرات المرفوعة</p>
            @foreach($lessons as $lesson)
            <div class="lecture-item">
                <i class="fa-solid fa-circle-play"></i>
                <div class="lecture-info">
                    <h4>{{ $lesson->title }}</h4>
                    <p>{{ $lesson->course->title ?? '—' }} | {{ $lesson->department->name ?? '—' }}</p>
                </div>
                <div class="lecture-date">{{ $lesson->created_at->format('Y-m-d') }}</div>
            @endforeach
        </div>
        @endif
    </main>

</body>
</html>
