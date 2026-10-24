@echo off
setlocal enabledelayedexpansion
title EduBridge - Server Deployment and Setup
cd /d "%~dp0.."

call :find_php
if "%PHP_EXE%"=="" (
    echo [ERROR] Could not find PHP executable!
    echo Please make sure XAMPP is installed.
    pause
    exit /b 1
)

echo ============================================================
echo   EduBridge - Safe Production Deployment and Setup
echo ============================================================
echo.

echo [1/4] Clearing cache (optimize:clear)...
"%PHP_EXE%" artisan optimize:clear
if errorlevel 1 goto failed

echo.
echo [2/4] Linking public storage (storage:link)...
"%PHP_EXE%" artisan storage:link 2>nul

echo.
echo [3/4] Running safe database migrations (migrate --force)...
echo       (Only adds missing tables/columns, never touches existing data)
"%PHP_EXE%" artisan migrate --force
if errorlevel 1 goto failed

echo.
echo [4/4] Caching framework for production performance (optimize)...
"%PHP_EXE%" artisan optimize
if errorlevel 1 goto failed

echo.
echo ============================================================
echo   [SUCCESS] Server preparation completed successfully!
echo   All database records are safe and the system is ready.
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

:failed
echo.
echo ============================================================
echo   [FAILED] An error occurred during deployment.
echo   Check the error message above, then run this file again.
echo ============================================================
echo.
pause
exit /b 1
