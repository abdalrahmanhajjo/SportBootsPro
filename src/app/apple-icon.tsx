import { ImageResponse } from "next/og";
import { MARK_PATH } from "@/components/brand/Logo";

export const size = { width: 180, height: 180 };
export const contentType = "image/png";

// iOS adds its own rounded corners, so the icon is a full square.
export default function AppleIcon() {
  return new ImageResponse(
    (
      <div style={{ width: "100%", height: "100%", display: "flex", alignItems: "center", justifyContent: "center", background: "#0B0D10" }}>
        <svg width="132" height="132" viewBox="0 0 100 100">
          <g transform="skewX(-14) translate(12 0)">
            <path d={MARK_PATH} fill="none" stroke="#F6F5F1" strokeWidth="16" strokeLinejoin="round" />
          </g>
          <circle cx="80" cy="24" r="8.5" fill="#E10600" />
        </svg>
      </div>
    ),
    size,
  );
}
