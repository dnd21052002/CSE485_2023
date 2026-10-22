# Phòng thử đồ ảo realtime — chạy 100% local, miễn phí

Website thử đồ ảo chạy trên máy cá nhân (i9-12900 + RTX 3060 12GB). Không gọi API trả phí nào.
Sau khi tải model lần đầu, web chạy được cả khi không có mạng (trừ thư viện MediaPipe đang lấy từ CDN, xem phần "Chạy offline hoàn toàn").

## 1. Kết quả nghiên cứu (tóm tắt)

### Decart / Anywear làm thế nào?
- Anywear chạy model **Lucy VTON** (bản thương mại, mã nguồn đóng) của Decart. Đây là một model **video diffusion realtime**: camera gửi lên server qua WebRTC, server sinh lại từng khung hình với bộ đồ mới rồi stream về.
- Model chạy trên cụm GPU datacenter của Decart (cỡ H100) với kernel tối ưu riêng. Bản dành cho dev tính **~$0.02/giây** (qua Decart/fal.ai). Anywear miễn phí lúc beta vì Decart đang trả tiền GPU.
- **Không có trọng số public.** Không thể tải Lucy về chạy trên RTX 3060.

### Open-source có gì có thể thay thế?
| Model | Loại | VRAM | Chạy được trên 3060? | Ghi chú |
|---|---|---|---|---|
| **CatVTON** (ICLR 2025) | ảnh, diffusion | < 8GB ở 768×1024 | ✅ **đang dùng** | Nhẹ nhất mà vẫn đẹp, 899M tham số |
| IDM-VTON | ảnh, SDXL | ~16GB+ | ⚠️ phải lượng tử hoá/offload, chậm | Đẹp hơn với chi tiết logo |
| OOTDiffusion | ảnh, SD1.5 | ~10GB | ✅ | Chất lượng thấp hơn CatVTON |
| Leffa | ảnh | ~10GB | ✅ | Có thể dùng thay CatVTON |
| MagicTryOn, ViViD | **video** diffusion (Wan2.1…) | 24–80GB | ❌ | Xử lý offline, mất vài phút/clip |

**Nói thẳng:** chưa có model mở nào đạt được "video diffusion realtime chất lượng Anywear" trên một card 12GB. Model mạnh nhất chạy được trên 3060 là try-on **ảnh tĩnh**, mỗi ảnh mất từ dưới 1 giây (ảnh nhỏ, ít bước) đến hơn chục giây (ảnh nét, nhiều bước).

### Kiến trúc chọn: hybrid 2 tầng
```
Webcam ─► Trình duyệt ──────────────────────────────────────────────┐
           │ MediaPipe Pose (33 khớp) + Selfie-multiclass segmentation │  ~30 FPS
           │ WebGL: warp lưới áo theo vai/hông, tay áo bám cánh tay,   │  (tầng 1: AR)
           │ tóc/mặt/cẳng tay che áo, ánh sáng camera đổ lên áo        │
           └──► khung hình 3:4 + mask ──► FastAPI (localhost) ────────┘
                                          CatVTON trên RTX 3060      ~0.5–15s/ảnh
                                          (tầng 2: AI, gần chất lượng Anywear)
```
- **Tầng 1 – Live AR (realtime thật):** chạy trong trình duyệt và dùng GPU qua WebGL. Áo được dán và uốn theo cơ thể ở ~30 FPS. Vì là warp 2D nên không có nếp vải thật, nhưng phản hồi tức thì.
- **Tầng 2 – AI try-on (CatVTON):** chụp khung hình hiện tại rồi sinh ảnh mặc đồ có nếp vải, bóng đổ và độ vừa người thật. Đây là phần gần với chất lượng Anywear nhất.
  - *Chụp & thử bằng AI*: ảnh 576×768 hoặc 768×1024.
  - *Live AI*: gửi liên tục ở 384×512, 12 bước, để có một luồng "AI view" cập nhật liên tục. Tốc độ phụ thuộc GPU, chưa phải 30 FPS.

## 2. Cài đặt

Yêu cầu: driver NVIDIA mới (CUDA 12.x), Git, Python **3.9** (khuyên dùng, vì AutoMasker cần bản này) hoặc 3.10, Chrome/Edge.

### Cách A — WSL2 / Linux (khuyên dùng, có AutoMasker cho mask đẹp nhất)
```bash
cd virtual-tryon
./setup.sh                 # tạo venv, cài torch CUDA 12.1, clone CatVTON
source .venv/bin/activate
python server/app.py       # mở http://localhost:8000
```

### Cách B — Windows native
```
setup_windows.bat
run_windows.bat
```
Trên Windows, AutoMasker (DensePose của detectron2) thường không chạy. Khi đó server tự chuyển sang **mask do trình duyệt tạo** bằng MediaPipe. Kết quả vẫn tốt, chỉ kém hơn một chút ở viền áo.

Lần chạy đầu server tự tải khoảng 6GB model từ HuggingFace (miễn phí), gồm SD-inpainting, CatVTON, DensePose và SCHP. Khi badge **AI ✓** chuyển xanh là dùng được.

## 3. Sử dụng
1. Đứng cách camera khoảng 1.5–2m, thấy được từ đầu tới hông (thấy cả chân nếu thử quần).
2. Chọn đồ có sẵn, hoặc **tải ảnh / kéo thả** ảnh sản phẩm vào khung camera. Ảnh tốt nhất là chụp phẳng, chính diện, nền trắng; nền sẽ được tự tách.
3. Xem AR realtime bên trái. Nếu áo lệch, mở *Tinh chỉnh AR* để chỉnh độ rộng, độ dài và góc tay áo.
4. Bấm **Chụp & thử bằng AI** để có ảnh chất lượng cao, hoặc bật **Live AI**.

## 4. Tối ưu cho RTX 3060
| Preset | Độ phân giải | Bước | Ước tính trên 3060* |
|---|---|---|---|
| Nhanh / Live AI | 384×512 | 12 | ~1 giây |
| Cân bằng | 576×768 | 25 | ~4–6 giây |
| Đẹp nhất | 768×1024 | 50 | ~15–20 giây |

\*Ước tính dựa trên số tham số và độ phân giải, **chưa đo trên máy thật**. Thời gian GPU thật được hiện dưới ảnh kết quả.

Biến môi trường:
- `CATVTON_PRECISION=bf16|fp16|no` (mặc định bf16)
- `CATVTON_COMPILE=1`: bật torch.compile. Chỉ hoạt động trên Linux/WSL, nhanh thêm khoảng 20–30% sau lần chạy đầu.
- `CATVTON_AUTOMASKER=0`: tắt DensePose/SCHP để tiết kiệm khoảng 1.5GB VRAM.
- `CATVTON_SKIP_SAFETY=1`: bỏ safety checker. Tiết kiệm VRAM và tránh ảnh đen do nhận nhầm.

### Hướng nâng cấp để Live AI nhanh hơn
- Biên dịch UNet sang **TensorRT** (theo kiểu StreamDiffusion), ước tính nhanh hơn 2–3 lần.
- Distill CatVTON xuống 4–8 bước (LCM/Turbo LoRA). Cần tự train vì hiện chưa có bản public.
- Chỉ sinh lại vùng thân và tái sử dụng latent giữa các khung hình liền nhau.

## 5. Chạy offline hoàn toàn
MediaPipe đang được tải từ jsDelivr và Google Storage. Để chạy offline, tải các file sau về `web/vendor/` rồi sửa 3 hằng số URL ở đầu `web/js/tracker.js`:
`vision_bundle.mjs`, thư mục `wasm/` của `@mediapipe/tasks-vision@0.10.18`, `pose_landmarker_full.task`, `selfie_multiclass_256x256.tflite`.

## 6. Giấy phép
- Code trong thư mục này: dùng tự do.
- **CatVTON: CC BY-NC-SA 4.0, chỉ được dùng phi thương mại.** Muốn bán hoặc dùng cho shop thì phải xin phép tác giả, hoặc đổi sang model có license thương mại.
- MediaPipe: Apache 2.0.

## Cấu trúc
```
virtual-tryon/
├── server/app.py          FastAPI: phục vụ web + /api/tryon (CatVTON) + /api/health
├── server/requirements.txt
├── setup.sh / setup_windows.bat / run_windows.bat
└── web/
    ├── index.html, style.css
    ├── js/tracker.js      MediaPipe pose + segmentation + lọc rung One-Euro
    ├── js/renderer.js     WebGL warp áo, che khuất, ánh sáng
    ├── js/garments.js     tách nền ảnh sản phẩm, crop, tự dò điểm neo
    ├── js/main.js         ghép camera ↔ AR ↔ AI, tạo mask cho CatVTON
    └── garments/          mẫu đồ để test
```
