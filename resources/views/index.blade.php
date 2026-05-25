<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu Bridge - اختيار الدخول</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: #fcfcfc; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0; 
        }
        .main-title { 
            color: #333; 
            font-weight: 900; 
            margin-bottom: 50px; 
        }
        .logo-text { 
            color: #FFD700; 
            text-shadow: 1px 1px 0 #000;
        }
        .actor-card { 
            background: #ffffff; 
            border: 2px solid #f1f1f1; 
            border-radius: 25px; 
            transition: 0.3s; 
            padding: 40px 20px; 
            text-align: center; 
            height: 100%; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.02); 
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .actor-card:hover { 
            border-color: #FFD700; 
            transform: translateY(-10px); 
            box-shadow: 0 15px 30px rgba(255, 215, 0, 0.15); 
        }
        .actor-icon { 
            font-size: 3.5rem; 
            color: #FFD700; 
            margin-bottom: 20px; 
        }
        .btn-select { 
            background-color: #FFD700; 
            color: #000; 
            border-radius: 50px; 
            font-weight: 700; 
            border: none; 
            padding: 12px; 
            width: 100%; 
            margin-top: 20px; 
            text-decoration: none;
            display: block;
            transition: 0.3s;
        }
        .btn-select:hover {
            background-color: #000;
            color: #fff;
        }
        h4 { font-weight: 800; color: #444; }
    </style>
</head>
<body>

    <div class="container text-center">
        <h1 class="main-title">مرحباً بكِ في <span class="logo-text">Edu Bridge</span></h1>
        
        <div class="row g-4 justify-content-center">
            
            <div class="col-md-3">
                <div class="actor-card">
                    <div>
                        <i class="fa-solid fa-chalkboard-user actor-icon"></i>
                        <h4>معلم</h4>
                        <p class="text-muted small">إدارة المحاضرات، الحضور، والطلاب</p>
                    </div>
                    {{-- التعديل هنا: الربط بصفحة اللوغ ان وليس الداش بورد --}}
                    <a href="{{ route('teacher.login') }}" class="btn btn-select">دخول</a>
                </div>
            </div>

            <div class="col-md-3">
                <div class="actor-card">
                    <div>
                        <i class="fa-solid fa-user-tie actor-icon"></i>
                        <h4>رئيس قسم</h4>
                        <p class="text-muted small">متابعة الأقسام والتقارير الأكاديمية</p>
                    </div>
                    <a href="#" class="btn btn-select">دخول</a>
                </div>
            </div>

            <div class="col-md-3">
                <div class="actor-card">
                    <div>
                        <i class="fa-solid fa-file-invoice actor-icon"></i>
                        <h4>شؤون طلاب</h4>
                        <p class="text-muted small">إدارة التسجيل والوثائق الطلابية</p>
                    </div>
                    <a href="#" class="btn btn-select">دخول</a>
                </div>
            </div>

            <div class="col-md-3">
                <div class="actor-card">
                    <div>
                        <i class="fa-solid fa-shield-halved actor-icon"></i>
                        <h4>الإدارة</h4>
                        <p class="text-muted small">التحكم الكامل بالنظام والصلاحيات</p>
                    </div>
                    <a href="#" class="btn btn-select">دخول</a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>