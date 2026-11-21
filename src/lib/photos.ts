// Demo photography from Unsplash (free under the Unsplash License: https://unsplash.com/license).
// Images are served from Unsplash's CDN, which resizes them on the fly. Replace any product
// photo from the admin panel by pasting your own image URL.

const UNSPLASH = "https://images.unsplash.com/photo-";

export const unsplash = (id: string) => `${UNSPLASH}${id}`;

export const SITE_PHOTOS = {
  hero: unsplash("1620650663852-9ff13eb84d8f"), // boot on a match ball, on grass
  editorial: unsplash("1786514781319-a04b1ec172b8"), // boots hanging by their laces
  about: unsplash("1758506971683-c080e6f16ce9"), // runner with long shadow
};

/** Build a sized, optimised image URL. Unsplash URLs are resized by Unsplash's CDN; others are returned as-is. */
export function sizedImage(url: string, width: number, opts: { height?: number; quality?: number } = {}) {
  try {
    const u = new URL(url);
    if (u.hostname !== "images.unsplash.com") return url;
    u.search = "";
    u.searchParams.set("auto", "format");
    u.searchParams.set("fit", "crop");
    u.searchParams.set("w", String(width));
    if (opts.height) u.searchParams.set("h", String(opts.height));
    u.searchParams.set("q", String(opts.quality ?? 72));
    return u.toString();
  } catch {
    return url;
  }
}

/** srcset for responsive images (only for Unsplash URLs). */
export function imageSrcSet(url: string, widths: number[], aspect?: number) {
  if (!url.startsWith(UNSPLASH)) return undefined;
  return widths.map((w) => `${sizedImage(url, w, { height: aspect ? Math.round(w / aspect) : undefined })} ${w}w`).join(", ");
}
