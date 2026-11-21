import Link from "next/link";
import { ArrowRight, ArrowUpRight, BadgeCheck, Feather, Footprints, RotateCcw, Truck, Wallet, Wind, Zap } from "lucide-react";
import { LogoMark } from "@/components/brand/Logo";
import { ProductCard } from "@/components/ProductCard";
import { NewsletterForm } from "@/components/shop/NewsletterForm";
import { getCategories, getFeaturedProducts } from "@/lib/catalog";
import { formatMoney } from "@/lib/money";
import { imageSrcSet, SITE_PHOTOS, sizedImage } from "@/lib/photos";
import { getSettings } from "@/lib/settings";

export default async function HomePage() {
  const [settings, categories, featured] = await Promise.all([getSettings(), getCategories(), getFeaturedProducts(8)]);

  const promises = [
    { icon: Truck, title: "1–3 day delivery", text: `Anywhere in Lebanon · free over ${formatMoney(settings.freeShippingThresholdCents, settings.currency)}` },
    { icon: Wallet, title: "Cash on delivery", text: "Pay the courier when your order arrives" },
    { icon: RotateCcw, title: "Free size exchange", text: "Wrong fit? Swap it within 14 days" },
    { icon: BadgeCheck, title: "Checked before it ships", text: "Every pair is inspected by our team" },
  ];

  const tech = [
    { icon: Feather, title: "Lightweight", text: "Knit uppers and foam midsoles shave grams for faster feet." },
    { icon: Footprints, title: "Traction", text: "Studs and outsoles tuned for grass, turf, courts and trails." },
    { icon: Zap, title: "Energy return", text: "Responsive foams give back energy on every stride." },
    { icon: Wind, title: "Breathable", text: "Engineered mesh keeps feet cool through Lebanese summers." },
  ];

  return (
    <>
      {/* ── Hero ── */}
      <section className="relative isolate overflow-hidden bg-ink text-white">
        {/* eslint-disable-next-line @next/next/no-img-element -- Unsplash CDN handles sizing */}
        <img
          src={sizedImage(SITE_PHOTOS.hero, 1920, { quality: 70 })}
          srcSet={imageSrcSet(SITE_PHOTOS.hero, [768, 1280, 1920, 2560])}
          sizes="100vw"
          alt=""
          fetchPriority="high"
          referrerPolicy="no-referrer"
          className="absolute inset-0 -z-10 h-full w-full object-cover object-[70%_center] opacity-90"
        />
        <div className="absolute inset-0 -z-10 bg-gradient-to-r from-ink via-ink/80 to-ink/10" />
        <div className="absolute inset-x-0 bottom-0 -z-10 h-40 bg-gradient-to-t from-ink to-transparent" />
        <div className="container-page flex min-h-[78vh] flex-col justify-center py-24 lg:min-h-[86vh]">
          <p className="eyebrow text-brand-500">New season · {new Date().getFullYear()}</p>
          <h1 className="display mt-4 text-[clamp(4rem,11vw,9.5rem)]">
            Gear up.
            <br />
            <span className="text-brand-600">Play harder.</span>
          </h1>
          <p className="mt-4 text-left font-arabic text-2xl font-bold text-white/85" lang="ar" dir="rtl">
            جهّز حالك. العب أقوى.
          </p>
          <p className="mt-6 max-w-lg text-lg leading-relaxed text-neutral-300">
            Football boots, running shoes and court footwear chosen for performance — delivered to your door anywhere in Lebanon.
          </p>
          <div className="mt-10 flex flex-wrap gap-3">
            <Link href="/shop" className="btn-primary px-7 py-3.5 text-base">
              Shop the collection <ArrowRight className="h-4 w-4" strokeWidth={2} />
            </Link>
            <Link href="/shop?category=football" className="btn border border-white/30 px-7 py-3.5 text-base text-white backdrop-blur-sm hover:bg-white/10">
              Football boots
            </Link>
          </div>
        </div>
      </section>

      {/* ── Promises ── */}
      <section aria-label="Why shop with us" className="border-b border-black/[0.06] bg-white">
        <div className="container-page grid gap-6 py-8 sm:grid-cols-2 lg:grid-cols-4">
          {promises.map((p) => (
            <div key={p.title} className="flex items-center gap-4">
              <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                <p.icon className="h-5 w-5" strokeWidth={1.75} aria-hidden />
              </span>
              <div>
                <p className="font-semibold">{p.title}</p>
                <p className="text-sm text-steel">{p.text}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* ── Shop by sport ── */}
      <section className="container-page py-24">
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className="eyebrow">Shop by sport</p>
            <h2 className="display mt-2 text-5xl sm:text-6xl">Find your game</h2>
          </div>
          <Link href="/shop" className="hidden items-center gap-1.5 text-sm font-semibold hover:text-brand-700 sm:flex">
            Shop all <ArrowRight className="h-4 w-4" />
          </Link>
        </div>
        <div className="mt-10 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
          {categories.map((c) => (
            <Link key={c.id} href={`/shop?category=${c.slug}`} className="group relative isolate flex aspect-[4/5] overflow-hidden rounded-2xl bg-ink lg:aspect-[4/3]">
              {c.imageUrl && (
                // eslint-disable-next-line @next/next/no-img-element -- Unsplash CDN handles sizing
                <img
                  src={sizedImage(c.imageUrl, 800, { height: 640 })}
                  srcSet={imageSrcSet(c.imageUrl, [400, 600, 800, 1000], 1.25)}
                  sizes="(min-width: 1024px) 33vw, 50vw"
                  alt=""
                  loading="lazy"
                  referrerPolicy="no-referrer"
                  className="absolute inset-0 -z-10 h-full w-full object-cover transition-transform duration-700 ease-out-expo group-hover:scale-105"
                />
              )}
              <div className="absolute inset-0 -z-10 bg-gradient-to-t from-ink/85 via-ink/20 to-transparent" />
              <div className="mt-auto flex w-full items-end justify-between gap-3 p-5 text-white sm:p-6">
                <div>
                  <h3 className="display text-3xl sm:text-4xl">{c.name}</h3>
                  <p className="mt-1 text-sm text-white/75">{c.productCount} styles</p>
                </div>
                <span className="hidden h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-ink transition-transform group-hover:-rotate-45 sm:flex" aria-hidden>
                  <ArrowRight className="h-5 w-5" strokeWidth={1.75} />
                </span>
              </div>
            </Link>
          ))}
        </div>
      </section>

      {/* ── Best sellers ── */}
      <section className="bg-white py-24">
        <div className="container-page">
          <div className="flex items-end justify-between gap-4">
            <div>
              <p className="eyebrow">Best sellers</p>
              <h2 className="display mt-2 text-5xl sm:text-6xl">Most wanted right now</h2>
            </div>
            <Link href="/shop" className="hidden items-center gap-1.5 text-sm font-semibold hover:text-brand-700 sm:flex">
              View all <ArrowRight className="h-4 w-4" />
            </Link>
          </div>
          {featured.length === 0 ? (
            <p className="mt-8 text-steel">New products are coming soon.</p>
          ) : (
            <div className="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
              {featured.map((p) => (
                <ProductCard key={p.id} product={p} currency={settings.currency} />
              ))}
            </div>
          )}
        </div>
      </section>

      {/* ── Editorial ── */}
      <section className="container-page py-24">
        <div className="grid items-center overflow-hidden rounded-3xl bg-ink text-white lg:grid-cols-2">
          <div className="relative aspect-[4/3] lg:aspect-auto lg:h-full lg:min-h-[560px]">
            {/* eslint-disable-next-line @next/next/no-img-element -- Unsplash CDN handles sizing */}
            <img
              src={sizedImage(SITE_PHOTOS.editorial, 1100)}
              srcSet={imageSrcSet(SITE_PHOTOS.editorial, [600, 900, 1200])}
              sizes="(min-width: 1024px) 50vw, 100vw"
              alt="Performance shoes hanging by their laces"
              loading="lazy"
              referrerPolicy="no-referrer"
              className="absolute inset-0 h-full w-full object-cover"
            />
          </div>
          <div className="p-8 sm:p-12 lg:p-16">
            <p className="eyebrow text-brand-500">Built for performance</p>
            <h2 className="display mt-3 text-5xl sm:text-6xl">
              Engineered for
              <br />
              every surface.
            </h2>
            <dl className="mt-10 grid gap-8 sm:grid-cols-2">
              {tech.map((t) => (
                <div key={t.title}>
                  <dt className="flex items-center gap-2.5 font-semibold">
                    <t.icon className="h-5 w-5 text-brand-500" strokeWidth={1.75} aria-hidden /> {t.title}
                  </dt>
                  <dd className="mt-1.5 text-sm leading-relaxed text-neutral-400">{t.text}</dd>
                </div>
              ))}
            </dl>
            <Link href="/technology" className="btn-light mt-10 px-6 py-3">
              Explore the technology <ArrowUpRight className="h-4 w-4" />
            </Link>
          </div>
        </div>
      </section>

      {/* ── Newsletter ── */}
      <section className="container-page">
        <div className="relative isolate overflow-hidden rounded-3xl bg-brand-600 p-8 text-white sm:p-14">
          <LogoMark className="absolute -top-10 -right-10 -z-10 h-80 w-80 opacity-15" color="#FFFFFF" dot="#0B0D10" />
          <div className="flex flex-col items-start justify-between gap-8 lg:flex-row lg:items-center">
            <div>
              <h2 className="display text-5xl sm:text-6xl">Never miss a drop.</h2>
              <p className="mt-3 max-w-md text-brand-100">Restocks, limited editions and subscriber-only offers. No spam — unsubscribe any time.</p>
            </div>
            <div className="w-full max-w-md">
              <NewsletterForm buttonClassName="btn-dark" />
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
