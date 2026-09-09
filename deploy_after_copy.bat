@echo off
setlocal enabledelayedexpansion

cd /d "%~dp0"

echo ============================================
echo   Preparing project after copy to new PC
echo ============================================
echo.

rem --- locate php.exe ---
where php >nul 2>&1
if %errorlevel%==0 (
    set "PHP_EXE=php"
) else if exist "C:\xampp\php\php.exe" (
    set "PHP_EXE=C:\xampp\php\php.exe"
) else (
    echo [ERROR] Could not find php.exe in PATH or at C:\xampp\php\php.exe
    echo Edit this file and set the correct path to php.exe on this PC.
    pause
    exit /b 1
)

echo Clearing old cache (config, routes, views)...
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

echo.
echo ============================================
echo   Done - the project is ready on this PC.
echo   If error 419 still happens, close all open
echo   browser tabs for the site and open it fresh.
echo ============================================
pause
