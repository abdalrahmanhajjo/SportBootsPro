import Link from "next/link";
import { Search, ShoppingBag, Truck } from "lucide-react";
import { Logo } from "@/components/brand/Logo";
import { cartCount } from "@/lib/cart";
import { readCartItems } from "@/lib/catalog";
import { formatMoney } from "@/lib/money";
import type { StoreSettings } from "@/lib/settings-defaults";
import { MobileNav } from "./MobileNav";

export const NAV_LINKS = [
  { href: "/shop", label: "Shop all" },
  { href: "/shop?category=football", label: "Football" },
  { href: "/shop?category=running", label: "Running" },
  { href: "/shop?category=basketball", label: "Basketball" },
  { href: "/technology", label: "Technology" },
  { href: "/contact", label: "Contact" },
];

export async function Header({ settings }: { settings: StoreSettings }) {
  const count = cartCount(await readCartItems());
  return (
    <>
      <div className="bg-ink text-chalk">
        <p className="container-page flex h-9 items-center justify-center gap-2 text-center text-xs font-medium tracking-wide">
          <Truck className="h-3.5 w-3.5 text-brand-500" strokeWidth={2} aria-hidden />
          Free delivery across Lebanon over {formatMoney(settings.freeShippingThresholdCents, settings.currency)}
          <span className="hidden text-neutral-500 sm:inline">·</span>
          <span className="hidden sm:inline">Cash on delivery</span>
        </p>
      </div>
      <header className="sticky top-0 z-40 border-b border-black/[0.06] bg-chalk/90 backdrop-blur-md">
        <div className="container-page flex h-16 items-center justify-between gap-6">
          <Link href="/" className="shrink-0" aria-label={`${settings.storeName} home`}>
            <Logo size="sm" />
          </Link>
          <nav aria-label="Main" className="hidden lg:block">
            <ul className="flex items-center gap-1">
              {NAV_LINKS.map((l) => (
                <li key={l.href}>
                  <Link href={l.href} className="rounded-full px-3.5 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-black/[0.05] hover:text-ink">
                    {l.label}
                  </Link>
                </li>
              ))}
            </ul>
          </nav>
          <div className="flex items-center gap-1.5">
            <form action="/shop" role="search" className="relative hidden md:block">
              <label htmlFor="header-search" className="sr-only">
                Search products
              </label>
              <Search className="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-steel" strokeWidth={1.75} aria-hidden />
              <input
                id="header-search"
                name="q"
                type="search"
                placeholder="Search boots, shoes…"
                className="h-10 w-52 rounded-full border border-black/10 bg-white/70 pr-4 pl-10 text-sm placeholder:text-steel focus:w-64 focus:border-ink focus:bg-white focus:outline-none xl:w-60"
              />
            </form>
            <Link
              href="/cart"
              className="relative flex h-10 w-10 items-center justify-center rounded-full transition-colors hover:bg-black/[0.05]"
              aria-label={`Cart, ${count} item${count === 1 ? "" : "s"}`}
            >
              <ShoppingBag className="h-5 w-5" strokeWidth={1.75} />
              {count > 0 && (
                <span className="absolute -top-0.5 -right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1 text-[11px] font-bold text-white ring-2 ring-chalk">
                  {count}
                </span>
              )}
            </Link>
            <MobileNav links={[{ href: "/", label: "Home" }, ...NAV_LINKS, { href: "/about", label: "About" }]} />
          </div>
        </div>
      </header>
    </>
  );
}
