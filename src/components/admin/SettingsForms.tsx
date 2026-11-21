"use client";

import { useActionState } from "react";
import { changePassword, saveSettings, type AdminFormState } from "@/app/actions/admin";
import { Field, FormMessage, SubmitButton } from "@/components/ui";

export function StoreSettingsForm({ initial }: { initial: Record<string, string> }) {
  const [state, action] = useActionState<AdminFormState, FormData>(saveSettings, {});
  const e = state.errors ?? {};
  const v = { ...initial, ...state.values };
  return (
    <form action={action} key={JSON.stringify(state.values ?? {})} className="space-y-5">
      <FormMessage state={state} />
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="Store name" name="storeName" error={e.storeName}>
          <input id="storeName" name="storeName" defaultValue={v.storeName} required className="input" />
        </Field>
        <Field label="Currency" name="currency" error={e.currency}>
          <select id="currency" name="currency" defaultValue={v.currency} className="input">
            <option value="USD">USD ($)</option>
            <option value="LBP">LBP (ل.ل)</option>
            <option value="EUR">EUR (€)</option>
          </select>
        </Field>
      </div>
      <Field label="Tagline" name="tagline" error={e.tagline} hint="Shown in the footer">
        <input id="tagline" name="tagline" defaultValue={v.tagline} className="input" />
      </Field>
      <div className="grid gap-5 sm:grid-cols-3">
        <Field label="Delivery fee" name="shipping" error={e.shipping}>
          <input id="shipping" name="shipping" inputMode="decimal" defaultValue={v.shipping} className="input" />
        </Field>
        <Field label="Free delivery from" name="freeShippingThreshold" error={e.freeShippingThreshold} hint="0 = never free">
          <input id="freeShippingThreshold" name="freeShippingThreshold" inputMode="decimal" defaultValue={v.freeShippingThreshold} className="input" />
        </Field>
        <Field label="Low-stock alert at" name="lowStockThreshold" error={e.lowStockThreshold}>
          <input id="lowStockThreshold" name="lowStockThreshold" type="number" min={0} defaultValue={v.lowStockThreshold} className="input" />
        </Field>
      </div>
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="WhatsApp number" name="whatsapp" error={e.whatsapp} hint="Digits only with country code, e.g. 96176536462">
          <input id="whatsapp" name="whatsapp" inputMode="numeric" defaultValue={v.whatsapp} className="input" />
        </Field>
        <Field label="Phone (display)" name="phone" error={e.phone}>
          <input id="phone" name="phone" defaultValue={v.phone} className="input" />
        </Field>
        <Field label="Email" name="email" error={e.email}>
          <input id="email" name="email" type="email" defaultValue={v.email} className="input" />
        </Field>
        <Field label="Address" name="address" error={e.address}>
          <input id="address" name="address" defaultValue={v.address} className="input" />
        </Field>
      </div>
      <SubmitButton pendingText="Saving…">Save settings</SubmitButton>
    </form>
  );
}

export function PasswordForm() {
  const [state, action] = useActionState<AdminFormState, FormData>(changePassword, {});
  const e = state.errors ?? {};
  return (
    <form action={action} className="space-y-4">
      <FormMessage state={state} />
      <Field label="Current password" name="current" error={e.current}>
        <input id="current" name="current" type="password" autoComplete="current-password" required className="input" />
      </Field>
      <Field label="New password" name="next" error={e.next} hint="At least 10 characters">
        <input id="next" name="next" type="password" autoComplete="new-password" required minLength={10} className="input" />
      </Field>
      <Field label="Confirm new password" name="confirm" error={e.confirm}>
        <input id="confirm" name="confirm" type="password" autoComplete="new-password" required className="input" />
      </Field>
      <SubmitButton className="btn-dark" pendingText="Updating…">
        Change password
      </SubmitButton>
    </form>
  );
}
