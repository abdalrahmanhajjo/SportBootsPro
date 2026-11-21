import Link from "next/link";
import type { StoreSettings } from "@/lib/settings-defaults";
import { NewsletterForm } from "./NewsletterForm";

const groups = [
  {
    title: "Shop",
    links: [
      { href: "/shop", label: "All products" },
      { href: "/shop?category=football", label: "Football" },
      { href: "/shop?category=running", label: "Running" },
      { href: "/shop?category=basketball", label: "Basketball" },
    ],
  },
  {
    title: "Help",
    links: [
      { href: "/shipping", label: "Shipping" },
      { href: "/returns", label: "Returns & exchanges" },
      { href: "/size-guide", label: "Size guide" },
      { href: "/faq", label: "FAQ" },
    ],
  },
  {
    title: "Company",
    links: [
      { href: "/about", label: "About us" },
      { href: "/technology", label: "Technology" },
      { href: "/contact", label: "Contact" },
    ],
  },
];

export function Footer({ settings }: { settings: StoreSettings }) {
  return (
    <footer className="mt-24 bg-ink text-neutral-300">
      <div className="container-page grid gap-12 py-16 lg:grid-cols-[1.4fr_2fr]">
        <div>
          <p className="text-2xl font-black text-white">
            {settings.storeName}
          </p>
          <p className="mt-3 max-w-sm text-sm leading-relaxed text-neutral-400">{settings.tagline}</p>
          <div className="mt-6 max-w-sm">
            <p className="mb-2 text-sm font-semibold text-white">Get new drops & offers</p>
            <NewsletterForm />
          </div>
        </div>
        <div className="grid grid-cols-2 gap-8 sm:grid-cols-3">
          {groups.map((g) => (
            <div key={g.title}>
              <p className="text-sm font-semibold text-white">{g.title}</p>
              <ul className="mt-4 space-y-2.5 text-sm">
                {g.links.map((l) => (
                  <li key={l.href}>
                    <Link href={l.href} className="hover:text-white">
                      {l.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>
      <div className="border-t border-white/10">
        <div className="container-page flex flex-col gap-2 py-6 text-xs text-neutral-500 sm:flex-row sm:justify-between">
          <p>
            © {new Date().getFullYear()} {settings.storeName}. All rights reserved.
          </p>
          <p>
            {settings.address}
            {settings.phone && <> · {settings.phone}</>}
            {settings.email && <> · {settings.email}</>}
          </p>
        </div>
      </div>
    </footer>
  );
}
