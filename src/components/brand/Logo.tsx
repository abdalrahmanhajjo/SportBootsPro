/**
 * SportBootsPro brand mark: a forward-cut "S" (speed) with a signal-red dot (the ball).
 * Single source of truth for the symbol — also used by the favicon, app icon and OG image.
 */
export const MARK_PATH =
  "M62 24 L44 24 C30 24 26 36 34 43 C40 48 58 49 65 55 C75 64 69 80 52 80 L22 80";

export function LogoMark({
  className = "h-8 w-8",
  color = "currentColor",
  dot = "#E10600",
  title,
}: {
  className?: string;
  color?: string;
  dot?: string;
  title?: string;
}) {
  return (
    <svg viewBox="0 0 100 100" className={className} role={title ? "img" : undefined} aria-hidden={title ? undefined : true}>
      {title && <title>{title}</title>}
      <g transform="skewX(-14) translate(12 0)">
        <path d={MARK_PATH} fill="none" stroke={color} strokeWidth="16" strokeLinejoin="round" />
      </g>
      <circle cx="80" cy="24" r="8.5" fill={dot} />
    </svg>
  );
}

/** Rounded-square app icon (favicon, home screen, social avatar). */
export function AppIcon({ className = "h-10 w-10", variant = "ink" }: { className?: string; variant?: "ink" | "signal" }) {
  const bg = variant === "ink" ? "#0B0D10" : "#E10600";
  const fg = variant === "ink" ? "#F6F5F1" : "#FFFFFF";
  const dot = variant === "ink" ? "#E10600" : "#0B0D10";
  return (
    <svg viewBox="0 0 100 100" className={className} aria-hidden>
      <rect width="100" height="100" rx="24" fill={bg} />
      <g transform="translate(13 13) scale(0.74)">
        <g transform="skewX(-14) translate(12 0)">
          <path d={MARK_PATH} fill="none" stroke={fg} strokeWidth="16" strokeLinejoin="round" />
        </g>
        <circle cx="80" cy="24" r="8.5" fill={dot} />
      </g>
    </svg>
  );
}

/** Full horizontal lockup: symbol + SPORTBOOTSPRO wordmark. */
export function Logo({
  tone = "dark",
  className = "",
  size = "md",
}: {
  tone?: "dark" | "light";
  className?: string;
  size?: "sm" | "md" | "lg";
}) {
  const ink = tone === "dark" ? "#0B0D10" : "#F6F5F1";
  const s = {
    sm: { mark: "h-7 w-7", text: "text-xl" },
    md: { mark: "h-9 w-9", text: "text-[26px]" },
    lg: { mark: "h-16 w-16", text: "text-5xl" },
  }[size];
  return (
    <span className={`inline-flex items-center gap-1.5 ${className}`} aria-label="SportBootsPro">
      <LogoMark className={`${s.mark} shrink-0`} color={ink} />
      <span className={`font-display ${s.text} leading-none font-extrabold tracking-[-0.01em] uppercase italic`} style={{ color: ink }} aria-hidden>
        SportBoots<span className="text-brand-600">Pro</span>
      </span>
    </span>
  );
}
