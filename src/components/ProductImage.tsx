const GRADIENTS = [
  "from-neutral-900 via-neutral-800 to-brand-700",
  "from-brand-600 via-brand-700 to-neutral-900",
  "from-neutral-800 via-neutral-700 to-neutral-900",
  "from-neutral-950 via-brand-700 to-brand-500",
];

/** Product photo when an image URL is set; otherwise a branded gradient tile with the category icon. */
export function ProductImage({
  name,
  imageUrl,
  icon,
  seed = 0,
  className = "",
  size = "sm",
}: {
  name: string;
  imageUrl: string | null;
  icon: string;
  seed?: number;
  className?: string;
  /** sm = thumbnails, md = product cards, lg = product page hero */
  size?: "sm" | "md" | "lg";
}) {
  if (imageUrl) {
    return (
      // eslint-disable-next-line @next/next/no-img-element -- admins may paste images from any host
      <img
        src={imageUrl}
        alt={name}
        loading="lazy"
        referrerPolicy="no-referrer"
        className={`h-full w-full object-cover ${className}`}
      />
    );
  }
  return (
    <div
      role="img"
      aria-label={name}
      className={`relative flex h-full w-full items-center justify-center overflow-hidden bg-gradient-to-br ${GRADIENTS[seed % GRADIENTS.length]} ${className}`}
    >
      <div className="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-white/10 blur-2xl" />
      <span className={{ sm: "text-2xl", md: "text-6xl drop-shadow-lg", lg: "text-8xl drop-shadow-xl" }[size]} aria-hidden>
        {icon}
      </span>
      {size !== "sm" && (
        <span className="absolute bottom-3 left-4 text-[10px] font-black tracking-[0.3em] text-white/50 uppercase">
          SportBootsPro
        </span>
      )}
    </div>
  );
}
