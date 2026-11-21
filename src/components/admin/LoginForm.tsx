"use client";

import { useActionState } from "react";
import { login, type AdminFormState } from "@/app/actions/admin";
import { Field, FormMessage, SubmitButton } from "@/components/ui";

export function LoginForm() {
  const [state, action] = useActionState<AdminFormState, FormData>(login, {});
  const e = state.errors ?? {};
  return (
    <form action={action} key={state.email ?? ""} className="space-y-4">
      <FormMessage state={state} />
      <Field label="Email" name="email" error={e.email}>
        <input id="email" name="email" type="email" defaultValue={state.email} autoComplete="username" required className="input" />
      </Field>
      <Field label="Password" name="password" error={e.password}>
        <input id="password" name="password" type="password" autoComplete="current-password" required className="input" />
      </Field>
      <SubmitButton className="btn-primary w-full py-3" pendingText="Signing in…">
        Sign in
      </SubmitButton>
    </form>
  );
}
