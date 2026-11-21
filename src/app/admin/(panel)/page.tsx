import Link from "next/link";
import { AlertTriangle, DollarSign, Mail, Package, ShoppingCart, Users } from "lucide-react";
import { BarChart, formatDate, PageHeader, StatCard, StatusBadge } from "@/components/admin/bits";
import { ProductImage } from "@/components/ProductImage";
import { getDashboard } from "@/lib/admin-queries";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata = { title: "Dashboard" };

export default async function DashboardPage() {
  const settings = await getSettings();
  const d = await getDashboard(settings.lowStockThreshold);
  const money = (c: number) => formatMoney(c, settings.currency);
  const aov = d.totals.orders ? Math.round(d.totals.revenue / d.totals.orders) : 0;
  const last14 = d.sales.reduce((s, x) => s + x.revenue, 0);

  return (
    <>
      <PageHeader
        title="Dashboard"
        description="Your store at a glance"
        actions={
          <Link href="/admin/products/new" className="btn-primary">
            Add product
          </Link>
        }
      />

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Revenue (all time)" value={money(d.totals.revenue)} hint={`Today: ${money(d.today.revenue)}`} icon={<DollarSign className="h-4 w-4" />} />
        <StatCard label="Orders" value={String(d.totals.orders)} hint={`${d.counts.pending} pending · avg ${money(aov)}`} icon={<ShoppingCart className="h-4 w-4" />} />
        <StatCard label="Customers" value={String(d.counts.customers)} icon={<Users className="h-4 w-4" />} />
        <StatCard label="Active products" value={String(d.counts.products)} hint={`${d.lowStock.length} low on stock`} icon={<Package className="h-4 w-4" />} />
      </div>

      {(d.counts.pending > 0 || d.unreadMessages > 0) && (
        <div className="mt-6 flex flex-wrap gap-3">
          {d.counts.pending > 0 && (
            <Link href="/admin/orders?status=pending" className="badge bg-amber-50 px-3 py-1.5 text-amber-800 ring-1 ring-amber-200">
              {d.counts.pending} order{d.counts.pending === 1 ? "" : "s"} waiting for confirmation →
            </Link>
          )}
          {d.unreadMessages > 0 && (
            <Link href="/admin/messages" className="badge gap-1.5 bg-blue-50 px-3 py-1.5 text-blue-800 ring-1 ring-blue-200">
              <Mail className="h-3.5 w-3.5" /> {d.unreadMessages} unread message{d.unreadMessages === 1 ? "" : "s"} →
            </Link>
          )}
        </div>
      )}

      <div className="mt-6 grid gap-6 xl:grid-cols-3">
        <section className="card p-6 xl:col-span-2">
          <div className="mb-6 flex items-baseline justify-between">
            <h2 className="font-bold">Sales · last 14 days</h2>
            <span className="text-sm text-neutral-500">{money(last14)}</span>
          </div>
          <BarChart
            data={d.sales.map((s) => ({ label: formatDate(s.day), value: s.revenue, sub: `${s.orders} orders` }))}
            format={money}
          />
        </section>

        <section className="card p-6">
          <h2 className="flex items-center gap-2 font-bold">
            <AlertTriangle className="h-4 w-4 text-amber-500" /> Low stock
          </h2>
          {d.lowStock.length === 0 ? (
            <p className="mt-4 text-sm text-neutral-500">All products are well stocked.</p>
          ) : (
            <ul className="mt-4 space-y-3">
              {d.lowStock.map((p) => (
                <li key={p.id} className="flex items-center justify-between text-sm">
                  <Link href={`/admin/products/${p.id}`} className="flex min-w-0 items-center gap-3 hover:text-brand-700">
                    <span className="h-9 w-9 shrink-0 overflow-hidden rounded-lg">
                      <ProductImage name={p.name} imageUrl={p.imageUrl} />
                    </span>
                    <span className="truncate">{p.name}</span>
                  </Link>
                  <span className={`badge ${p.stock === 0 ? "bg-red-50 text-red-700" : "bg-amber-50 text-amber-800"}`}>{p.stock} left</span>
                </li>
              ))}
            </ul>
          )}
          <Link href="/admin/inventory" className="mt-5 inline-block text-sm font-semibold text-brand-700 hover:underline">
            Manage inventory →
          </Link>
        </section>
      </div>

      <section className="card mt-6 overflow-hidden">
        <div className="flex items-center justify-between p-6 pb-2">
          <h2 className="font-bold">Recent orders</h2>
          <Link href="/admin/orders" className="text-sm font-semibold text-brand-700 hover:underline">
            View all →
          </Link>
        </div>
        {d.recent.length === 0 ? (
          <p className="px-6 pb-6 text-sm text-neutral-500">No orders yet. They will show up here as soon as customers check out.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="table-base">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th className="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                {d.recent.map((o) => (
                  <tr key={o.id} className="hover:bg-neutral-50">
                    <td>
                      <Link href={`/admin/orders/${o.id}`} className="font-mono font-semibold hover:text-brand-700">
                        {o.number}
                      </Link>
                    </td>
                    <td>{o.customerName}</td>
                    <td className="text-neutral-500">{formatDate(o.createdAt)}</td>
                    <td>
                      <StatusBadge status={o.status} />
                    </td>
                    <td className="text-right font-semibold">{money(o.totalCents)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </>
  );
}
