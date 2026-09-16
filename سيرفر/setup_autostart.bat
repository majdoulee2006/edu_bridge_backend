@echo off
rem ============================================================
rem  setup_autostart.bat
rem  شغّليه مرة وحدة بس على كمبيوتر المعهد (بعد كل نسخ جديد للمشروع
rem  لجهاز جديد). بيسجّل مهمتين بجدول مهام ويندوز (Task Scheduler)
rem  تخلي السيرفر وبوت تيليغرام يشتغلوا تلقائياً من غير ما حدا
rem  يفوت عالكمبيوتر أو يدوس أي زر، حتى لو أعاد الجهاز تشغيل نفسو.
rem
rem  ملاحظة مهمة: لازم تشغلي هالملف بصلاحيات "Run as Administrator"
rem  (كليك يمين عليه ⇦ Run as administrator)، وإلا رح يفشل التسجيل.
rem ============================================================

net session >nul 2>&1
if not %errorlevel%==0 (
    echo.
    echo [خطأ] لازم تشغلي هالملف بصلاحيات مدير.
    echo كليك يمين على setup_autostart.bat ثم اختاري "Run as administrator"
    echo.
    pause
    exit /b 1
)

cd /d "%~dp0"
set "PROJECT_DIR=%~dp0"

echo تسجيل مهمة تشغيل السيرفر تلقائياً عند فتح الكمبيوتر...
schtasks /create /tn "EduBridge Server" /tr "\"%PROJECT_DIR%run_server_forever.bat\"" /sc onstart /ru SYSTEM /rl highest /f

echo تسجيل مهمة تشغيل بوت تيليغرام تلقائياً عند فتح الكمبيوتر...
schtasks /create /tn "EduBridge Telegram Bot" /tr "\"%PROJECT_DIR%run_bot_forever.bat\"" /sc onstart /ru SYSTEM /rl highest /f

echo.
echo ============================================================
echo   تم! السيرفر والبوت هلق مسجلين يشتغلوا تلقائياً مع كل
echo   تشغيل للكمبيوتر، بدون أي نافذة ظاهرة وبدون تدخل يدوي.
echo   (تأكدي إن MySQL شغال من XAMPP Control Panel بشكل دائم كمان)
echo ============================================================
echo.
echo لتشغيلهم الآن فوراً بدون انتظار إعادة تشغيل الكمبيوتر:
schtasks /run /tn "EduBridge Server"
schtasks /run /tn "EduBridge Telegram Bot"

echo.
pause
