<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة المعلم - تسجيل الدخول</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
        body { font-family: 'Tajawal', sans-serif; }
        .theme-yellow { background-color: #f6f021; }
        .theme-yellow-hover { background-color: #e8e21f; }
        .welcome-bg { background-color: #ffffff; } 
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white rounded-[3rem] shadow-2xl flex w-full max-w-5xl overflow-hidden min-h-[600px] border border-gray-100">
        
        <div class="hidden md:flex w-1/2 welcome-bg p-12 flex flex-col justify-center items-center text-center border-l border-gray-100 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-yellow-50 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-yellow-50 rounded-full blur-3xl"></div>

            <div class="bg-yellow-400 w-32 h-32 rounded-3xl flex items-center justify-center mb-8 shadow-lg transform rotate-3">
                <i class="fas fa-chalkboard-teacher text-white text-6xl -rotate-3"></i>
            </div>
            
            <h1 class="text-4xl font-extrabold mb-5 leading-tight text-gray-800">بوابة <span class="text-yellow-500">المعلم</span></h1>
            <p class="text-lg text-gray-500 font-medium">نظام إدارة المحتوى التعليمي<br>معهد Edu Bridge</p>
            
            <div class="w-16 h-1.5 theme-yellow mt-6 rounded-full"></div>
        </div>

        <div class="w-full md:w-1/2 p-12 lg:p-16 flex flex-col justify-center bg-white">
            <div class="mb-10 text-right">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">تسجيل الدخول</h2>
                <p class="text-gray-500">يرجى إدخال بيانات حسابك كمعلم</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border-r-4 border-red-500 p-4 mb-6 rounded-xl text-right">
                    <p class="text-red-700 font-bold text-sm">{{ $errors->first() }}</p>
                </div>
            @endif

            <form action="{{ route('teacher.login.post') }}" method="POST">
                @csrf
                
                <div class="mb-6 relative text-right">
                    <label class="block text-gray-700 font-bold mb-2 mr-2">اسم المستخدم</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="text" name="username" value="{{ old('username') }}" required
                               class="w-full pl-12 pr-6 py-4 border-2 border-gray-100 rounded-2xl text-right bg-gray-50 focus:outline-none focus:border-yellow-400 focus:bg-white transition-all" 
                               placeholder="مثال: heba_2024">
                    </div>
                </div>

                <div class="mb-6 relative text-right">
                    <label class="block text-gray-700 font-bold mb-2 mr-2">كلمة المرور</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="password" name="password" required 
                               class="w-full pl-12 pr-6 py-4 border-2 border-gray-100 rounded-2xl text-right bg-gray-50 focus:outline-none focus:border-yellow-400 focus:bg-white transition-all" 
                               placeholder="*********">
                    </div>
                </div>

                <div class="mb-8 text-right px-2">
                    <a href="#" class="text-yellow-600 hover:text-yellow-700 font-bold text-sm">نسيت كلمة المرور؟</a>
                </div>

                <button type="submit" 
                        class="w-full theme-yellow hover:theme-yellow-hover hover:shadow-xl hover:shadow-yellow-200 text-gray-800 font-extrabold py-4 rounded-2xl text-xl transition-all duration-300 flex items-center justify-center">
                    دخول للمنصة
                    <i class="fas fa-sign-in-alt mr-3"></i>
                </button>
            </form>
        </div>
    </div>

</body>
</html>