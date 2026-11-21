import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft, MessageCircle } from "lucide-react";
import { formatDate, StatCard, StatusBadge } from "@/components/admin/bits";
import { getCustomer } from "@/lib/admin-queries";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata = { title: "Customer" };

export default async function CustomerPage({ params }: PageProps<"/admin/customers/[id]">) {
  const id = Number.parseInt((await params).id, 10);
  if (!Number.isFinite(id)) notFound();
  const [data, settings] = await Promise.all([getCustomer(id), getSettings()]);
  if (!data) notFound();
  const { customer: c, history } = data;
  const money = (n: number) => formatMoney(n, settings.currency);
  const valid = history.filter((o) => o.status !== "cancelled");
  const spent = valid.reduce((s, o) => s + o.totalCents, 0);

  return (
    <>
      <Link href="/admin/customers" className="mb-4 inline-flex items-center gap-1 text-sm text-neutral-500 hover:text-neutral-900">
        <ArrowLeft className="h-4 w-4" /> Customers
      </Link>
      <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black">{c.name}</h1>
          <p className="text-sm text-neutral-500">
            {c.phone}
            {c.email && ` · ${c.email}`} · customer since {formatDate(c.createdAt)}
          </p>
        </div>
        <a href={`https://wa.me/${c.phone.replace(/[^0-9]/g, "")}`} target="_blank" rel="noopener noreferrer" className="btn-outline">
          <MessageCircle className="h-4 w-4" /> WhatsApp
        </a>
      </div>
      <div className="mb-6 grid gap-4 sm:grid-cols-3">
        <StatCard label="Orders" value={String(history.length)} />
        <StatCard label="Total spent" value={money(spent)} />
        <StatCard label="Average order" value={money(valid.length ? Math.round(spent / valid.length) : 0)} />
      </div>
      <div className="grid gap-6 xl:grid-cols-3">
        <section className="card overflow-x-auto xl:col-span-2">
          <h2 className="p-6 pb-2 font-bold">Order history</h2>
          <table className="table-base">
            <thead>
              <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Status</th>
                <th className="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              {history.map((o) => (
                <tr key={o.id}>
                  <td>
                    <Link href={`/admin/orders/${o.id}`} className="font-mono font-semibold hover:text-brand-700">
                      {o.number}
                    </Link>
                  </td>
                  <td>{formatDate(o.createdAt)}</td>
                  <td>
                    <StatusBadge status={o.status} />
                  </td>
                  <td className="text-right font-semibold">{money(o.totalCents)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </section>
        <section className="card h-fit p-6 text-sm">
          <h2 className="font-bold">Latest address</h2>
          <p className="mt-2 whitespace-pre-line text-neutral-600">
            {c.city}
            {"\n"}
            {c.address}
          </p>
        </section>
      </div>
    </>
  );
}
