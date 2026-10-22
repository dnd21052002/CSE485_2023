#!/usr/bin/env bash
# Cài đặt trên Linux / WSL2 (khuyên dùng: có đủ AutoMasker cho mask đẹp nhất).
# Yêu cầu: Python 3.9 (detectron2 đi kèm CatVTON build sẵn cho 3.9), driver NVIDIA mới.
set -euo pipefail
cd "$(dirname "$0")"

PY=${PYTHON:-python3.9}
$PY -m venv .venv
source .venv/bin/activate
pip install --upgrade pip
pip install torch==2.4.0 torchvision==0.19.0 --index-url https://download.pytorch.org/whl/cu121
pip install -r server/requirements.txt

mkdir -p server/third_party
if [ ! -d server/third_party/CatVTON ]; then
  git clone --depth 1 https://github.com/Zheng-Chong/CatVTON server/third_party/CatVTON
fi

echo
echo "Xong. Chạy:  source .venv/bin/activate && python server/app.py"
echo "Lần đầu sẽ tự tải ~6GB model từ HuggingFace (miễn phí)."
