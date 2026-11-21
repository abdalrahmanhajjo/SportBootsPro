import Link from "next/link";
import { EmptyState, formatDateTime, PageHeader, Pagination, StatusBadge } from "@/components/admin/bits";
import { ORDER_STATUSES } from "@/db/schema";
import { listOrders } from "@/lib/admin-queries";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata = { title: "Orders" };
const one = (v: string | string[] | undefined) => (Array.isArray(v) ? v[0] : v);

export default async function OrdersPage({ searchParams }: PageProps<"/admin/orders">) {
  const sp = await searchParams;
  const f = { q: one(sp.q), status: one(sp.status), page: one(sp.page) };
  const [settings, data] = await Promise.all([getSettings(), listOrders(f)]);
  const all = Object.values(data.statusCounts).reduce((a, b) => a + (b ?? 0), 0);

  const href = (over: Record<string, string | undefined>) => {
    const p = new URLSearchParams();
    const merged = { q: f.q, status: f.status, ...over };
    for (const [k, v] of Object.entries(merged)) if (v) p.set(k, v);
    const s = p.toString();
    return s ? `/admin/orders?${s}` : "/admin/orders";
  };

  return (
    <>
      <PageHeader title="Orders" description={`${data.total} order${data.total === 1 ? "" : "s"}`} />

      <div className="mb-4 flex flex-wrap gap-2">
        {[{ key: undefined, label: "All", count: all }, ...ORDER_STATUSES.map((s) => ({ key: s, label: s, count: data.statusCounts[s] ?? 0 }))].map((t) => (
          <Link
            key={t.label}
            href={href({ status: t.key, page: undefined })}
            className={`badge px-3 py-1.5 capitalize ring-1 ${f.status === t.key || (!f.status && !t.key) ? "bg-ink text-white ring-ink" : "bg-white text-neutral-700 ring-neutral-200 hover:ring-neutral-400"}`}
          >
            {t.label} · {t.count}
          </Link>
        ))}
      </div>

      <form className="mb-4 flex gap-2" action="/admin/orders">
        {f.status && <input type="hidden" name="status" value={f.status} />}
        <label htmlFor="q" className="sr-only">
          Search orders
        </label>
        <input id="q" name="q" type="search" defaultValue={f.q} placeholder="Search by order number, name, phone or city" className="input max-w-md" />
        <button className="btn-dark" type="submit">
          Search
        </button>
      </form>

      {data.rows.length === 0 ? (
        <EmptyState title="No orders found" text={f.q || f.status ? "Try another filter." : "Orders will appear here when customers check out."} />
      ) : (
        <div className="card overflow-x-auto">
          <table className="table-base">
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>City</th>
                <th>Items</th>
                <th>Placed</th>
                <th>Status</th>
                <th className="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              {data.rows.map(({ order: o, itemCount }) => (
                <tr key={o.id} className="hover:bg-neutral-50">
                  <td>
                    <Link href={`/admin/orders/${o.id}`} className="font-mono font-semibold hover:text-brand-700">
                      {o.number}
                    </Link>
                  </td>
                  <td>
                    <div className="font-medium">{o.customerName}</div>
                    <div className="text-xs text-neutral-500">{o.phone}</div>
                  </td>
                  <td>{o.city}</td>
                  <td>{itemCount}</td>
                  <td className="whitespace-nowrap text-neutral-500">{formatDateTime(o.createdAt)}</td>
                  <td>
                    <StatusBadge status={o.status} />
                  </td>
                  <td className="text-right font-semibold">{formatMoney(o.totalCents, settings.currency)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <Pagination page={data.page} pages={data.pages} href={(p) => href({ page: p > 1 ? String(p) : undefined })} />
    </>
  );
}
