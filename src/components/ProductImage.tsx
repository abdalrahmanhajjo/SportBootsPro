import { LogoMark } from "@/components/brand/Logo";
import { imageSrcSet, sizedImage } from "@/lib/photos";

const SIZES = {
  sm: { w: 160, widths: [96, 160, 240], sizes: "120px" },
  md: { w: 600, widths: [320, 480, 640, 800], sizes: "(min-width: 1024px) 25vw, (min-width: 640px) 33vw, 50vw" },
  lg: { w: 1200, widths: [640, 900, 1200, 1600], sizes: "(min-width: 1024px) 50vw, 100vw" },
};

/** Product photo (resized by the image CDN), or a branded tile when no photo is set. */
export function ProductImage({
  name,
  imageUrl,
  className = "",
  size = "sm",
  priority = false,
}: {
  name: string;
  imageUrl: string | null;
  icon?: string;
  seed?: number;
  className?: string;
  /** sm = thumbnails, md = product cards, lg = product page */
  size?: "sm" | "md" | "lg";
  priority?: boolean;
}) {
  const s = SIZES[size];
  if (imageUrl) {
    return (
      // eslint-disable-next-line @next/next/no-img-element -- admins may paste images from any host; Unsplash's CDN resizes for us
      <img
        src={sizedImage(imageUrl, s.w, { height: s.w })}
        srcSet={imageSrcSet(imageUrl, s.widths, 1)}
        sizes={s.sizes}
        alt={name}
        loading={priority ? "eager" : "lazy"}
        fetchPriority={priority ? "high" : undefined}
        decoding="async"
        referrerPolicy="no-referrer"
        className={`h-full w-full bg-chalk-2 object-cover ${className}`}
      />
    );
  }
  return (
    <div role="img" aria-label={name} className={`flex h-full w-full items-center justify-center bg-chalk-2 ${className}`}>
      <LogoMark className={size === "sm" ? "h-1/2 w-1/2 opacity-25" : "h-1/3 w-1/3 opacity-20"} color="#0B0D10" dot="#E10600" />
    </div>
  );
}
