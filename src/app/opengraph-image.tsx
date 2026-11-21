import { readFile } from "node:fs/promises";
import { join } from "node:path";
import { ImageResponse } from "next/og";
import { MARK_PATH } from "@/components/brand/Logo";

export const alt = "SportBootsPro — Gear up. Play harder.";
export const size = { width: 1200, height: 630 };
export const contentType = "image/png";

const fontDir = join(process.cwd(), "src/assets/fonts");

export default async function OpengraphImage() {
  const [display, label] = await Promise.all([
    readFile(join(fontDir, "barlow-condensed-latin-800-italic.woff")),
    readFile(join(fontDir, "barlow-condensed-latin-600-normal.woff")),
  ]);

  return new ImageResponse(
    (
      <div style={{ width: "100%", height: "100%", display: "flex", flexDirection: "column", justifyContent: "space-between", background: "#0B0D10", padding: 72, color: "#F6F5F1" }}>
        <div style={{ display: "flex", alignItems: "center", gap: 14 }}>
          <svg width="84" height="84" viewBox="0 0 100 100">
            <g transform="skewX(-14) translate(12 0)">
              <path d={MARK_PATH} fill="none" stroke="#F6F5F1" strokeWidth="16" strokeLinejoin="round" />
            </g>
            <circle cx="80" cy="24" r="8.5" fill="#E10600" />
          </svg>
          <div style={{ display: "flex", fontFamily: "Display", fontSize: 56, fontStyle: "italic", textTransform: "uppercase" }}>
            SportBoots<span style={{ color: "#E10600" }}>Pro</span>
          </div>
        </div>
        <div style={{ display: "flex", flexDirection: "column", fontFamily: "Display", fontStyle: "italic", fontSize: 150, lineHeight: 0.9, textTransform: "uppercase", letterSpacing: -2 }}>
          <span>Gear up.</span>
          <span style={{ color: "#E10600" }}>Play harder.</span>
        </div>
        <div style={{ display: "flex", justifyContent: "space-between", fontFamily: "Label", fontSize: 28, letterSpacing: 4, textTransform: "uppercase", color: "#9CA0A8" }}>
          <span>Football · Running · Basketball · Training</span>
          <span>Delivered across Lebanon</span>
        </div>
      </div>
    ),
    {
      ...size,
      fonts: [
        { name: "Display", data: display, style: "italic", weight: 800 },
        { name: "Label", data: label, style: "normal", weight: 600 },
      ],
    },
  );
}
