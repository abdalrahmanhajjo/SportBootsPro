import { deleteMessage, toggleMessageRead } from "@/app/actions/admin";
import { EmptyState, formatDateTime, PageHeader } from "@/components/admin/bits";
import { ConfirmButton } from "@/components/admin/ConfirmButton";
import { listMessages } from "@/lib/admin-queries";

export const metadata = { title: "Messages" };

export default async function MessagesPage() {
  const messages = await listMessages();
  const unread = messages.filter((m) => !m.isRead).length;
  return (
    <>
      <PageHeader title="Messages" description={`From the contact form · ${unread} unread`} />
      {messages.length === 0 ? (
        <EmptyState title="No messages yet" text="Messages sent from the Contact page appear here." />
      ) : (
        <ul className="space-y-4">
          {messages.map((m) => (
            <li key={m.id} className={`card p-5 ${m.isRead ? "" : "border-l-4 border-l-brand-600"}`}>
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p className="font-semibold">
                    {m.subject} {!m.isRead && <span className="badge ml-1 bg-brand-50 text-brand-700">New</span>}
                  </p>
                  <p className="text-sm text-neutral-500">
                    {m.name} ·{" "}
                    <a href={`mailto:${m.email}?subject=${encodeURIComponent(`Re: ${m.subject}`)}`} className="underline hover:text-neutral-900">
                      {m.email}
                    </a>{" "}
                    · {formatDateTime(m.createdAt)}
                  </p>
                </div>
                <div className="flex gap-2">
                  <form action={toggleMessageRead}>
                    <input type="hidden" name="id" value={m.id} />
                    <button type="submit" className="btn-outline btn-sm">
                      Mark as {m.isRead ? "unread" : "read"}
                    </button>
                  </form>
                  <form action={deleteMessage}>
                    <input type="hidden" name="id" value={m.id} />
                    <ConfirmButton message="Delete this message?" className="btn-danger btn-sm">
                      Delete
                    </ConfirmButton>
                  </form>
                </div>
              </div>
              <p className="mt-3 text-sm whitespace-pre-line text-neutral-700">{m.message}</p>
            </li>
          ))}
        </ul>
      )}
    </>
  );
}
