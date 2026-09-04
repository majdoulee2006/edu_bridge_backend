@echo off
title "Edu Bridge - Bot and Server Launcher"
echo ========================================================
echo        Starting Edu Bridge Backend and Telegram Bot
echo ========================================================
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start_bot.ps1"

pause
