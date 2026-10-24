@echo off
setlocal enabledelayedexpansion
title EduBridge - Workers and Background Services
cd /d "%~dp0.."

call :find_php
if "%PHP_EXE%"=="" (
    echo [ERROR] Could not find PHP executable!
    echo Please make sure XAMPP is installed.
    pause
    exit /b 1
)

echo ============================================================
echo   EduBridge - Background Workers and Real-time Services
echo ============================================================
echo.

echo [1/2] Launching Queue Worker in background...
start "[EduBridge] Queue Worker" "%PHP_EXE%" artisan queue:work --tries=3 --timeout=90
echo       - Queue Worker started successfully.
echo.

echo [2/2] Checking Reverb WebSockets Server...
"%PHP_EXE%" artisan list | findstr /i "reverb:start" >nul 2>&1
if %errorlevel%==0 (
    echo       - Reverb found. Launching WebSocket server on port 8080...
    start "[EduBridge] Reverb Server" "%PHP_EXE%" artisan reverb:start --host=0.0.0.0 --port=8080
    echo       - Reverb Server started on port 8080.
) else (
    echo       - Note: Laravel Reverb is not installed. Pusher is used instead.
    echo       - To install it later: composer require laravel/reverb
)

echo.
echo ============================================================
echo   [SUCCESS] All background services are running!
echo   - Keep background windows minimized while using the system.
echo ============================================================
echo.
pause
exit /b 0

:find_php
where php >nul 2>&1
if %errorlevel%==0 (
    set "PHP_EXE=php"
) else if exist "C:\xampp 8.2\php\php.exe" (
    set "PHP_EXE=C:\xampp 8.2\php\php.exe"
) else if exist "C:\xampp\php\php.exe" (
    set "PHP_EXE=C:\xampp\php\php.exe"
) else (
    set "PHP_EXE="
)
exit /b 0
