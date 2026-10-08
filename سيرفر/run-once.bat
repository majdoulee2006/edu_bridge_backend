@echo off
rem ============================================================
rem  run-once.bat  -  ONE file to run once after uploading to a NEW server
rem  (it replaces run_migrations.bat and run-once.php)
rem
rem  Usage (on the server):
rem    double-click this file, or run it from a terminal
rem    Add /keep to run it WITHOUT deleting itself (for testing only)
rem
rem  What it does, in order:
rem    1) php artisan optimize:clear
rem    2) php artisan migrate --force
rem         (weekly-digest tables + attendance-intelligence tables/columns)
rem    3) php artisan db:seed --class=DigestDemoSeeder --force
rem         (accounts of Abu Omar Al-Haddad + Layan + Omar, this week's
rem          activity, and their weekly digests)
rem    4) php artisan db:seed --class=AttendanceIntelligenceDemoSeeder --force
rem         (demo teacher + 6 weeks of attendance + suspicious cases, so the
rem          attendance heatmap and the flag-review queue have data)
rem
rem  If every step succeeds this file DELETES ITSELF.
rem  If any step fails it stops and keeps itself, so you can fix the
rem  problem and run it again (all steps are safe to repeat).
rem
rem  To add more commands: copy one of the 4 blocks below.
rem ============================================================
chcp 65001 >nul
setlocal
title Edu Bridge - Run Once
cd /d "%~dp0.."

set "KEEP=0"
if /i "%~1"=="/keep" set "KEEP=1"

call :find_php
if "%PHP_EXE%"=="" (
    echo [ERROR] Could not find php.exe in PATH or at C:\xampp\php\php.exe
    echo Edit this file and set the correct path to php.exe on this PC.
    pause
    exit /b 1
)

echo ============================================
echo   [1/4] php artisan optimize:clear
echo ============================================
"%PHP_EXE%" artisan optimize:clear
if errorlevel 1 goto failed

echo.
echo ============================================
echo   [2/4] php artisan migrate --force
echo ============================================
"%PHP_EXE%" artisan migrate --force
if errorlevel 1 goto failed

echo.
echo ============================================
echo   [3/4] php artisan db:seed --class=DigestDemoSeeder --force
echo ============================================
"%PHP_EXE%" artisan db:seed --class=DigestDemoSeeder --force
if errorlevel 1 goto failed

echo.
echo ============================================
echo   [4/4] php artisan db:seed --class=AttendanceIntelligenceDemoSeeder --force
echo ============================================
"%PHP_EXE%" artisan db:seed --class=AttendanceIntelligenceDemoSeeder --force
if errorlevel 1 goto failed

goto success

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
:failed
echo.
echo ============================================
echo   [FAILED] A step failed. Nothing was deleted.
echo   Fix the problem above, then run this file again.
echo ============================================
pause
exit /b 1

rem ------------------------------------------------------------
:success
echo.
echo ============================================
echo   DONE. All steps finished successfully.
echo ============================================
if "%KEEP%"=="1" (
    echo   /keep was used: this file was NOT deleted.
    pause
    exit /b 0
)
echo   This file will now delete itself.
pause
(goto) 2>nul & del "%~f0"
