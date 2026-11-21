import type { MetadataRoute } from "next";

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "SportBootsPro",
    short_name: "SportBootsPro",
    description: "Performance footwear delivered across Lebanon. Cash on delivery.",
    start_url: "/",
    display: "standalone",
    background_color: "#F6F5F1",
    theme_color: "#0B0D10",
    icons: [
      { src: "/icon.svg", sizes: "any", type: "image/svg+xml" },
      { src: "/apple-icon", sizes: "180x180", type: "image/png" },
    ],
  };
}
