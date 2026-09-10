@echo off
title "Edu Bridge - Run Database Migrations"
echo ========================================================
echo        Running Database Migrations
echo ========================================================
echo.
php artisan migrate
echo.
echo ========================================================
echo        Done! Press any key to close.
echo ========================================================
pause
