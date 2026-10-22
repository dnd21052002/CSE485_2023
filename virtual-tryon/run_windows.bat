@echo off
cd /d "%~dp0"
call .venv\Scripts\activate.bat
start "" http://localhost:8000
python server\app.py
