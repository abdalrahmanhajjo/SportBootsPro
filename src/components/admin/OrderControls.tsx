"use client";

import { useState } from "react";
import { saveOrderNote, setOrderStatus, type AdminFormState } from "@/app/actions/admin";
import { FormMessage, SubmitButton, useActionNoReset } from "@/components/ui";
import type { OrderStatus } from "@/db/schema";

const STATUSES: OrderStatus[] = ["pending", "processing", "shipped", "delivered", "cancelled"];

export function OrderStatusForm({ id, status }: { id: number; status: OrderStatus }) {
  const [state, onSubmit, pending] = useActionNoReset<AdminFormState>(setOrderStatus, {}, (fd) =>
    fd.get("status") === "cancelled" ? "Cancel this order? Its items will be returned to stock. This cannot be undone." : null,
  );
  const [value, setValue] = useState<OrderStatus>(status);
  const [synced, setSynced] = useState(status);
  if (synced !== status) {
    setSynced(status);
    setValue(status);
  }
  const locked = status === "cancelled";
  return (
    <form onSubmit={onSubmit} className="space-y-3">
      <input type="hidden" name="id" value={id} />
      <label htmlFor="status" className="label">
        Order status
      </label>
      <div className="flex gap-2">
        <select id="status" name="status" value={value} onChange={(e) => setValue(e.target.value as OrderStatus)} disabled={locked} className="input capitalize">
          {STATUSES.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
        <SubmitButton className="btn-dark" pendingText="Saving…" pending={pending} disabled={locked}>
          Update
        </SubmitButton>
      </div>
      {locked && <p className="text-xs text-neutral-500">Cancelled orders are final.</p>}
      <FormMessage state={state} />
    </form>
  );
}

export function OrderNoteForm({ id, note }: { id: number; note: string }) {
  const [state, onSubmit, pending] = useActionNoReset<AdminFormState>(saveOrderNote, {});
  const [value, setValue] = useState(note);
  return (
    <form onSubmit={onSubmit} className="space-y-3">
      <input type="hidden" name="id" value={id} />
      <label htmlFor="adminNote" className="label">
        Internal note
      </label>
      <textarea id="adminNote" name="adminNote" rows={3} value={value} onChange={(e) => setValue(e.target.value)} placeholder="Only visible to admins" className="input" />
      <SubmitButton className="btn-outline btn-sm" pendingText="Saving…" pending={pending}>
        Save note
      </SubmitButton>
      <FormMessage state={state} />
    </form>
  );
}
