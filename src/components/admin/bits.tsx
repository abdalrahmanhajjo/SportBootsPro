import Link from "next/link";
import type { ReactNode } from "react";
import type { OrderStatus } from "@/db/schema";

export const STATUS_STYLES: Record<OrderStatus, string> = {
  pending: "bg-amber-50 text-amber-800 ring-amber-200",
  processing: "bg-blue-50 text-blue-800 ring-blue-200",
  shipped: "bg-violet-50 text-violet-800 ring-violet-200",
  delivered: "bg-emerald-50 text-emerald-800 ring-emerald-200",
  cancelled: "bg-neutral-100 text-neutral-600 ring-neutral-200",
};

export function StatusBadge({ status }: { status: OrderStatus }) {
  return <span className={`badge capitalize ring-1 ${STATUS_STYLES[status]}`}>{status}</span>;
}

export function PageHeader({ title, description, actions }: { title: string; description?: string; actions?: ReactNode }) {
  return (
    <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 className="text-2xl font-black tracking-tight">{title}</h1>
        {description && <p className="mt-1 text-sm text-neutral-500">{description}</p>}
      </div>
      {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
    </div>
  );
}

export function StatCard({ label, value, hint, icon }: { label: string; value: string; hint?: string; icon?: ReactNode }) {
  return (
    <div className="card p-5">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium text-neutral-500">{label}</p>
        {icon && <span className="text-neutral-400">{icon}</span>}
      </div>
      <p className="mt-2 text-2xl font-black tracking-tight">{value}</p>
      {hint && <p className="mt-1 text-xs text-neutral-500">{hint}</p>}
    </div>
  );
}

export function Notice({ tone = "success", children }: { tone?: "success" | "error" | "info"; children: ReactNode }) {
  const styles = {
    success: "bg-emerald-50 text-emerald-800",
    error: "bg-red-50 text-red-700",
    info: "bg-blue-50 text-blue-800",
  }[tone];
  return (
    <p role={tone === "error" ? "alert" : "status"} className={`mb-6 rounded-xl px-4 py-3 text-sm font-medium ${styles}`}>
      {children}
    </p>
  );
}

/** Simple, dependency-free bar chart. */
export function BarChart({
  data,
  format,
}: {
  data: { label: string; value: number; sub?: string }[];
  format: (n: number) => string;
}) {
  const max = Math.max(1, ...data.map((d) => d.value));
  return (
    <div>
      <div className="flex h-48 items-end gap-1.5" role="img" aria-label="Sales chart">
        {data.map((d) => (
          <div key={d.label} className="group relative flex h-full flex-1 flex-col justify-end">
            <div
              className="min-h-[2px] rounded-t-md bg-brand-600/80 transition group-hover:bg-brand-600"
              style={{ height: `${(d.value / max) * 100}%` }}
            />
            <div className="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 rounded-lg bg-ink px-2 py-1 text-center text-[11px] whitespace-nowrap text-white group-hover:block">
              <div className="font-semibold">{format(d.value)}</div>
              <div className="text-neutral-400">
                {d.label}
                {d.sub ? ` · ${d.sub}` : ""}
              </div>
            </div>
          </div>
        ))}
      </div>
      <div className="mt-2 flex justify-between text-[11px] text-neutral-400">
        <span>{data[0]?.label}</span>
        <span>{data[data.length - 1]?.label}</span>
      </div>
    </div>
  );
}

export function Pagination({ page, pages, href }: { page: number; pages: number; href: (p: number) => string }) {
  if (pages <= 1) return null;
  return (
    <nav aria-label="Pagination" className="mt-6 flex items-center justify-end gap-2 text-sm">
      {page > 1 && (
        <Link href={href(page - 1)} className="btn-outline btn-sm">
          Previous
        </Link>
      )}
      <span className="text-neutral-500">
        Page {page} of {pages}
      </span>
      {page < pages && (
        <Link href={href(page + 1)} className="btn-outline btn-sm">
          Next
        </Link>
      )}
    </nav>
  );
}

export function EmptyState({ title, text, action }: { title: string; text?: string; action?: ReactNode }) {
  return (
    <div className="card flex flex-col items-center p-12 text-center">
      <p className="font-semibold">{title}</p>
      {text && <p className="mt-1 text-sm text-neutral-500">{text}</p>}
      {action && <div className="mt-4">{action}</div>}
    </div>
  );
}

export const formatDate = (d: Date | string | null) =>
  d ? new Intl.DateTimeFormat("en-GB", { day: "numeric", month: "short", year: "numeric" }).format(new Date(d)) : "—";

export const formatDateTime = (d: Date | string) =>
  new Intl.DateTimeFormat("en-GB", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }).format(new Date(d));
