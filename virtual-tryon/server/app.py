"""
Local virtual try-on server (100% offline sau khi tải model, không dùng API trả phí).

- Phục vụ web tĩnh trong ../web
- POST /api/tryon : chạy CatVTON (diffusion try-on) trên GPU local
- GET  /api/health: trạng thái model / GPU

Chạy:  python server/app.py   (mặc định http://localhost:8000)
"""
import io
import os
import sys
import threading
import time
import traceback
from pathlib import Path
from typing import Optional

import numpy as np
import torch
import uvicorn
from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from fastapi.responses import JSONResponse, Response
from fastapi.staticfiles import StaticFiles
from PIL import Image, ImageFilter

ROOT = Path(__file__).resolve().parent
WEB_DIR = ROOT.parent / "web"
CATVTON_DIR = Path(os.environ.get("CATVTON_DIR", ROOT / "third_party" / "CatVTON"))
sys.path.insert(0, str(CATVTON_DIR))

BASE_MODEL = os.environ.get("CATVTON_BASE", "booksforcharlie/stable-diffusion-inpainting")
ATTN_REPO = os.environ.get("CATVTON_ATTN", "zhengchong/CatVTON")
# bf16 chạy tốt trên RTX 30xx (Ampere). Đặt CATVTON_PRECISION=fp16 nếu muốn thử.
PRECISION = os.environ.get("CATVTON_PRECISION", "bf16")
USE_AUTOMASKER = os.environ.get("CATVTON_AUTOMASKER", "1") == "1"
USE_COMPILE = os.environ.get("CATVTON_COMPILE", "0") == "1"
SKIP_SAFETY = os.environ.get("CATVTON_SKIP_SAFETY", "0") == "1"
PORT = int(os.environ.get("PORT", "8000"))

state = {"ready": False, "error": None, "automasker": False, "loading": False}
pipeline = None
automasker = None
mask_processor = None
gpu_lock = threading.Lock()


def load_models():
    global pipeline, automasker, mask_processor
    state["loading"] = True
    try:
        from diffusers.image_processor import VaeImageProcessor
        from huggingface_hub import snapshot_download
        from model.pipeline import CatVTONPipeline
        from utils import init_weight_dtype

        if not torch.cuda.is_available():
            raise RuntimeError("Không tìm thấy CUDA GPU. Kiểm tra driver NVIDIA + bản torch CUDA.")

        repo_path = snapshot_download(repo_id=ATTN_REPO)
        pipeline = CatVTONPipeline(
            base_ckpt=BASE_MODEL,
            attn_ckpt=repo_path,
            attn_ckpt_version="mix",
            weight_dtype=init_weight_dtype(PRECISION),
            use_tf32=True,
            compile=USE_COMPILE,
            skip_safety_check=SKIP_SAFETY,
            device="cuda",
        )
        mask_processor = VaeImageProcessor(
            vae_scale_factor=8, do_normalize=False, do_binarize=True, do_convert_grayscale=True
        )

        if USE_AUTOMASKER:
            # AutoMasker (DensePose + SCHP) cho mask chính xác nhất. Bản detectron2 đi kèm
            # CatVTON chỉ có binary cho Linux/Python 3.9 -> trên Windows native sẽ fallback
            # sang mask do trình duyệt tạo từ MediaPipe.
            try:
                from model.cloth_masker import AutoMasker

                automasker = AutoMasker(
                    densepose_ckpt=os.path.join(repo_path, "DensePose"),
                    schp_ckpt=os.path.join(repo_path, "SCHP"),
                    device="cuda",
                )
                state["automasker"] = True
            except Exception as e:  # noqa: BLE001
                print(f"[warn] AutoMasker không khả dụng, dùng mask từ client: {e}")

        # Warm-up để lần chạy đầu không bị chậm
        with gpu_lock:
            dummy = Image.new("RGB", (384, 512), "white")
            m = Image.new("L", (384, 512), 255)
            pipeline(image=dummy, condition_image=dummy, mask=m, num_inference_steps=2,
                     guidance_scale=2.5, height=512, width=384)
        state["ready"] = True
        print("[ok] CatVTON sẵn sàng.")
    except Exception as e:  # noqa: BLE001
        traceback.print_exc()
        state["error"] = str(e)
    finally:
        state["loading"] = False


def read_image(upload: UploadFile, mode: str) -> Image.Image:
    data = upload.file.read()
    img = Image.open(io.BytesIO(data))
    if mode == "RGB" and img.mode in ("RGBA", "LA", "P"):
        # Ảnh áo PNG trong suốt -> nền trắng (CatVTON train với nền trắng)
        img = img.convert("RGBA")
        bg = Image.new("RGBA", img.size, (255, 255, 255, 255))
        img = Image.alpha_composite(bg, img)
    return img.convert(mode)


app = FastAPI(title="Local Virtual Try-On")


@app.get("/api/health")
def health():
    info = {**state}
    if torch.cuda.is_available():
        free, total = torch.cuda.mem_get_info()
        info["gpu"] = torch.cuda.get_device_name(0)
        info["vram_free_gb"] = round(free / 1e9, 2)
        info["vram_total_gb"] = round(total / 1e9, 2)
    return info


@app.post("/api/tryon")
def tryon(
    person: UploadFile = File(...),
    garment: UploadFile = File(...),
    mask: Optional[UploadFile] = File(None),
    cloth_type: str = Form("upper"),
    steps: int = Form(30),
    guidance: float = Form(2.5),
    width: int = Form(576),
    height: int = Form(768),
    seed: int = Form(42),
    mask_mode: str = Form("auto"),  # auto | client
):
    if not state["ready"]:
        raise HTTPException(503, detail=state["error"] or "Model đang tải, thử lại sau.")
    if cloth_type not in ("upper", "lower", "overall"):
        raise HTTPException(400, detail="cloth_type phải là upper | lower | overall")

    from utils import resize_and_crop, resize_and_padding

    width = max(256, min(1024, width // 8 * 8))
    height = max(256, min(1360, height // 8 * 8))
    steps = max(1, min(100, steps))

    person_img = resize_and_crop(read_image(person, "RGB"), (width, height))
    garment_img = resize_and_padding(read_image(garment, "RGB"), (width, height))

    t0 = time.perf_counter()
    with gpu_lock:
        if automasker is not None and (mask_mode == "auto" or mask is None):
            mask_img = automasker(person_img, cloth_type)["mask"]
        elif mask is not None:
            mask_img = resize_and_crop(read_image(mask, "L"), (width, height))
            mask_img = Image.fromarray((np.array(mask_img) > 127).astype(np.uint8) * 255)
        else:
            raise HTTPException(400, detail="Không có AutoMasker và client không gửi mask.")
        mask_img = mask_processor.blur(mask_img, blur_factor=9)

        generator = torch.Generator(device="cuda").manual_seed(seed) if seed >= 0 else None
        result = pipeline(
            image=person_img,
            condition_image=garment_img,
            mask=mask_img,
            num_inference_steps=steps,
            guidance_scale=guidance,
            height=height,
            width=width,
            generator=generator,
        )[0]
    elapsed_ms = int((time.perf_counter() - t0) * 1000)

    # Giữ nguyên vùng ngoài mask = ảnh gốc (tránh model làm đổi mặt/nền)
    soft = mask_img.convert("L").filter(ImageFilter.GaussianBlur(4))
    result = Image.composite(result, person_img, soft)

    buf = io.BytesIO()
    result.save(buf, format="JPEG", quality=92)
    return Response(
        content=buf.getvalue(),
        media_type="image/jpeg",
        headers={"X-Inference-Ms": str(elapsed_ms), "Cache-Control": "no-store"},
    )


@app.exception_handler(Exception)
def on_error(_, exc: Exception):
    traceback.print_exc()
    return JSONResponse(status_code=500, content={"detail": str(exc)})


app.mount("/", StaticFiles(directory=WEB_DIR, html=True), name="web")

if __name__ == "__main__":
    threading.Thread(target=load_models, daemon=True).start()
    print(f"Mở trình duyệt: http://localhost:{PORT}")
    uvicorn.run(app, host="127.0.0.1", port=PORT, log_level="info")
