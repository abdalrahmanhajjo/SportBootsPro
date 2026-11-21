import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Check, ChevronRight, RotateCcw, Truck, Wallet } from "lucide-react";
import { ProductCard } from "@/components/ProductCard";
import { ProductImage } from "@/components/ProductImage";
import { AddToCartForm } from "@/components/shop/AddToCartForm";
import { getProductBySlug, getRelatedProducts } from "@/lib/catalog";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

const DISPLAY_SIZES = ["38", "39", "40", "41", "42", "43", "44"];

export async function generateMetadata({ params }: PageProps<"/products/[slug]">): Promise<Metadata> {
  const { slug } = await params;
  const row = await getProductBySlug(slug);
  if (!row) return { title: "Product not found" };
  return {
    title: row.product.name,
    description: row.product.description.slice(0, 160),
    openGraph: { title: row.product.name, images: row.product.imageUrl ? [row.product.imageUrl] : undefined },
  };
}

export default async function ProductPage({ params }: PageProps<"/products/[slug]">) {
  const { slug } = await params;
  const row = await getProductBySlug(slug);
  if (!row) notFound();
  const { product, category } = row;
  const [settings, related] = await Promise.all([getSettings(), getRelatedProducts(product.id, product.categoryId)]);

  const allSizes = [...new Set([...DISPLAY_SIZES, ...product.sizes])].sort((a, b) => Number(a) - Number(b));
  const features = product.features.split(",").map((f) => f.trim()).filter(Boolean);
  const lowStock = product.stock > 0 && product.stock <= settings.lowStockThreshold;
  const saving = product.compareAtCents ? product.compareAtCents - product.priceCents : 0;

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "Product",
    name: product.name,
    description: product.description,
    image: product.imageUrl ?? undefined,
    category: category?.name,
    offers: {
      "@type": "Offer",
      priceCurrency: settings.currency,
      price: (product.priceCents / 100).toFixed(2),
      availability: product.stock > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
    },
  };

  return (
    <div className="container-page py-10">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd).replace(/</g, "\\u003c") }} />
      <nav aria-label="Breadcrumb" className="mb-8 flex items-center gap-1 text-sm text-neutral-500">
        <Link href="/" className="hover:text-neutral-900">Home</Link>
        <ChevronRight className="h-4 w-4" />
        <Link href="/shop" className="hover:text-neutral-900">Shop</Link>
        {category && (
          <>
            <ChevronRight className="h-4 w-4" />
            <Link href={`/shop?category=${category.slug}`} className="hover:text-neutral-900">{category.name}</Link>
          </>
        )}
        <ChevronRight className="h-4 w-4" />
        <span className="truncate text-neutral-900">{product.name}</span>
      </nav>

      <div className="grid gap-10 lg:grid-cols-2 lg:gap-16">
        <div className="relative aspect-square overflow-hidden rounded-3xl">
          <ProductImage name={product.name} imageUrl={product.imageUrl} icon={product.icon} seed={product.id} size="lg" />
          {product.badge && <span className="badge absolute top-5 left-5 bg-white px-3 py-1 text-brand-700">{product.badge}</span>}
        </div>

        <div>
          {category && <p className="eyebrow">{category.name}</p>}
          <h1 className="mt-2 text-4xl font-black tracking-tight">{product.name}</h1>
          <div className="mt-4 flex flex-wrap items-baseline gap-3">
            <span className="text-3xl font-bold">{formatMoney(product.priceCents, settings.currency)}</span>
            {product.compareAtCents && (
              <>
                <span className="text-lg text-neutral-400 line-through">{formatMoney(product.compareAtCents, settings.currency)}</span>
                <span className="badge bg-brand-50 text-brand-700">Save {formatMoney(saving, settings.currency)}</span>
              </>
            )}
          </div>
          <p className={`mt-3 text-sm font-medium ${product.stock <= 0 ? "text-red-600" : lowStock ? "text-amber-600" : "text-emerald-600"}`}>
            {product.stock <= 0 ? "Out of stock" : lowStock ? `Hurry — only ${product.stock} left` : "In stock, ready to ship"}
          </p>
          <p className="mt-6 leading-relaxed text-neutral-600">{product.description}</p>

          <div className="mt-8">
            <AddToCartForm productId={product.id} allSizes={allSizes} availableSizes={product.sizes} stock={product.stock} />
          </div>

          {features.length > 0 && (
            <div className="mt-10 border-t border-neutral-200 pt-8">
              <h2 className="font-semibold">Key features</h2>
              <ul className="mt-4 grid gap-2 sm:grid-cols-2">
                {features.map((f) => (
                  <li key={f} className="flex items-center gap-2 text-sm text-neutral-700">
                    <Check className="h-4 w-4 shrink-0 text-brand-600" /> {f}
                  </li>
                ))}
              </ul>
            </div>
          )}

          <ul className="mt-8 space-y-3 rounded-2xl bg-neutral-50 p-5 text-sm">
            <li className="flex gap-3">
              <Truck className="h-5 w-5 shrink-0 text-neutral-500" />
              Delivery across Lebanon in 1–3 days · free over {formatMoney(settings.freeShippingThresholdCents, settings.currency)}
            </li>
            <li className="flex gap-3">
              <Wallet className="h-5 w-5 shrink-0 text-neutral-500" /> Pay cash on delivery
            </li>
            <li className="flex gap-3">
              <RotateCcw className="h-5 w-5 shrink-0 text-neutral-500" /> Free size exchange within 14 days
            </li>
          </ul>
        </div>
      </div>

      {related.length > 0 && (
        <section className="mt-24">
          <h2 className="text-2xl font-black tracking-tight">You may also like</h2>
          <div className="mt-8 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
            {related.map((p) => (
              <ProductCard key={p.id} product={p} currency={settings.currency} />
            ))}
          </div>
        </section>
      )}
    </div>
  );
}
