import Link from "next/link";
import { BarChart, formatDate, PageHeader, StatCard, StatusBadge } from "@/components/admin/bits";
import { ORDER_STATUSES } from "@/db/schema";
import { getReport } from "@/lib/admin-queries";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata = { title: "Reports" };
const RANGES = [7, 30, 90] as const;

export default async function ReportsPage({ searchParams }: PageProps<"/admin/reports">) {
  const sp = await searchParams;
  const days = RANGES.find((r) => String(r) === sp.days) ?? 30;
  const [settings, r] = await Promise.all([getSettings(), getReport(days)]);
  const money = (c: number) => formatMoney(c, settings.currency);
  const aov = r.summary.orders ? Math.round(r.summary.revenue / r.summary.orders) : 0;
  const catTotal = r.byCategory.reduce((s, c) => s + c.revenue, 0) || 1;
  const statusMap = Object.fromEntries(r.byStatus.map((s) => [s.status, s.count]));

  return (
    <>
      <PageHeader
        title="Reports"
        description={`Last ${days} days · cancelled orders excluded from revenue`}
        actions={RANGES.map((d) => (
          <Link key={d} href={`/admin/reports?days=${d}`} className={d === days ? "btn-dark btn-sm" : "btn-outline btn-sm"}>
            {d} days
          </Link>
        ))}
      />
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Revenue" value={money(r.summary.revenue)} />
        <StatCard label="Orders" value={String(r.summary.orders)} />
        <StatCard label="Average order value" value={money(aov)} />
        <StatCard label="Customers who ordered" value={String(r.summary.customers)} />
      </div>

      <section className="card mt-6 p-6">
        <h2 className="mb-6 font-bold">Daily revenue</h2>
        <BarChart data={r.sales.map((s) => ({ label: formatDate(s.day), value: s.revenue, sub: `${s.orders} orders` }))} format={money} />
      </section>

      <div className="mt-6 grid gap-6 xl:grid-cols-2">
        <section className="card overflow-x-auto">
          <h2 className="p-6 pb-2 font-bold">Top products</h2>
          {r.topProducts.length === 0 ? (
            <p className="px-6 pb-6 text-sm text-neutral-500">No sales in this period.</p>
          ) : (
            <table className="table-base">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Units</th>
                  <th className="text-right">Revenue</th>
                </tr>
              </thead>
              <tbody>
                {r.topProducts.map((p) => (
                  <tr key={p.name}>
                    <td className="font-medium">{p.name}</td>
                    <td>{p.units}</td>
                    <td className="text-right font-semibold">{money(p.revenue)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </section>

        <section className="card p-6">
          <h2 className="font-bold">Revenue by category</h2>
          {r.byCategory.length === 0 ? (
            <p className="mt-4 text-sm text-neutral-500">No sales in this period.</p>
          ) : (
            <ul className="mt-5 space-y-4">
              {r.byCategory.map((c) => (
                <li key={c.name}>
                  <div className="mb-1 flex justify-between text-sm">
                    <span className="font-medium">{c.name}</span>
                    <span>
                      {money(c.revenue)} <span className="text-neutral-400">· {Math.round((c.revenue / catTotal) * 100)}%</span>
                    </span>
                  </div>
                  <div className="h-2 rounded-full bg-neutral-100">
                    <div className="h-2 rounded-full bg-brand-600" style={{ width: `${(c.revenue / catTotal) * 100}%` }} />
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="card p-6">
          <h2 className="font-bold">Orders by status</h2>
          <ul className="mt-4 space-y-2.5">
            {ORDER_STATUSES.map((s) => (
              <li key={s} className="flex items-center justify-between text-sm">
                <StatusBadge status={s} />
                <span className="font-semibold">{statusMap[s] ?? 0}</span>
              </li>
            ))}
          </ul>
        </section>

        <section className="card overflow-x-auto">
          <h2 className="p-6 pb-2 font-bold">Top cities</h2>
          {r.byCity.length === 0 ? (
            <p className="px-6 pb-6 text-sm text-neutral-500">No orders in this period.</p>
          ) : (
            <table className="table-base">
              <thead>
                <tr>
                  <th>City</th>
                  <th>Orders</th>
                  <th className="text-right">Revenue</th>
                </tr>
              </thead>
              <tbody>
                {r.byCity.map((c) => (
                  <tr key={c.city}>
                    <td className="font-medium">{c.city}</td>
                    <td>{c.count}</td>
                    <td className="text-right font-semibold">{money(c.revenue)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </section>
      </div>
    </>
  );
}
