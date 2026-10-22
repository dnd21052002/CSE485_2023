import { Tracker, LM } from "./tracker.js";
import { Renderer } from "./renderer.js";
import { prepareGarment } from "./garments.js";

const $ = (s) => document.querySelector(s);
const video = $("#video");
const stage = $("#stage");
const aiImg = $("#aiResult");
const renderer = new Renderer(stage);
const tracker = new Tracker();

const PRESETS = {
  live: { width: 384, height: 512, steps: 12 },
  balanced: { width: 576, height: 768, steps: 25 },
  best: { width: 768, height: 1024, steps: 50 },
};

let garments = [];
let current = null;
let lastTrack = null;
let liveAI = false;
let aiBusy = false;

function setStatus(id, text, cls) {
  const el = $(id);
  el.textContent = text;
  el.className = "pill " + (cls || "");
}

// ---------- Camera + vòng lặp AR ----------
async function startCamera() {
  const stream = await navigator.mediaDevices.getUserMedia({
    video: { width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { ideal: 30 }, facingMode: "user" },
    audio: false,
  });
  video.srcObject = stream;
  await video.play();
  setStatus("#camStatus", "Camera ✓", "ok");
}

let frames = 0, fpsT = performance.now();
function loop() {
  const now = performance.now();
  if (video.readyState >= 2) {
    lastTrack = tracker.detect(video, now);
    renderer.render(video, lastTrack);
    frames++;
    if (now - fpsT > 1000) {
      setStatus("#fps", `${Math.round((frames * 1000) / (now - fpsT))} FPS`, lastTrack.landmarks ? "ok" : "warn");
      frames = 0;
      fpsT = now;
    }
  }
  requestAnimationFrame(loop);
}

// ---------- Catalog ----------
function renderCatalog() {
  const grid = $("#catalog");
  grid.innerHTML = "";
  garments.forEach((g, i) => {
    const b = document.createElement("button");
    b.className = "garment" + (g === current ? " active" : "");
    b.innerHTML = `<img src="${g.thumb}" alt=""><span>${g.name}</span>`;
    b.onclick = () => selectGarment(i);
    grid.appendChild(b);
  });
}

function selectGarment(i) {
  current = garments[i];
  renderer.setGarment(current);
  $("#clothType").value = current.type;
  $("#sleeveAngle").value = current.sleeveAngle;
  renderCatalog();
}

async function loadCatalog() {
  const list = await (await fetch("garments/catalog.json")).json();
  for (const item of list) garments.push(await prepareGarment(item.src, item));
  selectGarment(0);
}

$("#upload").onchange = async (e) => {
  for (const f of e.target.files) {
    const g = await prepareGarment(f, { name: f.name.replace(/\.\w+$/, ""), type: $("#clothType").value });
    garments.push(g);
  }
  selectGarment(garments.length - 1);
  e.target.value = "";
};

// Kéo thả ảnh áo từ web/thư mục vào khung hình
stage.parentElement.addEventListener("dragover", (e) => e.preventDefault());
stage.parentElement.addEventListener("drop", async (e) => {
  e.preventDefault();
  const f = [...e.dataTransfer.files].find((x) => x.type.startsWith("image/"));
  if (!f) return;
  garments.push(await prepareGarment(f, { name: f.name.replace(/\.\w+$/, ""), type: $("#clothType").value }));
  selectGarment(garments.length - 1);
});

// ---------- Tham số AR ----------
for (const id of ["width", "length", "offsetY", "shade"]) {
  const el = $("#" + id);
  el.oninput = () => (renderer.params[id] = parseFloat(el.value));
  renderer.params[id] = parseFloat(el.value);
}
$("#flip").onchange = (e) => (renderer.params.flip = e.target.checked);
$("#sleeves").onchange = (e) => (renderer.params.sleeves = e.target.checked);
$("#sleeveAngle").oninput = (e) => current && (current.sleeveAngle = parseFloat(e.target.value));
$("#clothType").onchange = (e) => {
  if (!current) return;
  current.type = e.target.value;
};
$("#showAR").onchange = (e) => (renderer.params.opacity = e.target.checked ? 1 : 0);

// ---------- AI try-on (CatVTON local) ----------
// Cắt khung 3:4 dọc quanh người (CatVTON train ở 3:4)
function cropRect(lm, W, H) {
  const ch = H, cw = Math.min(W, Math.round((H * 3) / 4));
  let cx = W / 2;
  if (lm) cx = ((lm[LM.L_SHOULDER].x + lm[LM.R_SHOULDER].x + lm[LM.L_HIP].x + lm[LM.R_HIP].x) / 4) * W;
  const x = Math.max(0, Math.min(W - cw, Math.round(cx - cw / 2)));
  return { x, y: 0, w: cw, h: ch };
}

// Mask vùng cần thay (dùng khi server không có AutoMasker)
function buildMask(lm, cats, segSize, W, H, type) {
  const m = Object.assign(document.createElement("canvas"), { width: W, height: H });
  const ctx = m.getContext("2d");
  ctx.fillStyle = "#000";
  ctx.fillRect(0, 0, W, H);
  if (!lm) return m;
  const P = (i) => [lm[i].x * W, lm[i].y * H];
  const sw = Math.hypot(P(LM.L_SHOULDER)[0] - P(LM.R_SHOULDER)[0], P(LM.L_SHOULDER)[1] - P(LM.R_SHOULDER)[1]);
  const hipY = (P(LM.L_HIP)[1] + P(LM.R_HIP)[1]) / 2;

  // 1) pixel "quần áo" từ segmenter, giới hạn theo vùng thân trên/dưới
  if (cats) {
    const [sx, sy] = segSize;
    const seg = new ImageData(sx, sy);
    for (let i = 0; i < cats.length; i++) if (cats[i] === 4) seg.data.set([255, 255, 255, 255], i * 4);
    const sc = Object.assign(document.createElement("canvas"), { width: sx, height: sy });
    sc.getContext("2d").putImageData(seg, 0, 0);
    ctx.save();
    ctx.beginPath();
    if (type === "upper") ctx.rect(0, 0, W, hipY + sw * 0.35);
    else if (type === "lower") ctx.rect(0, hipY - sw * 0.35, W, H);
    else ctx.rect(0, 0, W, H);
    ctx.clip();
    ctx.drawImage(sc, 0, 0, W, H);
    ctx.restore();
  }

  // 2) hình thân + tay/chân từ pose (để áo mới rộng hơn áo cũ vẫn vẽ được)
  ctx.strokeStyle = ctx.fillStyle = "#fff";
  ctx.lineCap = ctx.lineJoin = "round";
  const poly = (pts, grow) => {
    ctx.lineWidth = grow;
    ctx.beginPath();
    pts.forEach((p, i) => (i ? ctx.lineTo(...p) : ctx.moveTo(...p)));
    ctx.closePath();
    ctx.fill();
    ctx.stroke();
  };
  const limb = (a, b, wdt) => {
    ctx.lineWidth = wdt;
    ctx.beginPath();
    ctx.moveTo(...P(a));
    ctx.lineTo(...P(b));
    ctx.stroke();
  };
  if (type !== "lower") {
    poly([P(LM.L_SHOULDER), P(LM.R_SHOULDER), P(LM.R_HIP), P(LM.L_HIP)], sw * 0.35);
    limb(LM.L_SHOULDER, LM.L_ELBOW, sw * 0.38);
    limb(LM.R_SHOULDER, LM.R_ELBOW, sw * 0.38);
    limb(LM.L_ELBOW, LM.L_WRIST, sw * 0.3);
    limb(LM.R_ELBOW, LM.R_WRIST, sw * 0.3);
  }
  if (type !== "upper") {
    const hw = sw * 0.45;
    poly([P(LM.L_HIP), P(LM.R_HIP), P(LM.R_KNEE), P(LM.L_KNEE)], hw);
    limb(LM.L_HIP, LM.L_KNEE, hw);
    limb(LM.R_HIP, LM.R_KNEE, hw);
    limb(LM.L_KNEE, LM.L_ANKLE, hw * 0.8);
    limb(LM.R_KNEE, LM.R_ANKLE, hw * 0.8);
  }

  // 3) giữ nguyên mặt + tóc
  if (cats) {
    const [sx, sy] = segSize;
    const keep = new ImageData(sx, sy);
    for (let i = 0; i < cats.length; i++) if (cats[i] === 1 || cats[i] === 3) keep.data.set([0, 0, 0, 255], i * 4);
    const kc = Object.assign(document.createElement("canvas"), { width: sx, height: sy });
    kc.getContext("2d").putImageData(keep, 0, 0);
    ctx.drawImage(kc, 0, 0, W, H);
  }
  return m;
}

const toBlob = (c, type, q) => new Promise((r) => c.toBlob(r, type, q));

async function runAI(presetName) {
  if (!current || aiBusy || !video.videoWidth) return;
  aiBusy = true;
  const preset = PRESETS[presetName];
  const W = video.videoWidth, H = video.videoHeight;
  const lm = lastTrack && lastTrack.landmarks;
  const type = $("#clothType").value;
  const r = cropRect(lm, W, H);

  const frame = Object.assign(document.createElement("canvas"), { width: r.w, height: r.h });
  frame.getContext("2d").drawImage(video, r.x, r.y, r.w, r.h, 0, 0, r.w, r.h);
  const fullMask = buildMask(lm, lastTrack && lastTrack.categories, lastTrack ? lastTrack.segSize : [256, 256], W, H, type);
  const mask = Object.assign(document.createElement("canvas"), { width: r.w, height: r.h });
  mask.getContext("2d").drawImage(fullMask, r.x, r.y, r.w, r.h, 0, 0, r.w, r.h);

  const fd = new FormData();
  fd.append("person", await toBlob(frame, "image/jpeg", 0.92), "person.jpg");
  fd.append("garment", current.blob, "garment.png");
  fd.append("mask", await toBlob(mask, "image/png"), "mask.png");
  fd.append("cloth_type", type);
  fd.append("steps", presetName === "live" ? preset.steps : $("#steps").value || preset.steps);
  fd.append("width", preset.width);
  fd.append("height", preset.height);
  fd.append("guidance", $("#guidance").value);
  fd.append("seed", 42);
  fd.append("mask_mode", $("#maskMode").value);

  const t0 = performance.now();
  try {
    const res = await fetch("/api/tryon", { method: "POST", body: fd });
    if (!res.ok) throw new Error((await res.json().catch(() => ({}))).detail || res.statusText);
    const url = URL.createObjectURL(await res.blob());
    if (aiImg.dataset.url) URL.revokeObjectURL(aiImg.dataset.url);
    aiImg.src = aiImg.dataset.url = url;
    $("#download").href = url;
    $("#aiPanel").classList.add("has-result");
    const gpuMs = res.headers.get("X-Inference-Ms");
    $("#aiInfo").textContent = `GPU ${gpuMs} ms · tổng ${Math.round(performance.now() - t0)} ms · ${preset.width}×${preset.height}`;
  } catch (err) {
    $("#aiInfo").textContent = "Lỗi: " + err.message;
    liveAI = false;
    $("#liveAI").checked = false;
  } finally {
    aiBusy = false;
  }
}

$("#snap").onclick = () => runAI($("#preset").value);
$("#preset").onchange = (e) => ($("#steps").value = PRESETS[e.target.value].steps);
$("#liveAI").onchange = async (e) => {
  liveAI = e.target.checked;
  // Live AI: gửi khung mới ngay khi khung trước xong -> tốc độ = tốc độ GPU
  while (liveAI) {
    await runAI("live");
    await new Promise((r) => setTimeout(r, 0));
  }
};

async function pollServer() {
  try {
    const h = await (await fetch("/api/health")).json();
    if (h.ready) {
      setStatus("#srvStatus", `AI ✓ ${h.gpu || ""} · ${h.vram_free_gb}/${h.vram_total_gb} GB`, "ok");
      $("#maskMode").querySelector('[value="auto"]').disabled = !h.automasker;
      if (!h.automasker) $("#maskMode").value = "client";
      return;
    }
    setStatus("#srvStatus", h.error ? "AI lỗi: " + h.error : "AI đang tải model…", h.error ? "err" : "warn");
  } catch {
    setStatus("#srvStatus", "AI server chưa chạy (chỉ có AR)", "warn");
  }
  setTimeout(pollServer, 3000);
}

(async () => {
  try {
    setStatus("#trkStatus", "Đang tải tracker…", "warn");
    await Promise.all([startCamera(), tracker.init()]);
    setStatus("#trkStatus", "Tracker ✓", "ok");
    await loadCatalog();
    loop();
  } catch (e) {
    console.error(e);
    setStatus("#trkStatus", "Lỗi: " + e.message, "err");
  }
  pollServer();
})();
