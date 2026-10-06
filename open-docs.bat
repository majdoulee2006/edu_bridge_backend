@echo off
chcp 65001 >nul
title Edu-Bridge Docs
cd /d "%~dp0"
echo Opening documentation at http://127.0.0.1:8765  (close this window to stop)
start "" /min cmd /c "ping 127.0.0.1 -n 6 >nul & start http://127.0.0.1:8765"
python -m mkdocs serve -a 127.0.0.1:8765
pause
