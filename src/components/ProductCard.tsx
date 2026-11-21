import Link from "next/link";
import { ArrowUpRight } from "lucide-react";
import type { ProductCardData } from "@/lib/catalog";
import { formatMoney } from "@/lib/money";
import { ProductImage } from "./ProductImage";

export function ProductCard({ product, currency, priority = false }: { product: ProductCardData; currency: string; priority?: boolean }) {
  const soldOut = product.stock <= 0;
  const off = product.compareAtCents ? Math.round((1 - product.priceCents / product.compareAtCents) * 100) : 0;
  return (
    <Link href={`/products/${product.slug}`} className="group flex flex-col">
      <div className="relative aspect-[4/5] overflow-hidden rounded-2xl bg-chalk-2">
        <ProductImage
          name={product.name}
          imageUrl={product.imageUrl}
          size="md"
          priority={priority}
          className="transition-transform duration-700 ease-out-expo group-hover:scale-[1.04]"
        />
        <div className="absolute top-3 left-3 flex gap-1.5">
          {soldOut ? (
            <span className="badge bg-ink text-white">Sold out</span>
          ) : (
            <>
              {product.badge && (
                <span className={`badge ${product.badge.toLowerCase() === "sale" ? "bg-brand-600 text-white" : "bg-white text-ink"}`}>{product.badge}</span>
              )}
              {off > 0 && <span className="badge bg-white text-brand-700">−{off}%</span>}
            </>
          )}
        </div>
        <span className="absolute right-3 bottom-3 flex h-10 w-10 translate-y-2 items-center justify-center rounded-full bg-white text-ink opacity-0 shadow-lg transition duration-300 group-hover:translate-y-0 group-hover:opacity-100" aria-hidden>
          <ArrowUpRight className="h-5 w-5" strokeWidth={1.75} />
        </span>
      </div>
      <div className="flex flex-1 flex-col px-0.5 pt-3.5">
        {product.categoryName && <span className="text-xs font-medium tracking-wide text-steel uppercase">{product.categoryName}</span>}
        <h3 className="mt-0.5 font-semibold text-ink decoration-2 underline-offset-4 group-hover:underline">{product.name}</h3>
        <div className="mt-1.5 flex items-baseline gap-2">
          <span className={`font-bold ${product.compareAtCents ? "text-brand-700" : ""}`}>{formatMoney(product.priceCents, currency)}</span>
          {product.compareAtCents && <span className="text-sm text-steel line-through">{formatMoney(product.compareAtCents, currency)}</span>}
        </div>
      </div>
    </Link>
  );
}
