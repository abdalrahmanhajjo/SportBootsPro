import Link from "next/link";
import { Plus } from "lucide-react";
import { EmptyState, Notice, PageHeader } from "@/components/admin/bits";
import { ProductImage } from "@/components/ProductImage";
import { listCategoriesWithCounts, listProducts } from "@/lib/admin-queries";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata = { title: "Products" };
const one = (v: string | string[] | undefined) => (Array.isArray(v) ? v[0] : v);

export default async function ProductsPage({ searchParams }: PageProps<"/admin/products">) {
  const sp = await searchParams;
  const f = { q: one(sp.q), category: one(sp.category), status: one(sp.status) };
  const [settings, rows, cats] = await Promise.all([getSettings(), listProducts(f), listCategoriesWithCounts()]);

  return (
    <>
      <PageHeader
        title="Products"
        description={`${rows.length} product${rows.length === 1 ? "" : "s"}`}
        actions={
          <Link href="/admin/products/new" className="btn-primary">
            <Plus className="h-4 w-4" /> Add product
          </Link>
        }
      />
      {sp.saved && <Notice>Product saved.</Notice>}
      {sp.deleted && <Notice>Product deleted.</Notice>}
      {sp.hidden && <Notice tone="info">This product has orders, so it was hidden from the store instead of deleted.</Notice>}

      <form action="/admin/products" className="mb-4 flex flex-wrap gap-2">
        <label htmlFor="q" className="sr-only">
          Search
        </label>
        <input id="q" name="q" type="search" defaultValue={f.q} placeholder="Search products" className="input max-w-xs" />
        <label htmlFor="category" className="sr-only">
          Category
        </label>
        <select id="category" name="category" defaultValue={f.category ?? ""} className="input w-auto">
          <option value="">All categories</option>
          {cats.map(({ category: c }) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </select>
        <label htmlFor="status" className="sr-only">
          Visibility
        </label>
        <select id="status" name="status" defaultValue={f.status ?? ""} className="input w-auto">
          <option value="">Any visibility</option>
          <option value="active">Visible</option>
          <option value="hidden">Hidden</option>
        </select>
        <button type="submit" className="btn-dark">
          Filter
        </button>
      </form>

      {rows.length === 0 ? (
        <EmptyState title="No products found" action={<Link href="/admin/products/new" className="btn-primary">Add your first product</Link>} />
      ) : (
        <div className="card overflow-x-auto">
          <table className="table-base">
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Sold</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              {rows.map(({ product: p, categoryName, sold }) => (
                <tr key={p.id} className="hover:bg-neutral-50">
                  <td>
                    <Link href={`/admin/products/${p.id}`} className="flex items-center gap-3 font-medium hover:text-brand-700">
                      <span className="h-10 w-10 shrink-0 overflow-hidden rounded-lg">
                        <ProductImage name={p.name} imageUrl={p.imageUrl} icon={p.icon} seed={p.id} />
                      </span>
                      {p.name}
                    </Link>
                  </td>
                  <td>{categoryName ?? "—"}</td>
                  <td className="whitespace-nowrap">
                    {formatMoney(p.priceCents, settings.currency)}
                    {p.compareAtCents && <span className="ml-1 text-xs text-neutral-400 line-through">{formatMoney(p.compareAtCents, settings.currency)}</span>}
                  </td>
                  <td>
                    <span className={p.stock === 0 ? "font-semibold text-red-600" : p.stock <= settings.lowStockThreshold ? "font-semibold text-amber-600" : ""}>{p.stock}</span>
                  </td>
                  <td>{sold}</td>
                  <td>
                    <span className={`badge ring-1 ${p.isActive ? "bg-emerald-50 text-emerald-800 ring-emerald-200" : "bg-neutral-100 text-neutral-600 ring-neutral-200"}`}>
                      {p.isActive ? "Visible" : "Hidden"}
                    </span>
                    {p.isFeatured && <span className="badge ml-1 bg-brand-50 text-brand-700">Featured</span>}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </>
  );
}
