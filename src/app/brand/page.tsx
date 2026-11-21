import type { Metadata } from "next";
import { ArrowRight, Check, Footprints, MapPin, MessageCircle, Ruler, ShoppingBag, Timer, Trophy, Truck, Zap } from "lucide-react";
import { AppIcon, LogoMark } from "@/components/brand/Logo";

export const metadata: Metadata = { title: "Brand foundation", robots: { index: false, follow: false } };

const palette = [
  { name: "Ink", hex: "#0B0D10", text: "text-white" },
  { name: "Signal Red", hex: "#E10600", text: "text-white" },
  { name: "Chalk", hex: "#F6F5F1", text: "text-ink", border: true },
  { name: "Steel", hex: "#6B7079", text: "text-white" },
];

const icons = [
  { icon: Footprints, label: "Footwear" },
  { icon: Truck, label: "Delivery" },
  { icon: Ruler, label: "Size" },
  { icon: Timer, label: "Speed" },
  { icon: Trophy, label: "Win" },
  { icon: MapPin, label: "Lebanon" },
];

function Label({ n, children }: { n: string; children: string }) {
  return (
    <p className="text-[13px] font-medium tracking-[0.25em] text-steel uppercase">
      {n} / {children}
    </p>
  );
}

export default function BrandPage() {
  return (
    <main className="min-h-screen bg-chalk px-6 py-8 text-ink sm:px-10">
      <div className="mx-auto max-w-[1480px]">
        <header className="flex items-center justify-between text-lg">
          <p>
            <span className="font-semibold">sportbootspro</span> <span className="text-steel">/ brand foundation</span>
          </p>
          <p className="text-steel">01</p>
        </header>

        {/* Lockup */}
        <section className="flex flex-col items-center justify-center gap-8 py-16 sm:flex-row sm:gap-12">
          <LogoMark className="h-40 w-40 sm:h-52 sm:w-52" color="#0B0D10" />
          <div>
            <p className="display text-[clamp(4rem,9vw,8.5rem)] leading-[0.85]">
              SportBoots<span className="text-brand-600">Pro</span>
            </p>
            <p className="mt-3 text-left font-arabic text-5xl font-extrabold sm:text-6xl" lang="ar" dir="rtl">
              سبورت بوتس برو
            </p>
          </div>
        </section>

        <div className="grid border-t border-black/15 lg:grid-cols-[1fr_1fr_1.6fr]">
          <section className="border-b border-black/15 py-8 lg:border-r lg:pr-8">
            <Label n="01">Symbol</Label>
            <div className="flex h-56 items-center justify-center">
              <LogoMark className="h-40 w-40" color="#0B0D10" />
            </div>
            <p className="text-sm text-steel">A forward-cut “S” for speed, with a signal-red dot for the ball.</p>
          </section>
          <section className="border-b border-black/15 py-8 lg:border-r lg:px-8">
            <Label n="02">App icon</Label>
            <div className="flex h-56 items-center justify-center gap-6">
              <AppIcon className="h-40 w-40 drop-shadow-xl" />
              <div className="flex flex-col gap-4">
                <AppIcon className="h-16 w-16" variant="signal" />
                <AppIcon className="h-8 w-8" />
                <AppIcon className="h-4 w-4" />
              </div>
            </div>
            <p className="text-sm text-steel">Works from home screen size down to a 16px browser tab.</p>
          </section>
          <section className="border-b border-black/15 py-8 lg:pl-8">
            <Label n="03">Palette</Label>
            <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
              {palette.map((c) => (
                <div key={c.name}>
                  <div className={`aspect-[4/5] rounded-xl ${c.border ? "border border-black/15" : ""}`} style={{ background: c.hex }} />
                  <p className="mt-3 text-lg">{c.name}</p>
                  <p className="font-mono text-sm text-steel">{c.hex}</p>
                </div>
              ))}
            </div>
          </section>

          <section className="border-b border-black/15 py-8 lg:border-r lg:pr-8">
            <Label n="04">Typography</Label>
            <p className="display mt-6 text-7xl">
              Gear up.
              <br />
              Play harder.
            </p>
            <p className="mt-3 text-left font-arabic text-3xl font-bold" lang="ar" dir="rtl">
              جهّز حالك. العب أقوى.
            </p>
            <p className="mt-6 text-sm text-steel">
              <span className="font-semibold text-ink">Barlow Condensed</span> ExtraBold Italic — headlines ·{" "}
              <span className="font-semibold text-ink">Inter</span> — text · <span className="font-semibold text-ink">Cairo</span> — Arabic
            </p>
          </section>
          <section className="border-b border-black/15 py-8 lg:border-r lg:px-8">
            <Label n="05">Icon language</Label>
            <div className="mt-10 grid grid-cols-3 gap-y-10">
              {icons.map((i) => (
                <div key={i.label} className="flex flex-col items-center gap-3">
                  <i.icon className="h-11 w-11" strokeWidth={1.5} />
                  <span className="text-sm text-steel">{i.label}</span>
                </div>
              ))}
            </div>
            <p className="mt-10 text-sm text-steel">Lucide outline icons, 1.5–1.75 stroke, rounded joins.</p>
          </section>
          <section className="border-b border-black/15 py-8 lg:pl-8">
            <Label n="06">UI details</Label>
            <div className="mt-8 flex max-w-md flex-col gap-4">
              <span className="flex items-center justify-between rounded-full bg-ink px-7 py-4 text-xl font-semibold text-white">
                Shop the collection <ArrowRight className="h-6 w-6" />
              </span>
              <span className="flex items-center justify-between rounded-full bg-brand-600 px-7 py-4 text-xl font-semibold text-white">
                Add to cart <ShoppingBag className="h-6 w-6" strokeWidth={1.75} />
              </span>
              <span className="flex items-center justify-between rounded-full border-2 border-ink px-7 py-4 text-xl font-semibold">
                Size guide <ArrowRight className="h-6 w-6" />
              </span>
              <div className="flex flex-wrap gap-3">
                <span className="flex items-center gap-3 rounded-full bg-chalk-2 py-2.5 pr-3 pl-5 text-base">
                  <Zap className="h-5 w-5" strokeWidth={1.75} /> Running
                  <span className="flex h-7 w-7 items-center justify-center rounded-full bg-ink text-white">
                    <Check className="h-4 w-4" />
                  </span>
                </span>
                <span className="flex items-center gap-2 rounded-full bg-[#25D366] px-5 py-2.5 text-base font-semibold text-white">
                  <MessageCircle className="h-5 w-5" strokeWidth={1.75} /> WhatsApp us
                </span>
              </div>
            </div>
          </section>
        </div>

        <footer className="py-8 text-center text-lg tracking-[0.2em] text-steel">Lebanon&apos;s home for performance footwear.</footer>
      </div>
    </main>
  );
}
