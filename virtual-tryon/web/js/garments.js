// Chuẩn bị ảnh áo: tách nền (ảnh sản phẩm nền trắng/đơn sắc), crop sát, tự dò điểm neo.

export async function loadImage(src) {
  const img = new Image();
  img.crossOrigin = "anonymous";
  img.src = typeof src === "string" ? src : URL.createObjectURL(src);
  await img.decode();
  return img;
}

// Flood-fill từ viền ảnh: pixel gần màu nền và liên thông với viền -> trong suốt.
function removeBackground(ctx, w, h, tol = 38) {
  const id = ctx.getImageData(0, 0, w, h), d = id.data;
  let hasAlpha = false;
  for (let i = 3; i < d.length; i += 4 * 97) if (d[i] < 250) { hasAlpha = true; break; }
  if (hasAlpha) return id; // PNG đã trong suốt sẵn

  const corners = [0, w - 1, (h - 1) * w, h * w - 1];
  const bg = [0, 1, 2].map((c) => corners.reduce((s, p) => s + d[p * 4 + c], 0) / 4);
  const seen = new Uint8Array(w * h), stack = [];
  for (let x = 0; x < w; x++) stack.push(x, (h - 1) * w + x);
  for (let y = 0; y < h; y++) stack.push(y * w, y * w + w - 1);
  const near = (p) =>
    Math.abs(d[p * 4] - bg[0]) + Math.abs(d[p * 4 + 1] - bg[1]) + Math.abs(d[p * 4 + 2] - bg[2]) < tol * 3;
  while (stack.length) {
    const p = stack.pop();
    if (seen[p] || !near(p)) continue;
    seen[p] = 1;
    d[p * 4 + 3] = 0;
    const x = p % w;
    if (x > 0) stack.push(p - 1);
    if (x < w - 1) stack.push(p + 1);
    if (p >= w) stack.push(p - w);
    if (p < w * (h - 1)) stack.push(p + w);
  }
  // làm mềm viền 1px
  for (let p = 0; p < w * h; p++) {
    if (seen[p]) continue;
    const x = p % w;
    if ((x > 0 && seen[p - 1]) || (x < w - 1 && seen[p + 1]) || (p >= w && seen[p - w]) || (p < w * (h - 1) && seen[p + w]))
      d[p * 4 + 3] = 140;
  }
  return id;
}

// Mép trái/phải của đoạn liền chứa tâm ảnh (bỏ qua tay áo dài tách rời thân)
function rowEdges(d, w, y) {
  const on = (x) => d[(y * w + x) * 4 + 3] > 100;
  let c = Math.round(w / 2);
  if (!on(c)) {
    // tâm trống (vd: giữa hai ống quần) -> lấy mép ngoài cùng
    let l = -1, r = -1;
    for (let x = 0; x < w; x++) if (on(x)) { if (l < 0) l = x; r = x; }
    return l < 0 ? null : [l / w, r / w];
  }
  let l = c, r = c;
  while (l > 0 && on(l - 1)) l--;
  while (r < w - 1 && on(r + 1)) r++;
  return [l / w, r / w];
}

// Ước lượng vị trí khớp vai/hông/cổ chân trên ảnh áo đã crop (toạ độ 0..1)
function guessAnchors(d, w, h, type) {
  const edgesAt = (fy, fallback) => rowEdges(d, w, Math.min(h - 1, Math.round(fy * h))) || fallback;
  if (type === "lower") {
    const [l, r] = edgesAt(0.08, [0.1, 0.9]);
    const inset = (r - l) * 0.2;
    return { top: [l + inset, r - inset, 0.06], bottom: [0.27, 0.73, 0.97] };
  }
  if (type === "overall") {
    const [l, r] = edgesAt(0.3, [0.25, 0.75]);
    const inset = (r - l) * 0.08;
    return { top: [l + inset, r - inset, 0.09], bottom: [l + inset, r - inset, 0.78] };
  }
  // áo trên: lấy bề rộng thân ở 75% chiều cao (dưới tay áo)
  const [l, r] = edgesAt(0.75, [0.22, 0.78]);
  const inset = (r - l) * 0.1;
  return { top: [l + inset, r - inset, 0.1], bottom: [l + inset, r - inset, 0.93] };
}

export async function prepareGarment(src, meta = {}) {
  const img = await loadImage(src);
  const max = 1024, s = Math.min(1, max / Math.max(img.width, img.height));
  const w = Math.round(img.width * s), h = Math.round(img.height * s);
  const c = Object.assign(document.createElement("canvas"), { width: w, height: h });
  const ctx = c.getContext("2d", { willReadFrequently: true });
  ctx.drawImage(img, 0, 0, w, h);
  const id = removeBackground(ctx, w, h);
  ctx.putImageData(id, 0, 0);

  // crop theo alpha
  const d = id.data;
  let x0 = w, y0 = h, x1 = 0, y1 = 0;
  for (let y = 0; y < h; y++)
    for (let x = 0; x < w; x++)
      if (d[(y * w + x) * 4 + 3] > 20) {
        if (x < x0) x0 = x;
        if (x > x1) x1 = x;
        if (y < y0) y0 = y;
        if (y > y1) y1 = y;
      }
  if (x1 <= x0 || y1 <= y0) { x0 = 0; y0 = 0; x1 = w - 1; y1 = h - 1; }
  const cw = x1 - x0 + 1, ch = y1 - y0 + 1;
  const crop = Object.assign(document.createElement("canvas"), { width: cw, height: ch });
  const cctx = crop.getContext("2d", { willReadFrequently: true });
  cctx.drawImage(c, x0, y0, cw, ch, 0, 0, cw, ch);

  const type = meta.type || "upper";
  const anchors = meta.anchors || guessAnchors(cctx.getImageData(0, 0, cw, ch).data, cw, ch, type);
  const blob = await new Promise((r) => crop.toBlob(r, "image/png"));
  return {
    name: meta.name || "Áo của bạn",
    type,
    anchors,
    sleeveAngle: meta.sleeveAngle ?? (type === "upper" ? 50 : 20),
    canvas: crop,
    blob, // gửi lên server CatVTON
    thumb: crop.toDataURL("image/png"),
  };
}
