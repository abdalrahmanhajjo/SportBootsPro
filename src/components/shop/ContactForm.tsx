"use client";

import { useActionState } from "react";
import { sendContactMessage, type FormState } from "@/app/actions/shop";
import { Field, FormMessage, SubmitButton } from "@/components/ui";

export function ContactForm() {
  const [state, action] = useActionState<FormState, FormData>(sendContactMessage, {});
  const e = state.errors ?? {};
  const v = state.ok ? {} : (state.values ?? {});
  return (
    <form action={action} key={JSON.stringify(v) + String(state.ok)} className="space-y-5" noValidate>
      <FormMessage state={state} />
      {/* Honeypot: hidden from people, bots fill it in */}
      <div className="hidden" aria-hidden>
        <label htmlFor="website">Website</label>
        <input id="website" name="website" tabIndex={-1} autoComplete="off" />
      </div>
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="Name" name="name" error={e.name}>
          <input id="name" name="name" defaultValue={v.name} autoComplete="name" required className="input" />
        </Field>
        <Field label="Email" name="email" error={e.email}>
          <input id="email" name="email" type="email" defaultValue={v.email} autoComplete="email" required className="input" />
        </Field>
      </div>
      <Field label="Subject" name="subject" error={e.subject}>
        <input id="subject" name="subject" defaultValue={v.subject} required className="input" />
      </Field>
      <Field label="Message" name="message" error={e.message}>
        <textarea id="message" name="message" rows={6} defaultValue={v.message} required className="input" />
      </Field>
      <SubmitButton pendingText="Sending…">Send message</SubmitButton>
    </form>
  );
}
