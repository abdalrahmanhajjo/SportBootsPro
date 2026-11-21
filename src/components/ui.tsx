"use client";

import { startTransition, useActionState, type FormEvent, type ReactNode } from "react";
import { useFormStatus } from "react-dom";

/**
 * Like useActionState, but submits through onSubmit so React does NOT auto-reset the form
 * afterwards. Use for edit forms whose fields must keep their current (controlled) values.
 */
export function useActionNoReset<S extends object>(
  fn: (prev: S, formData: FormData) => Promise<S>,
  initial: S,
  confirmMessage?: (fd: FormData) => string | null,
) {
  const [state, dispatch, pending] = useActionState<S, FormData>(fn, initial as Awaited<S>);
  const onSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const submitter = (e.nativeEvent as SubmitEvent).submitter as HTMLElement | null;
    const fd = new FormData(e.currentTarget, submitter);
    const msg = confirmMessage?.(fd);
    if (msg && !confirm(msg)) return;
    startTransition(() => dispatch(fd));
  };
  return [state, onSubmit, pending] as const;
}

export function SubmitButton({
  children,
  pendingText = "Please wait…",
  className = "btn-primary",
  disabled,
  pending: pendingOverride,
}: {
  children: ReactNode;
  pendingText?: string;
  className?: string;
  disabled?: boolean;
  /** Pass when the form submits via onSubmit (useActionNoReset) instead of a form action. */
  pending?: boolean;
}) {
  const status = useFormStatus();
  const pending = pendingOverride ?? status.pending;
  return (
    <button type="submit" className={className} disabled={pending || disabled} aria-busy={pending}>
      {pending ? pendingText : children}
    </button>
  );
}

export function FormMessage({ state }: { state: { ok?: boolean; message?: string } }) {
  if (!state.message) return null;
  return (
    <p
      role={state.ok ? "status" : "alert"}
      className={`rounded-xl px-4 py-3 text-sm font-medium ${
        state.ok ? "bg-emerald-50 text-emerald-800" : "bg-red-50 text-red-700"
      }`}
    >
      {state.message}
    </p>
  );
}

export function Field({
  label,
  name,
  error,
  children,
  hint,
}: {
  label: string;
  name: string;
  error?: string;
  hint?: string;
  children: ReactNode;
}) {
  return (
    <div>
      <label htmlFor={name} className="label">
        {label}
      </label>
      {children}
      {hint && !error && <p className="mt-1 text-xs text-neutral-500">{hint}</p>}
      {error && (
        <p id={`${name}-error`} className="field-error">
          {error}
        </p>
      )}
    </div>
  );
}
