import { Download } from "lucide-react";
import { deleteSubscriber } from "@/app/actions/admin";
import { EmptyState, formatDate, PageHeader } from "@/components/admin/bits";
import { ConfirmButton } from "@/components/admin/ConfirmButton";
import { listSubscribers } from "@/lib/admin-queries";

export const metadata = { title: "Subscribers" };

export default async function SubscribersPage() {
  const subs = await listSubscribers();
  return (
    <>
      <PageHeader
        title="Newsletter subscribers"
        description={`${subs.length} subscriber${subs.length === 1 ? "" : "s"}`}
        actions={
          subs.length > 0 && (
            <a href="/admin/subscribers/export" className="btn-outline">
              <Download className="h-4 w-4" /> Export CSV
            </a>
          )
        }
      />
      {subs.length === 0 ? (
        <EmptyState title="No subscribers yet" text="People who sign up in the footer or home page appear here." />
      ) : (
        <div className="card overflow-x-auto">
          <table className="table-base">
            <thead>
              <tr>
                <th>Email</th>
                <th>Subscribed</th>
                <th className="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {subs.map((s) => (
                <tr key={s.id}>
                  <td className="font-medium">{s.email}</td>
                  <td className="text-neutral-500">{formatDate(s.createdAt)}</td>
                  <td className="text-right">
                    <form action={deleteSubscriber}>
                      <input type="hidden" name="id" value={s.id} />
                      <ConfirmButton message={`Remove ${s.email}?`} className="btn-danger btn-sm">
                        Remove
                      </ConfirmButton>
                    </form>
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
