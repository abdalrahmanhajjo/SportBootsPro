"use client";

import { useActionState } from "react";
import { saveCategory, type AdminFormState } from "@/app/actions/admin";
import { FormMessage, SubmitButton } from "@/components/ui";

export function CategoryForm({ category }: { category?: { id: number; name: string; description: string; icon: string; imageUrl: string | null } }) {
  const [state, action] = useActionState<AdminFormState, FormData>(saveCategory, {});
  const e = state.errors ?? {};
  const uid = category?.id ?? "new";
  const v = state.values ?? {};
  return (
    <form action={action} key={JSON.stringify(v)} className="space-y-3">
      {category && <input type="hidden" name="id" value={category.id} />}
      <div className="grid gap-3 sm:grid-cols-[80px_1fr]">
        <div>
          <label htmlFor={`icon-${uid}`} className="label">
            Icon
          </label>
          <input id={`icon-${uid}`} name="icon" defaultValue={v.icon ?? category?.icon ?? "👟"} maxLength={8} className="input text-center text-lg" />
        </div>
        <div>
          <label htmlFor={`name-${uid}`} className="label">
            Name
          </label>
          <input id={`name-${uid}`} name="name" defaultValue={v.name ?? category?.name} required className="input" />
          {e.name && <p className="field-error">{e.name}</p>}
        </div>
      </div>
      <div>
        <label htmlFor={`desc-${uid}`} className="label">
          Description
        </label>
        <input id={`desc-${uid}`} name="description" defaultValue={v.description ?? category?.description} className="input" />
      </div>
      <div>
        <label htmlFor={`img-${uid}`} className="label">
          Photo URL <span className="font-normal text-steel">(shown on the home page)</span>
        </label>
        <input id={`img-${uid}`} name="imageUrl" type="url" defaultValue={v.imageUrl ?? category?.imageUrl ?? ""} placeholder="https://…" className="input" />
        {e.imageUrl && <p className="field-error">{e.imageUrl}</p>}
      </div>
      <SubmitButton className={category ? "btn-outline btn-sm" : "btn-primary"} pendingText="Saving…">
        {category ? "Save" : "Add category"}
      </SubmitButton>
      <FormMessage state={state} />
    </form>
  );
}
