import Link from "next/link";
import { updateStock } from "@/app/actions/admin";
import { Notice, PageHeader, StatCard } from "@/components/admin/bits";
import { SubmitButton } from "@/components/ui";
import { listProducts } from "@/lib/admin-queries";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata = { title: "Inventory" };

export default async function InventoryPage({ searchParams }: PageProps<"/admin/inventory">) {
  const sp = await searchParams;
  const [settings, rows] = await Promise.all([getSettings(), listProducts({})]);
  const sorted = [...rows].sort((a, b) => a.product.stock - b.product.stock);
  const units = rows.reduce((n, r) => n + r.product.stock, 0);
  const value = rows.reduce((n, r) => n + r.product.stock * r.product.priceCents, 0);
  const out = rows.filter((r) => r.product.stock === 0).length;
  const low = rows.filter((r) => r.product.stock > 0 && r.product.stock <= settings.lowStockThreshold).length;

  return (
    <>
      <PageHeader title="Inventory" description={`Products at or below ${settings.lowStockThreshold} units are marked low stock.`} />
      {sp.saved && <Notice>Stock levels updated.</Notice>}
      <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Units in stock" value={units.toLocaleString("en-US")} />
        <StatCard label="Stock value (retail)" value={formatMoney(value, settings.currency)} />
        <StatCard label="Low stock" value={String(low)} />
        <StatCard label="Out of stock" value={String(out)} />
      </div>
      <form action={updateStock} className="card overflow-x-auto">
        <table className="table-base">
          <thead>
            <tr>
              <th>Product</th>
              <th>Category</th>
              <th>Sizes</th>
              <th>Status</th>
              <th className="w-36">Stock</th>
            </tr>
          </thead>
          <tbody>
            {sorted.map(({ product: p, categoryName }) => (
              <tr key={p.id}>
                <td>
                  <Link href={`/admin/products/${p.id}`} className="font-medium hover:text-brand-700">
                    {p.icon} {p.name}
                  </Link>
                  {!p.isActive && <span className="ml-2 text-xs text-neutral-400">(hidden)</span>}
                </td>
                <td>{categoryName ?? "—"}</td>
                <td className="text-xs text-neutral-500">{p.sizes.join(", ") || "—"}</td>
                <td>
                  {p.stock === 0 ? (
                    <span className="badge bg-red-50 text-red-700">Out of stock</span>
                  ) : p.stock <= settings.lowStockThreshold ? (
                    <span className="badge bg-amber-50 text-amber-800">Low</span>
                  ) : (
                    <span className="badge bg-emerald-50 text-emerald-800">OK</span>
                  )}
                </td>
                <td>
                  <label htmlFor={`stock_${p.id}`} className="sr-only">
                    Stock for {p.name}
                  </label>
                  <input id={`stock_${p.id}`} name={`stock_${p.id}`} type="number" min={0} max={100000} defaultValue={p.stock} className="input py-1.5" />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <div className="flex justify-end border-t border-neutral-200 p-4">
          <SubmitButton pendingText="Saving…">Save stock levels</SubmitButton>
        </div>
      </form>
    </>
  );
}
