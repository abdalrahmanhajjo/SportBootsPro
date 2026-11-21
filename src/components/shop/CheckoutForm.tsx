"use client";

import { useActionState } from "react";
import { checkout, type FormState } from "@/app/actions/shop";
import { Field, FormMessage, SubmitButton } from "@/components/ui";

export function CheckoutForm({ totalLabel }: { totalLabel: string }) {
  const [state, action] = useActionState<FormState, FormData>(checkout, {});
  const e = state.errors ?? {};
  const v = state.values ?? {};
  const aria = (name: string) => (e[name] ? { "aria-invalid": true, "aria-describedby": `${name}-error` } : {});

  return (
    <form action={action} key={JSON.stringify(v)} className="space-y-5" noValidate>
      <FormMessage state={state} />
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="Full name" name="name" error={e.name}>
          <input id="name" name="name" defaultValue={v.name} autoComplete="name" required className="input" {...aria("name")} />
        </Field>
        <Field label="Phone (WhatsApp)" name="phone" error={e.phone}>
          <input id="phone" name="phone" defaultValue={v.phone} type="tel" autoComplete="tel" placeholder="+961 70 123 456" required className="input" {...aria("phone")} />
        </Field>
      </div>
      <Field label="Email (optional)" name="email" error={e.email} hint="We'll send your order confirmation here.">
        <input id="email" name="email" defaultValue={v.email} type="email" autoComplete="email" className="input" {...aria("email")} />
      </Field>
      <Field label="City / area" name="city" error={e.city}>
        <input id="city" name="city" defaultValue={v.city} autoComplete="address-level2" placeholder="e.g. Tripoli, Beirut, Saida" required className="input" {...aria("city")} />
      </Field>
      <Field label="Full address" name="address" error={e.address}>
        <textarea id="address" name="address" defaultValue={v.address} rows={3} autoComplete="street-address" placeholder="Street, building, floor, nearby landmark" required className="input" {...aria("address")} />
      </Field>
      <Field label="Order notes (optional)" name="notes" error={e.notes}>
        <textarea id="notes" name="notes" defaultValue={v.notes} rows={2} placeholder="Best time to deliver, etc." className="input" />
      </Field>

      <div className="rounded-2xl border border-neutral-200 p-4">
        <p className="font-semibold">Payment</p>
        <label className="mt-3 flex items-center gap-3 rounded-xl border border-neutral-900 bg-neutral-50 p-3 text-sm">
          <input type="radio" name="payment" value="cod" defaultChecked className="accent-brand-600" />
          <span>
            <span className="font-semibold">Cash on delivery</span>
            <span className="block text-neutral-500">Pay the courier when your order arrives.</span>
          </span>
        </label>
      </div>

      <SubmitButton className="btn-primary w-full py-3.5 text-base" pendingText="Placing your order…">
        Place order · {totalLabel}
      </SubmitButton>
      <p className="text-center text-xs text-neutral-500">We&apos;ll call or WhatsApp you to confirm before delivery.</p>
    </form>
  );
}
