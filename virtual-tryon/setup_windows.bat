@echo off
REM Cai dat tren Windows native. Can: Python 3.10 (python.org, co py launcher), Git, driver NVIDIA moi.
REM Tren Windows, AutoMasker (DensePose) thuong khong chay -> web tu tao mask bang MediaPipe.
cd /d "%~dp0"

py -3.10 --version >nul 2>&1 || (echo [LOI] Khong tim thay Python 3.10. Cai: winget install -e --id Python.Python.3.10 & exit /b 1)

if exist .venv\Scripts\python.exe (
  .venv\Scripts\python.exe -c "import sys; sys.exit(0 if sys.version_info[:2]==(3,10) else 1)" || (echo Xoa .venv sai phien ban Python... & rmdir /s /q .venv)
)
if not exist .venv\Scripts\python.exe py -3.10 -m venv .venv || exit /b 1
set PY=.venv\Scripts\python.exe

%PY% -m pip install --upgrade pip || exit /b 1
REM torch 2.4.1 (2.4.0 thieu libomp140.x86_64.dll tren Windows -> loi fbgemm.dll)
%PY% -m pip install torch==2.4.1 torchvision==0.19.1 --index-url https://download.pytorch.org/whl/cu121 || exit /b 1
%PY% -m pip install -r server\requirements.txt || exit /b 1

if not exist server\third_party mkdir server\third_party
if not exist server\third_party\CatVTON git clone --depth 1 https://github.com/Zheng-Chong/CatVTON server\third_party\CatVTON || exit /b 1

%PY% -c "import torch; print('torch', torch.__version__, 'CUDA:', torch.cuda.is_available())" || exit /b 1
echo.
echo Xong. Chay: run_windows.bat
