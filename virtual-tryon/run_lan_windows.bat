@echo off
REM Cho phep dien thoai cung Wi-Fi truy cap (HTTPS tu ky, camera bat buoc HTTPS)
cd /d "%~dp0"
call .venv\Scripts\activate.bat
set LAN=1
python server\app.py
