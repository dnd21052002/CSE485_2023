// Renderer AR realtime bằng WebGL2.
// Pass 1: vẽ khung hình camera.
// Pass 2: vẽ lưới tam giác của áo đã được warp theo khung xương, kèm:
//   - occlusion: tóc / mặt / cẳng tay + bàn tay đè lên áo
//   - shading: lấy độ sáng (đã làm mờ) của camera nhân lên áo để áo "ăn" ánh sáng thật
import { LM } from "./tracker.js";

const GRID = 24; // độ mịn lưới warp

const VS_QUAD = `#version 300 es
in vec2 aPos; out vec2 vUV;
void main(){ vUV = vec2(aPos.x*0.5+0.5, 0.5-aPos.y*0.5); gl_Position = vec4(aPos,0.,1.); }`;
const FS_QUAD = `#version 300 es
precision mediump float; in vec2 vUV; uniform sampler2D uTex; out vec4 o;
void main(){ o = texture(uTex, vUV); }`;

const VS_MESH = `#version 300 es
in vec2 aPos; in vec2 aUV; uniform vec2 uRes; out vec2 vUV; out vec2 vScreen;
void main(){
  vUV = aUV; vScreen = aPos / uRes;
  vec2 c = vScreen*2.0-1.0; gl_Position = vec4(c.x, -c.y, 0., 1.);
}`;
const FS_MESH = `#version 300 es
precision mediump float;
in vec2 vUV; in vec2 vScreen;
uniform sampler2D uGarment, uOcc, uShadeTex;
uniform float uShade, uOpacity, uFlip;
out vec4 o;
void main(){
  vec4 g = texture(uGarment, uFlip > 0.5 ? vec2(1.0-vUV.x, vUV.y) : vUV);
  float occ = texture(uOcc, vScreen).r;
  float lum = dot(texture(uShadeTex, vScreen).rgb, vec3(0.299,0.587,0.114));
  float s = mix(1.0, clamp(lum/0.45, 0.55, 1.35), uShade);
  float a = g.a * (1.0-occ) * uOpacity;
  o = vec4(g.rgb*s*a, a); // premultiplied
}`;

function compile(gl, vs, fs) {
  const p = gl.createProgram();
  for (const [type, src] of [[gl.VERTEX_SHADER, vs], [gl.FRAGMENT_SHADER, fs]]) {
    const s = gl.createShader(type);
    gl.shaderSource(s, src);
    gl.compileShader(s);
    if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(s));
    gl.attachShader(p, s);
  }
  gl.linkProgram(p);
  if (!gl.getProgramParameter(p, gl.LINK_STATUS)) throw new Error(gl.getProgramInfoLog(p));
  return p;
}

function makeTex(gl, filter = gl.LINEAR) {
  const t = gl.createTexture();
  gl.bindTexture(gl.TEXTURE_2D, t);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, filter);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, filter);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
  return t;
}

const lerp = (a, b, t) => a + (b - a) * t;
const lerp2 = (p, q, t) => [lerp(p[0], q[0], t), lerp(p[1], q[1], t)];
const smooth = (e0, e1, x) => {
  const t = Math.min(1, Math.max(0, (x - e0) / (e1 - e0)));
  return t * t * (3 - 2 * t);
};
const rot = (p, c, a) => {
  const s = Math.sin(a), k = Math.cos(a), dx = p[0] - c[0], dy = p[1] - c[1];
  return [c[0] + dx * k - dy * s, c[1] + dx * s + dy * k];
};
const ang = (v) => Math.atan2(v[1], v[0]);
const wrapPi = (a) => Math.atan2(Math.sin(a), Math.cos(a));
const norm = (v) => {
  const l = Math.hypot(v[0], v[1]) || 1;
  return [v[0] / l, v[1] / l];
};

// Landmark nào ứng với góc nào của ảnh áo (ảnh chụp chính diện: mép trái ảnh = bên phải người mặc)
const BODY_MAP = {
  upper: { top: [LM.R_SHOULDER, LM.L_SHOULDER], bottom: [LM.R_HIP, LM.L_HIP] },
  overall: { top: [LM.R_SHOULDER, LM.L_SHOULDER], bottom: [LM.R_KNEE, LM.L_KNEE] },
  lower: { top: [LM.R_HIP, LM.L_HIP], bottom: [LM.R_ANKLE, LM.L_ANKLE] },
};

export class Renderer {
  constructor(canvas) {
    this.canvas = canvas;
    const gl = (this.gl = canvas.getContext("webgl2", { premultipliedAlpha: true, preserveDrawingBuffer: true }));
    if (!gl) throw new Error("Trình duyệt không hỗ trợ WebGL2");
    this.quadProg = compile(gl, VS_QUAD, FS_QUAD);
    this.meshProg = compile(gl, VS_MESH, FS_MESH);

    this.quadBuf = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, this.quadBuf);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);

    this.posBuf = gl.createBuffer();
    this.uvBuf = gl.createBuffer();
    this.idxBuf = gl.createBuffer();
    const idx = [];
    for (let j = 0; j < GRID; j++)
      for (let i = 0; i < GRID; i++) {
        const a = j * (GRID + 1) + i, b = a + 1, c = a + GRID + 1, d = c + 1;
        idx.push(a, b, c, b, d, c);
      }
    gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, this.idxBuf);
    gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, new Uint16Array(idx), gl.STATIC_DRAW);
    this.idxCount = idx.length;

    const uv = [];
    for (let j = 0; j <= GRID; j++) for (let i = 0; i <= GRID; i++) uv.push(i / GRID, j / GRID);
    this.uv = uv;
    gl.bindBuffer(gl.ARRAY_BUFFER, this.uvBuf);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(uv), gl.STATIC_DRAW);
    this.positions = new Float32Array(uv.length);

    this.videoTex = makeTex(gl);
    this.garmentTex = makeTex(gl);
    this.occTex = makeTex(gl);
    this.shadeTex = makeTex(gl);
    this.shadeCanvas = Object.assign(document.createElement("canvas"), { width: 96, height: 72 });
    this.shadeCtx = this.shadeCanvas.getContext("2d", { willReadFrequently: false });
    this.occData = null;
    this.garment = null;
    this.params = { flip: true, width: 1.15, length: 1.0, offsetY: 0.0, shade: 0.6, opacity: 1.0, sleeves: true };
  }

  setGarment(g) {
    // g: { canvas (đã crop theo alpha), type, anchors, sleeveAngle }
    this.garment = g;
    const gl = this.gl;
    gl.bindTexture(gl.TEXTURE_2D, this.garmentTex);
    gl.pixelStorei(gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, false);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, g.canvas);
  }

  resize(w, h) {
    if (this.canvas.width !== w || this.canvas.height !== h) {
      this.canvas.width = w;
      this.canvas.height = h;
    }
  }

  // Tính vị trí pixel đích cho từng đỉnh lưới áo
  warp(lm, W, H) {
    const g = this.garment, P = this.params, map = BODY_MAP[g.type];
    const px = (i) => [lm[i].x * W, lm[i].y * H];
    let TL = px(map.top[0]), TR = px(map.top[1]), BL = px(map.bottom[0]), BR = px(map.bottom[1]);

    const mt = lerp2(TL, TR, 0.5), mb = lerp2(BL, BR, 0.5);
    TL = lerp2(mt, TL, P.width); TR = lerp2(mt, TR, P.width);
    BL = lerp2(mb, BL, P.width); BR = lerp2(mb, BR, P.width);
    BL = lerp2(TL, BL, P.length); BR = lerp2(TR, BR, P.length);
    const sw = Math.hypot(TR[0] - TL[0], TR[1] - TL[1]);
    const oy = P.offsetY * sw;
    for (const p of [TL, TR, BL, BR]) p[1] += oy;

    // {top:[xl,xr,y], bottom:[xl,xr,y]} chuẩn hoá trong ảnh áo. Khi lật áo (để chữ/logo
    // đọc đúng chiều trong chế độ gương) thì neo cũng lật theo.
    let a = g.anchors;
    if (P.flip) a = { top: [1 - a.top[1], 1 - a.top[0], a.top[2]], bottom: [1 - a.bottom[1], 1 - a.bottom[0], a.bottom[2]] };
    const down = norm([mb[0] - mt[0], mb[1] - mt[1]]);
    const armOK = (i) => lm[i].v > 0.5;

    // Chuẩn bị dữ liệu tay áo (chỉ cho áo trên)
    const arms = [];
    if (g.type !== "lower" && P.sleeves) {
      const sides = [
        { s: LM.R_SHOULDER, e: LM.R_ELBOW, w: LM.R_WRIST, out: -1, anchor: TL },
        { s: LM.L_SHOULDER, e: LM.L_ELBOW, w: LM.L_WRIST, out: 1, anchor: TR },
      ];
      for (const sd of sides) {
        if (!armOK(sd.e)) { arms.push(null); continue; }
        const S = sd.anchor, E = px(sd.e), Wr = px(sd.w);
        // hướng tay áo ở tư thế "trải phẳng" của ảnh sản phẩm (xoay trục thân ra phía ngoài)
        const d0 = rot(down, [0, 0], (-sd.out * g.sleeveAngle * Math.PI) / 180);
        const d1 = norm([E[0] - S[0], E[1] - S[1]]);
        const upperLen = Math.hypot(E[0] - S[0], E[1] - S[1]);
        const fore = armOK(sd.w) ? wrapPi(ang([Wr[0] - E[0], Wr[1] - E[1]]) - ang(d1)) : 0;
        arms.push({ S, E, d1, upperLen, dA: wrapPi(ang(d1) - ang(d0)), fore });
      }
    }

    const pos = this.positions, uv = this.uv;
    for (let k = 0; k < uv.length; k += 2) {
      const gx = uv[k], gy = uv[k + 1];
      const v = (gy - a.top[2]) / (a.bottom[2] - a.top[2]);
      const xl = lerp(a.top[0], a.bottom[0], v), xr = lerp(a.top[1], a.bottom[1], v);
      const u = (gx - xl) / (xr - xl);
      let p = lerp2(lerp2(TL, TR, u), lerp2(BL, BR, u), v);

      if (arms.length) {
        const side = u < 0 ? 0 : u > 1 ? 1 : -1;
        const arm = side >= 0 ? arms[side] : null;
        if (arm) {
          const out = side === 0 ? -u : u - 1;
          const w = smooth(0.0, 0.3, out);
          p = rot(p, arm.S, arm.dA * w);
          // phần cẳng tay (áo dài tay) gập theo khuỷu
          const t = (p[0] - arm.S[0]) * arm.d1[0] + (p[1] - arm.S[1]) * arm.d1[1];
          const wf = smooth(arm.upperLen * 0.85, arm.upperLen * 1.15, t);
          if (wf > 0 && arm.fore) p = rot(p, arm.E, arm.fore * wf);
        }
      }
      pos[k] = p[0];
      pos[k + 1] = p[1];
    }
  }

  // Mask che khuất (hạ xuống ≤320px cho nhẹ CPU): tóc, mặt luôn ở trên;
  // da tay chỉ đè lên áo khi nằm trong vùng cẳng tay/bàn tay.
  updateOcclusion(lm, categories, segSize) {
    const gl = this.gl;
    const [fw, fh] = segSize;
    const step = Math.max(1, Math.ceil(fw / 320));
    const sw = Math.floor(fw / step), sh = Math.floor(fh / step);
    if (!this.occData || this.occData.length !== sw * sh) this.occData = new Uint8Array(sw * sh);
    const occ = this.occData;
    if (!categories) {
      occ.fill(0);
    } else {
      const caps = [];
      if (lm) {
        for (const [e, w] of [[LM.L_ELBOW, LM.L_WRIST], [LM.R_ELBOW, LM.R_WRIST]]) {
          if (lm[w].v < 0.4) continue;
          const E = [lm[e].x * sw, lm[e].y * sh], Wr = [lm[w].x * sw, lm[w].y * sh];
          // kéo dài qua cổ tay để phủ bàn tay
          const H = [Wr[0] + (Wr[0] - E[0]) * 0.45, Wr[1] + (Wr[1] - E[1]) * 0.45];
          const r = Math.hypot(Wr[0] - E[0], Wr[1] - E[1]) * 0.2 + 1;
          caps.push([E, H, r * r]);
        }
      }
      for (let y = 0; y < sh; y++)
        for (let x = 0; x < sw; x++) {
          const c = categories[y * step * fw + x * step];
          let o = c === 1 || c === 3 ? 255 : 0;
          if (c === 2 && caps.length) {
            for (const [A, B, r2] of caps) {
              const bx = B[0] - A[0], by = B[1] - A[1];
              const t = Math.max(0, Math.min(1, ((x - A[0]) * bx + (y - A[1]) * by) / (bx * bx + by * by)));
              const dx = x - A[0] - bx * t, dy = y - A[1] - by * t;
              if (dx * dx + dy * dy < r2) { o = 255; break; }
            }
          }
          occ[y * sw + x] = o;
        }
    }
    gl.bindTexture(gl.TEXTURE_2D, this.occTex);
    gl.pixelStorei(gl.UNPACK_ALIGNMENT, 1);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.R8, sw, sh, 0, gl.RED, gl.UNSIGNED_BYTE, occ);
  }

  render(video, track) {
    const gl = this.gl, W = video.videoWidth, H = video.videoHeight;
    if (!W) return;
    this.resize(W, H);
    gl.viewport(0, 0, W, H);
    gl.clearColor(0, 0, 0, 1);
    gl.clear(gl.COLOR_BUFFER_BIT);

    gl.bindTexture(gl.TEXTURE_2D, this.videoTex);
    gl.pixelStorei(gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, false);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, video);

    // pass 1: camera
    gl.useProgram(this.quadProg);
    gl.disable(gl.BLEND);
    this.bindAttr(this.quadProg, "aPos", this.quadBuf, 2);
    gl.activeTexture(gl.TEXTURE0);
    gl.bindTexture(gl.TEXTURE_2D, this.videoTex);
    gl.uniform1i(gl.getUniformLocation(this.quadProg, "uTex"), 0);
    gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);

    const lm = track && track.landmarks;
    if (!this.garment || !lm) return;
    const map = BODY_MAP[this.garment.type];
    if ([...map.top, ...map.bottom].some((i) => lm[i].v < 0.3)) return;

    this.warp(lm, W, H);
    this.updateOcclusion(lm, track.categories, track.segSize);
    this.shadeCtx.filter = "blur(3px)";
    this.shadeCtx.drawImage(video, 0, 0, 96, 72);
    gl.bindTexture(gl.TEXTURE_2D, this.shadeTex);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, this.shadeCanvas);

    // pass 2: áo
    const p = this.meshProg;
    gl.useProgram(p);
    gl.enable(gl.BLEND);
    gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA);
    gl.bindBuffer(gl.ARRAY_BUFFER, this.posBuf);
    gl.bufferData(gl.ARRAY_BUFFER, this.positions, gl.DYNAMIC_DRAW);
    this.bindAttr(p, "aPos", this.posBuf, 2);
    this.bindAttr(p, "aUV", this.uvBuf, 2);
    gl.uniform2f(gl.getUniformLocation(p, "uRes"), W, H);
    gl.uniform1f(gl.getUniformLocation(p, "uShade"), this.params.shade);
    gl.uniform1f(gl.getUniformLocation(p, "uOpacity"), this.params.opacity);
    gl.uniform1f(gl.getUniformLocation(p, "uFlip"), this.params.flip ? 1 : 0);
    [["uGarment", this.garmentTex], ["uOcc", this.occTex], ["uShadeTex", this.shadeTex]].forEach(([n, t], i) => {
      gl.activeTexture(gl.TEXTURE0 + i);
      gl.bindTexture(gl.TEXTURE_2D, t);
      gl.uniform1i(gl.getUniformLocation(p, n), i);
    });
    gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, this.idxBuf);
    gl.drawElements(gl.TRIANGLES, this.idxCount, gl.UNSIGNED_SHORT, 0);
  }

  bindAttr(prog, name, buf, size) {
    const gl = this.gl, loc = gl.getAttribLocation(prog, name);
    if (loc < 0) return;
    gl.bindBuffer(gl.ARRAY_BUFFER, buf);
    gl.enableVertexAttribArray(loc);
    gl.vertexAttribPointer(loc, size, gl.FLOAT, false, 0, 0);
  }
}
