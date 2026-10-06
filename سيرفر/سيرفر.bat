@echo off
rem ============================================================
rem  سيرفر.bat  -  ملف واحد بدل 4 ملفات
rem
rem  الاستخدام (على كمبيوتر المعهد):
rem    كليك يمين على هذا الملف  ->  Run as administrator   (مرة وحدة بعد كل رفع)
rem
rem  شو بيعمل بالترتيب:
rem    1) يجهز المشروع: يمسح الكاش (config / routes / views / cache)
rem       ويتأكد من وجود مجلدات storage المطلوبة
rem    2) يسجل مهمتين بجدول مهام ويندوز (يشتغلوا لحالهم مع كل تشغيل للجهاز):
rem         - EduBridge Server        -> سيرفر Laravel على بورت 8000
rem         - EduBridge Telegram Bot  -> بوت تيليغرام (telegram:poll)
rem       وكل مهمة بتعيد تشغيل نفسها تلقائياً إذا وقعت
rem    3) يشغلهم فوراً بدون انتظار إعادة تشغيل الجهاز
rem
rem  (الوضعين "server" و "bot" بيستدعيهم جدول المهام تلقائياً، ما تشغلهم يدوياً)
rem  الملف موجود جوا مجلد "سيرفر"، فبيطلع درجة لفوق لمجلد المشروع الرئيسي (فيه artisan)
rem ============================================================
chcp 65001 >nul
setlocal enabledelayedexpansion
set "SCRIPT=%~f0"
cd /d "%~dp0.."

if /i "%~1"=="server" goto run_server
if /i "%~1"=="bot"    goto run_bot
goto setup

rem ------------------------------------------------------------
:find_php
where php >nul 2>&1
if %errorlevel%==0 (
    set "PHP_EXE=php"
) else if exist "C:\xampp\php\php.exe" (
    set "PHP_EXE=C:\xampp\php\php.exe"
) else (
    set "PHP_EXE="
)
exit /b 0

rem ------------------------------------------------------------
:run_server
call :find_php
if "%PHP_EXE%"=="" exit /b 1
:server_loop
"%PHP_EXE%" artisan serve --host=0.0.0.0 --port=8000
echo [%date% %time%] Server stopped unexpectedly, restarting in 3 seconds...
timeout /t 3 /nobreak >nul
goto server_loop

rem ------------------------------------------------------------
:run_bot
call :find_php
if "%PHP_EXE%"=="" exit /b 1
:bot_loop
"%PHP_EXE%" artisan telegram:poll
echo [%date% %time%] Bot stopped unexpectedly, restarting in 3 seconds...
timeout /t 3 /nobreak >nul
goto bot_loop

rem ------------------------------------------------------------
:setup
net session >nul 2>&1
if not %errorlevel%==0 (
    echo.
    echo [ERROR] Run this file as Administrator:
    echo         right-click it, then choose "Run as administrator".
    echo.
    pause
    exit /b 1
)

echo ============================================
echo   Step 1/3 - Preparing the project
echo ============================================
call :find_php
if "%PHP_EXE%"=="" (
    echo [ERROR] Could not find php.exe in PATH or at C:\xampp\php\php.exe
    echo Edit this file and set the correct path to php.exe on this PC.
    pause
    exit /b 1
)

echo Clearing old cache (config, routes, views, cache)...
"%PHP_EXE%" artisan config:clear
"%PHP_EXE%" artisan route:clear
"%PHP_EXE%" artisan view:clear
"%PHP_EXE%" artisan cache:clear

echo.
echo Making sure required storage folders exist...
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"
if not exist "storage\logs" mkdir "storage\logs"
if not exist "storage\app\app-release" mkdir "storage\app\app-release"

echo.
echo ============================================
echo   Step 2/3 - Registering auto-start tasks
echo ============================================
schtasks /create /tn "EduBridge Server" /tr "\"%SCRIPT%\" server" /sc onstart /ru SYSTEM /rl highest /f
schtasks /create /tn "EduBridge Telegram Bot" /tr "\"%SCRIPT%\" bot" /sc onstart /ru SYSTEM /rl highest /f

echo.
echo ============================================
echo   Step 3/3 - Starting them now
echo ============================================
schtasks /run /tn "EduBridge Server"
schtasks /run /tn "EduBridge Telegram Bot"

echo.
echo ============================================
echo   DONE.
echo   The server and the Telegram bot now start automatically
echo   with the computer and restart themselves if they stop.
echo   Make sure MySQL is always running from XAMPP Control Panel.
echo   If error 419 appears, close all browser tabs of the site
echo   and open it fresh.
echo ============================================
pause
exit /b 0
