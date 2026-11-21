import Link from "next/link";
import type { ProductCardData } from "@/lib/catalog";
import { formatMoney } from "@/lib/money";
import { ProductImage } from "./ProductImage";

export function ProductCard({ product, currency }: { product: ProductCardData; currency: string }) {
  const soldOut = product.stock <= 0;
  return (
    <Link
      href={`/products/${product.slug}`}
      className="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white transition hover:-translate-y-0.5 hover:shadow-xl"
    >
      <div className="relative aspect-square overflow-hidden">
        <ProductImage
          name={product.name}
          imageUrl={product.imageUrl}
          icon={product.icon}
          seed={product.id}
          size="md"
          className="transition duration-500 group-hover:scale-105"
        />
        {(product.badge || soldOut) && (
          <span
            className={`badge absolute top-3 left-3 ${soldOut ? "bg-neutral-900 text-white" : "bg-white text-brand-700"}`}
          >
            {soldOut ? "Sold out" : product.badge}
          </span>
        )}
      </div>
      <div className="flex flex-1 flex-col gap-1 p-4">
        {product.categoryName && (
          <span className="text-xs font-medium tracking-wide text-neutral-500 uppercase">{product.categoryName}</span>
        )}
        <h3 className="font-semibold text-neutral-900 group-hover:text-brand-700">{product.name}</h3>
        <div className="mt-auto flex items-baseline gap-2 pt-2">
          <span className="text-lg font-bold">{formatMoney(product.priceCents, currency)}</span>
          {product.compareAtCents && (
            <span className="text-sm text-neutral-400 line-through">{formatMoney(product.compareAtCents, currency)}</span>
          )}
        </div>
      </div>
    </Link>
  );
}
