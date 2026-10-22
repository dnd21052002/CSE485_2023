// Tracking người realtime trong trình duyệt (chạy trên GPU qua WebGL của MediaPipe).
// - PoseLandmarker: 33 điểm khớp
// - ImageSegmenter selfie_multiclass: 0 nền, 1 tóc, 2 da người, 3 da mặt, 4 quần áo, 5 phụ kiện
import {
  FilesetResolver,
  PoseLandmarker,
  ImageSegmenter,
} from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.18/vision_bundle.mjs";

const WASM = "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.18/wasm";
const POSE_MODEL =
  "https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_full/float16/latest/pose_landmarker_full.task";
const SEG_MODEL =
  "https://storage.googleapis.com/mediapipe-models/image_segmenter/selfie_multiclass_256x256/float32/latest/selfie_multiclass_256x256.tflite";

// One Euro Filter: giảm rung khi đứng yên, vẫn bám nhanh khi chuyển động.
class OneEuro {
  constructor(minCutoff = 1.2, beta = 0.02, dCutoff = 1.0) {
    Object.assign(this, { minCutoff, beta, dCutoff, x: null, dx: 0, t: 0 });
  }
  static alpha(cutoff, dt) {
    const tau = 1 / (2 * Math.PI * cutoff);
    return 1 / (1 + tau / dt);
  }
  filter(v, t) {
    if (this.x === null) {
      this.x = v;
      this.t = t;
      return v;
    }
    const dt = Math.max(1e-3, (t - this.t) / 1000);
    this.t = t;
    const dv = (v - this.x) / dt;
    this.dx += OneEuro.alpha(this.dCutoff, dt) * (dv - this.dx);
    const cutoff = this.minCutoff + this.beta * Math.abs(this.dx);
    this.x += OneEuro.alpha(cutoff, dt) * (v - this.x);
    return this.x;
  }
}

export class Tracker {
  async init() {
    const fileset = await FilesetResolver.forVisionTasks(WASM);
    this.pose = await PoseLandmarker.createFromOptions(fileset, {
      baseOptions: { modelAssetPath: POSE_MODEL, delegate: "GPU" },
      runningMode: "VIDEO",
      numPoses: 1,
      minPoseDetectionConfidence: 0.5,
      minTrackingConfidence: 0.5,
    });
    this.segmenter = await ImageSegmenter.createFromOptions(fileset, {
      baseOptions: { modelAssetPath: SEG_MODEL, delegate: "GPU" },
      runningMode: "VIDEO",
      outputCategoryMask: true,
      outputConfidenceMasks: false,
    });
    this.filters = Array.from({ length: 33 }, () => [new OneEuro(), new OneEuro()]);
    this.lastTs = -1;
    // categories: Uint8Array 256x256 với id lớp ở mỗi pixel
    this.categories = null;
    this.segSize = [256, 256];
  }

  // Trả về { landmarks: [{x,y,z,v}], categories } hoặc null nếu không thấy người
  detect(video, now) {
    if (now <= this.lastTs) now = this.lastTs + 1;
    this.lastTs = now;

    const res = this.pose.detectForVideo(video, now);
    this.segmenter.segmentForVideo(video, now, (seg) => {
      const m = seg.categoryMask;
      if (!m) return;
      this.segSize = [m.width, m.height];
      this.categories = m.getAsUint8Array().slice();
      m.close();
    });

    const lm = res.landmarks && res.landmarks[0];
    if (!lm) {
      this.filters.forEach((f) => (f[0].x = f[1].x = null));
      return { landmarks: null, categories: this.categories, segSize: this.segSize };
    }
    const smooth = lm.map((p, i) => ({
      x: this.filters[i][0].filter(p.x, now),
      y: this.filters[i][1].filter(p.y, now),
      z: p.z,
      v: p.visibility ?? 1,
    }));
    return { landmarks: smooth, categories: this.categories, segSize: this.segSize };
  }
}

export const LM = {
  NOSE: 0,
  L_SHOULDER: 11,
  R_SHOULDER: 12,
  L_ELBOW: 13,
  R_ELBOW: 14,
  L_WRIST: 15,
  R_WRIST: 16,
  L_HIP: 23,
  R_HIP: 24,
  L_KNEE: 25,
  R_KNEE: 26,
  L_ANKLE: 27,
  R_ANKLE: 28,
};
