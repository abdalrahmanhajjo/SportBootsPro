import { MessageCircle } from "lucide-react";
import { Footer } from "@/components/shop/Footer";
import { Header } from "@/components/shop/Header";
import { getSettings } from "@/lib/settings";

// Every storefront page reads live stock/prices and the cart cookie.
export const dynamic = "force-dynamic";

export default async function ShopLayout({ children }: LayoutProps<"/">) {
  const settings = await getSettings();
  return (
    <>
      <a href="#main" className="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">
        Skip to content
      </a>
      <Header storeName={settings.storeName} />
      <main id="main" className="flex-1">
        {children}
      </main>
      <Footer settings={settings} />
      {settings.whatsapp && (
        <a
          href={`https://wa.me/${settings.whatsapp}`}
          target="_blank"
          rel="noopener noreferrer"
          aria-label="Chat with us on WhatsApp"
          className="fixed right-5 bottom-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-xl transition hover:scale-105"
        >
          <MessageCircle className="h-7 w-7" />
        </a>
      )}
    </>
  );
}
