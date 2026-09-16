@echo off
rem ============================================================
rem  run_server_forever.bat
rem  بيشغّل سيرفر Laravel، وإذا وقع أو انسكر لأي سبب، بيعيد تشغيله
rem  تلقائياً من جديد بدون توقف. ما تشغليه يدوياً — بيشتغل لحاله
rem  عن طريق setup_autostart.bat
rem  🔧 الملف صار جوا مجلد فرعي (سيرفر)، فلازم نطلع درجة وحدة لفوق
rem  لنوصل لمجلد المشروع الرئيسي يلي فيه ملف artisan
rem ============================================================
cd /d "%~dp0.."

:loop
where php >nul 2>&1
if %errorlevel%==0 (
    php artisan serve --host=0.0.0.0 --port=8001
) else (
    "C:\xampp\php\php.exe" artisan serve --host=0.0.0.0 --port=8001
)

echo [%date% %time%] Server stopped unexpectedly, restarting in 3 seconds...
timeout /t 3 /nobreak >nul
goto loop
