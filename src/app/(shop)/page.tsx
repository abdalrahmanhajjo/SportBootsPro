import Link from "next/link";
import { ArrowRight, BadgeCheck, RotateCcw, Truck, Wallet } from "lucide-react";
import { ProductCard } from "@/components/ProductCard";
import { NewsletterForm } from "@/components/shop/NewsletterForm";
import { getCategories, getFeaturedProducts } from "@/lib/catalog";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export default async function HomePage() {
  const [settings, categories, featured] = await Promise.all([getSettings(), getCategories(), getFeaturedProducts(8)]);

  const perks = [
    { icon: Truck, title: "Fast delivery", text: `Across Lebanon · free over ${formatMoney(settings.freeShippingThresholdCents, settings.currency)}` },
    { icon: Wallet, title: "Cash on delivery", text: "Pay when your order arrives" },
    { icon: RotateCcw, title: "Easy exchanges", text: "Wrong size? Swap it within 14 days" },
    { icon: BadgeCheck, title: "100% authentic", text: "Sourced from trusted suppliers" },
  ];

  return (
    <>
      {/* Hero */}
      <section className="relative overflow-hidden bg-ink text-white">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(224,0,0,0.55),transparent_45%)]" />
        <div className="absolute -right-24 -bottom-24 h-96 w-96 rounded-full bg-brand-600/30 blur-3xl" />
        <div className="container-page relative grid items-center gap-12 py-20 lg:grid-cols-2 lg:py-28">
          <div>
            <p className="eyebrow text-brand-500">New season · {new Date().getFullYear()}</p>
            <h1 className="mt-4 text-5xl leading-[1.05] font-black tracking-tight sm:text-6xl lg:text-7xl">
              Gear up.
              <br />
              <span className="text-brand-500">Play harder.</span>
            </h1>
            <p className="mt-6 max-w-lg text-lg text-neutral-300">
              Football boots, running shoes and court footwear built for performance — delivered to your door anywhere in Lebanon.
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Link href="/shop" className="btn-primary px-7 py-3.5 text-base">
                Shop the collection <ArrowRight className="h-4 w-4" />
              </Link>
              <Link href="/technology" className="btn border border-white/25 px-7 py-3.5 text-base text-white hover:bg-white/10">
                Our technology
              </Link>
            </div>
          </div>
          <div className="relative mx-auto hidden aspect-square w-full max-w-md lg:block">
            <div className="absolute inset-6 rotate-6 rounded-[2.5rem] bg-gradient-to-br from-brand-500 to-brand-700" />
            <div className="absolute inset-6 -rotate-3 rounded-[2.5rem] border border-white/15 bg-neutral-900/80 backdrop-blur" />
            <div className="absolute inset-0 flex items-center justify-center text-[10rem] drop-shadow-2xl" aria-hidden>
              👟
            </div>
          </div>
        </div>
      </section>

      {/* Perks */}
      <section className="border-b border-neutral-200 bg-neutral-50">
        <div className="container-page grid gap-6 py-8 sm:grid-cols-2 lg:grid-cols-4">
          {perks.map((p) => (
            <div key={p.title} className="flex items-start gap-3">
              <p.icon className="mt-0.5 h-6 w-6 shrink-0 text-brand-600" aria-hidden />
              <div>
                <p className="font-semibold">{p.title}</p>
                <p className="text-sm text-neutral-500">{p.text}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* Categories */}
      <section className="container-page py-20">
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className="eyebrow">Shop by sport</p>
            <h2 className="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Find your game</h2>
          </div>
          <Link href="/shop" className="hidden items-center gap-1 text-sm font-semibold text-brand-700 hover:underline sm:flex">
            View all <ArrowRight className="h-4 w-4" />
          </Link>
        </div>
        <div className="mt-10 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
          {categories.map((c) => (
            <Link
              key={c.id}
              href={`/shop?category=${c.slug}`}
              className="group flex flex-col items-center rounded-2xl border border-neutral-200 p-6 text-center transition hover:border-brand-600 hover:bg-brand-50"
            >
              <span className="text-4xl transition group-hover:scale-110" aria-hidden>
                {c.icon}
              </span>
              <span className="mt-3 font-semibold">{c.name}</span>
              <span className="text-xs text-neutral-500">{c.productCount} products</span>
            </Link>
          ))}
        </div>
      </section>

      {/* Featured */}
      <section className="bg-neutral-50 py-20">
        <div className="container-page">
          <p className="eyebrow">Featured</p>
          <h2 className="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Best sellers right now</h2>
          {featured.length === 0 ? (
            <p className="mt-8 text-neutral-500">New products are coming soon.</p>
          ) : (
            <div className="mt-10 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
              {featured.map((p) => (
                <ProductCard key={p.id} product={p} currency={settings.currency} />
              ))}
            </div>
          )}
          <div className="mt-12 text-center">
            <Link href="/shop" className="btn-dark px-7 py-3">
              Browse all products <ArrowRight className="h-4 w-4" />
            </Link>
          </div>
        </div>
      </section>

      {/* Performance strip */}
      <section className="container-page py-20">
        <div className="grid gap-10 rounded-3xl bg-ink p-8 text-white sm:p-12 lg:grid-cols-3">
          <div className="lg:col-span-1">
            <p className="eyebrow text-brand-500">Built for performance</p>
            <h2 className="mt-2 text-3xl font-black">Every detail, engineered.</h2>
            <Link href="/technology" className="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-brand-500 hover:underline">
              Explore the tech <ArrowRight className="h-4 w-4" />
            </Link>
          </div>
          <dl className="grid grid-cols-2 gap-8 lg:col-span-2">
            {[
              ["Lightweight", "Knit uppers and foam midsoles shave grams for faster feet."],
              ["Traction", "Stud and outsole patterns tuned for grass, turf and courts."],
              ["Cushioning", "Energy-return foams protect your joints over long sessions."],
              ["Fit", "Sizes 38–44 with an exchange if the fit isn't right."],
            ].map(([t, d]) => (
              <div key={t}>
                <dt className="font-bold">{t}</dt>
                <dd className="mt-1 text-sm text-neutral-400">{d}</dd>
              </div>
            ))}
          </dl>
        </div>
      </section>

      {/* Newsletter */}
      <section className="container-page">
        <div className="flex flex-col items-start justify-between gap-6 rounded-3xl bg-brand-600 p-8 text-white sm:p-12 lg:flex-row lg:items-center">
          <div>
            <h2 className="text-2xl font-black sm:text-3xl">Be first to know about new drops</h2>
            <p className="mt-2 text-brand-100">Restocks, limited editions and member-only offers.</p>
          </div>
          <div className="w-full max-w-md">
            <NewsletterForm buttonClassName="btn-dark" />
          </div>
        </div>
      </section>
    </>
  );
}
