import Link from "next/link";
import { Mail, MapPin, MessageCircle, Phone } from "lucide-react";
import { Logo } from "@/components/brand/Logo";
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
      { href: "/shop?category=training", label: "Training" },
    ],
  },
  {
    title: "Help",
    links: [
      { href: "/shipping", label: "Delivery" },
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
    <footer className="mt-28 bg-ink text-neutral-400">
      <div className="container-page grid gap-14 py-16 lg:grid-cols-[1.3fr_2fr]">
        <div>
          <Logo tone="light" />
          <p className="mt-4 max-w-sm text-sm leading-relaxed">{settings.tagline}</p>
          <p className="mt-1 text-left font-arabic text-sm text-neutral-500" lang="ar" dir="rtl">
            جهّز حالك. العب أقوى.
          </p>
          <div className="mt-8 max-w-sm">
            <p className="mb-2.5 text-sm font-semibold text-white">New drops & restocks, first.</p>
            <NewsletterForm />
          </div>
        </div>
        <div className="grid grid-cols-2 gap-10 sm:grid-cols-4">
          {groups.map((g) => (
            <div key={g.title}>
              <p className="font-display text-sm font-semibold tracking-[0.18em] text-white uppercase">{g.title}</p>
              <ul className="mt-4 space-y-2.5 text-sm">
                {g.links.map((l) => (
                  <li key={l.href}>
                    <Link href={l.href} className="transition-colors hover:text-white">
                      {l.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
          <div className="col-span-2 sm:col-span-1">
            <p className="font-display text-sm font-semibold tracking-[0.18em] text-white uppercase">Contact</p>
            <ul className="mt-4 space-y-2.5 text-sm">
              {settings.whatsapp && (
                <li>
                  <a href={`https://wa.me/${settings.whatsapp}`} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2 hover:text-white">
                    <MessageCircle className="h-4 w-4" strokeWidth={1.75} /> WhatsApp
                  </a>
                </li>
              )}
              {settings.phone && (
                <li className="flex items-center gap-2">
                  <Phone className="h-4 w-4" strokeWidth={1.75} /> {settings.phone}
                </li>
              )}
              {settings.email && (
                <li>
                  <a href={`mailto:${settings.email}`} className="flex items-center gap-2 hover:text-white">
                    <Mail className="h-4 w-4" strokeWidth={1.75} /> Email us
                  </a>
                </li>
              )}
              {settings.address && (
                <li className="flex items-center gap-2">
                  <MapPin className="h-4 w-4" strokeWidth={1.75} /> {settings.address}
                </li>
              )}
            </ul>
          </div>
        </div>
      </div>
      <div className="border-t border-white/10">
        <div className="container-page flex flex-col gap-2 py-6 text-xs text-neutral-500 sm:flex-row sm:items-center sm:justify-between">
          <p>
            © {new Date().getFullYear()} {settings.storeName}. All rights reserved.
          </p>
          <p className="flex items-center gap-2">
            <span className="rounded-md border border-white/15 px-2 py-0.5 font-semibold text-neutral-300">Cash on delivery</span>
            <span>Prices in {settings.currency}</span>
          </p>
        </div>
      </div>
    </footer>
  );
}
