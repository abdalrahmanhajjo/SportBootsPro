import type { Metadata } from "next";
import Link from "next/link";
import { ExternalLink, LogOut } from "lucide-react";
import { logout } from "@/app/actions/admin";
import { AdminNav } from "@/components/admin/AdminNav";
import { unreadMessageCount } from "@/lib/admin-queries";
import { requireAdmin } from "@/lib/auth";

export const dynamic = "force-dynamic";
export const metadata: Metadata = { title: { default: "Admin", template: "%s · Admin" }, robots: { index: false, follow: false } };

export default async function AdminLayout({ children }: LayoutProps<"/admin">) {
  const session = await requireAdmin();
  const unread = await unreadMessageCount();
  return (
    <div className="min-h-screen bg-neutral-50 lg:grid lg:grid-cols-[240px_1fr]">
      <aside className="bg-ink p-4 text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
        <Link href="/admin" className="mb-6 flex items-center gap-2 px-2 pt-2 text-lg font-black">
          <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-sm">SB</span>
          Admin
        </Link>
        <AdminNav unreadMessages={unread} />
        <div className="mt-6 space-y-1 border-t border-white/10 pt-4 lg:mt-auto">
          <p className="truncate px-3 text-xs text-neutral-500">{session.email}</p>
          <Link href="/" target="_blank" className="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-neutral-400 hover:text-white">
            <ExternalLink className="h-4 w-4" /> View store
          </Link>
          <form action={logout}>
            <button type="submit" className="flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2 text-sm text-neutral-400 hover:text-white">
              <LogOut className="h-4 w-4" /> Sign out
            </button>
          </form>
        </div>
      </aside>
      <main className="min-w-0 p-4 sm:p-8">{children}</main>
    </div>
  );
}
