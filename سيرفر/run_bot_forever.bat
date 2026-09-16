@echo off
rem ============================================================
rem  run_bot_forever.bat
rem  بيشغّل بوت تيليغرام (طريقة البولينغ)، وإذا وقع أو انسكر بيعيد
rem  تشغيله تلقائياً من جديد بدون توقف. ما تشغليه يدوياً — بيشتغل
rem  لحاله عن طريق setup_autostart.bat
rem  🔧 الملف صار جوا مجلد فرعي (سيرفر)، فلازم نطلع درجة وحدة لفوق
rem  لنوصل لمجلد المشروع الرئيسي يلي فيه ملف artisan
rem ============================================================
cd /d "%~dp0.."

:loop
where php >nul 2>&1
if %errorlevel%==0 (
    php artisan telegram:poll
) else (
    "C:\xampp\php\php.exe" artisan telegram:poll
)

echo [%date% %time%] Bot stopped unexpectedly, restarting in 3 seconds...
timeout /t 3 /nobreak >nul
goto loop
