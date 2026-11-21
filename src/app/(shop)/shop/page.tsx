import type { Metadata } from "next";
import Link from "next/link";
import { SlidersHorizontal } from "lucide-react";
import { ProductCard } from "@/components/ProductCard";
import { getCategories, searchProducts, SORTS, type ShopFilters } from "@/lib/catalog";
import { getSettings } from "@/lib/settings";

export const metadata: Metadata = {
  title: "Shop",
  description: "Browse football boots, running shoes, basketball, tennis, training and trail footwear.",
};

const one = (v: string | string[] | undefined) => (Array.isArray(v) ? v[0] : v);

export default async function ShopPage({ searchParams }: PageProps<"/shop">) {
  const sp = await searchParams;
  const filters: ShopFilters = {
    q: one(sp.q),
    category: one(sp.category),
    sort: one(sp.sort),
    min: one(sp.min),
    max: one(sp.max),
    page: one(sp.page),
  };
  const [settings, categories, result] = await Promise.all([getSettings(), getCategories(), searchProducts(filters)]);
  const activeCategory = categories.find((c) => c.slug === filters.category);

  const pageHref = (page: number) => {
    const params = new URLSearchParams();
    for (const [k, v] of Object.entries(filters)) if (v && k !== "page") params.set(k, v);
    if (page > 1) params.set("page", String(page));
    const qs = params.toString();
    return qs ? `/shop?${qs}` : "/shop";
  };

  return (
    <div className="container-page py-12">
      <header className="mb-10">
        <p className="eyebrow">Shop</p>
        <h1 className="display mt-2 text-6xl sm:text-7xl">{activeCategory ? activeCategory.name : "All products"}</h1>
        <p className="mt-2 text-neutral-500">
          {activeCategory?.description || "Performance footwear for every sport."} · {result.total} product
          {result.total === 1 ? "" : "s"}
        </p>
      </header>

      <div className="grid gap-10 lg:grid-cols-[260px_1fr]">
        {/* Filters (plain GET form → works without JavaScript and keeps URLs shareable) */}
        <aside>
          <form action="/shop" className="card space-y-6 p-5 lg:sticky lg:top-24">
            <p className="flex items-center gap-2 font-semibold">
              <SlidersHorizontal className="h-4 w-4" strokeWidth={1.75} /> Filters
            </p>
            <div>
              <label htmlFor="q" className="label">
                Search
              </label>
              <input id="q" name="q" type="search" defaultValue={filters.q} placeholder="e.g. running" className="input" />
            </div>
            <div>
              <label htmlFor="category" className="label">
                Sport
              </label>
              <select id="category" name="category" defaultValue={filters.category ?? ""} className="input">
                <option value="">All sports</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.slug}>
                    {c.name} ({c.productCount})
                  </option>
                ))}
              </select>
            </div>
            <fieldset>
              <legend className="label">Price ($)</legend>
              <div className="flex items-center gap-2">
                <input name="min" type="number" min={0} step="1" defaultValue={filters.min} placeholder="Min" aria-label="Minimum price" className="input" />
                <span className="text-neutral-400">–</span>
                <input name="max" type="number" min={0} step="1" defaultValue={filters.max} placeholder="Max" aria-label="Maximum price" className="input" />
              </div>
            </fieldset>
            <div>
              <label htmlFor="sort" className="label">
                Sort by
              </label>
              <select id="sort" name="sort" defaultValue={result.sort} className="input">
                {Object.entries(SORTS).map(([key, s]) => (
                  <option key={key} value={key}>
                    {s.label}
                  </option>
                ))}
              </select>
            </div>
            <div className="flex gap-2">
              <button type="submit" className="btn-dark flex-1">
                Apply
              </button>
              <Link href="/shop" className="btn-outline">
                Reset
              </Link>
            </div>
          </form>
        </aside>

        <section aria-label="Products">
          {result.rows.length === 0 ? (
            <div className="card flex flex-col items-center p-16 text-center">
              <p className="text-5xl" aria-hidden>
                🔍
              </p>
              <h2 className="mt-4 text-xl font-bold">No products match your filters</h2>
              <p className="mt-1 text-neutral-500">Try a different search or clear the filters.</p>
              <Link href="/shop" className="btn-primary mt-6">
                Clear filters
              </Link>
            </div>
          ) : (
            <div className="grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 xl:grid-cols-3">
              {result.rows.map((p, i) => (
                <ProductCard key={p.id} product={p} currency={settings.currency} priority={i < 3} />
              ))}
            </div>
          )}

          {result.pages > 1 && (
            <nav aria-label="Pagination" className="mt-12 flex items-center justify-center gap-2">
              {result.page > 1 && (
                <Link href={pageHref(result.page - 1)} className="btn-outline">
                  Previous
                </Link>
              )}
              <span className="px-3 text-sm text-neutral-500">
                Page {result.page} of {result.pages}
              </span>
              {result.page < result.pages && (
                <Link href={pageHref(result.page + 1)} className="btn-outline">
                  Next
                </Link>
              )}
            </nav>
          )}
        </section>
      </div>
    </div>
  );
}
