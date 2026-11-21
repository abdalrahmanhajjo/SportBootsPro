"use client";

import { useActionState } from "react";
import { subscribeNewsletter, type FormState } from "@/app/actions/shop";
import { SubmitButton } from "@/components/ui";

export function NewsletterForm({ dark = true, buttonClassName = "btn-primary" }: { dark?: boolean; buttonClassName?: string }) {
  const [state, action] = useActionState<FormState, FormData>(subscribeNewsletter, {});
  return (
    <form action={action} className="space-y-2">
      <div className="flex gap-2">
        <label htmlFor="newsletter-email" className="sr-only">
          Email address
        </label>
        <input
          id="newsletter-email"
          name="email"
          type="email"
          required
          placeholder="you@example.com"
          className={
            dark
              ? "min-w-0 flex-1 rounded-full border border-white/15 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-neutral-500 focus:border-white focus:outline-none"
              : "input flex-1 rounded-full"
          }
        />
        <SubmitButton pendingText="…" className={buttonClassName}>
          Subscribe
        </SubmitButton>
      </div>
      {state.message && (
        <p role="status" className={`text-xs ${state.ok ? "text-emerald-400" : "text-red-400"}`}>
          {state.message}
        </p>
      )}
    </form>
  );
}
