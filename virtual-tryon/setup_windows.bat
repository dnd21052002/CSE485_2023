@echo off
REM Cai dat tren Windows native. Can: Python 3.9/3.10 (python.org), Git, driver NVIDIA moi.
REM Tren Windows, AutoMasker (DensePose) thuong khong chay -> web tu tao mask bang MediaPipe.
cd /d "%~dp0"

python -m venv .venv
call .venv\Scripts\activate.bat
python -m pip install --upgrade pip
pip install torch==2.4.0 torchvision==0.19.0 --index-url https://download.pytorch.org/whl/cu121
pip install -r server\requirements.txt

if not exist server\third_party mkdir server\third_party
if not exist server\third_party\CatVTON git clone --depth 1 https://github.com/Zheng-Chong/CatVTON server\third_party\CatVTON

echo.
echo Xong. Chay: run_windows.bat
pause
