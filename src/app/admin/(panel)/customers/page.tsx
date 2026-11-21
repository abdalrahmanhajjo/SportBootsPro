import Link from "next/link";
import { EmptyState, formatDate, PageHeader, Pagination } from "@/components/admin/bits";
import { listCustomers } from "@/lib/admin-queries";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata = { title: "Customers" };
const one = (v: string | string[] | undefined) => (Array.isArray(v) ? v[0] : v);

export default async function CustomersPage({ searchParams }: PageProps<"/admin/customers">) {
  const sp = await searchParams;
  const f = { q: one(sp.q), page: one(sp.page) };
  const [settings, data] = await Promise.all([getSettings(), listCustomers(f)]);

  return (
    <>
      <PageHeader title="Customers" description={`${data.total} customer${data.total === 1 ? "" : "s"} — created automatically at checkout`} />
      <form action="/admin/customers" className="mb-4 flex gap-2">
        <label htmlFor="q" className="sr-only">
          Search customers
        </label>
        <input id="q" name="q" type="search" defaultValue={f.q} placeholder="Search by name, phone or email" className="input max-w-md" />
        <button type="submit" className="btn-dark">
          Search
        </button>
      </form>
      {data.rows.length === 0 ? (
        <EmptyState title="No customers yet" text="Customers are added when they place their first order." />
      ) : (
        <div className="card overflow-x-auto">
          <table className="table-base">
            <thead>
              <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>City</th>
                <th>Orders</th>
                <th>Total spent</th>
                <th>Last order</th>
              </tr>
            </thead>
            <tbody>
              {data.rows.map(({ customer: c, orderCount, spent, lastOrder }) => (
                <tr key={c.id} className="hover:bg-neutral-50">
                  <td>
                    <Link href={`/admin/customers/${c.id}`} className="font-medium hover:text-brand-700">
                      {c.name}
                    </Link>
                    {c.email && <div className="text-xs text-neutral-500">{c.email}</div>}
                  </td>
                  <td>{c.phone}</td>
                  <td>{c.city}</td>
                  <td>{orderCount}</td>
                  <td className="font-semibold">{formatMoney(spent, settings.currency)}</td>
                  <td className="text-neutral-500">{formatDate(lastOrder)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <Pagination
        page={data.page}
        pages={data.pages}
        href={(p) => `/admin/customers?${new URLSearchParams({ ...(f.q ? { q: f.q } : {}), page: String(p) })}`}
      />
    </>
  );
}
