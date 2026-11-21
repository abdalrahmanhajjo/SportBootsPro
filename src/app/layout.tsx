import type { Metadata, Viewport } from "next";
import "@fontsource-variable/inter";
import "./globals.css";

const siteUrl = process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

export const metadata: Metadata = {
  metadataBase: new URL(siteUrl),
  title: { default: "SportBootsPro — Performance sportswear in Lebanon", template: "%s | SportBootsPro" },
  description:
    "Football boots, running shoes, basketball and training footwear with fast delivery across Lebanon and cash on delivery.",
  openGraph: { type: "website", siteName: "SportBootsPro" },
};

export const viewport: Viewport = { themeColor: "#e00000" };

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="en">
      <body className="flex min-h-screen flex-col">{children}</body>
    </html>
  );
}
