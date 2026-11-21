import { listSubscribers } from "@/lib/admin-queries";
import { getSession } from "@/lib/auth";

export const dynamic = "force-dynamic";

/** Quote a CSV cell and neutralise spreadsheet formula injection (=, +, -, @). */
function csvCell(value: string) {
  const safe = /^[=+\-@\t\r]/.test(value) ? `'${value}` : value;
  return `"${safe.replace(/"/g, '""')}"`;
}

export async function GET() {
  if (!(await getSession())) return new Response("Unauthorized", { status: 401 });
  const subs = await listSubscribers();
  const lines = ["email,subscribed_at", ...subs.map((s) => `${csvCell(s.email)},${csvCell(s.createdAt.toISOString())}`)];
  return new Response(lines.join("\n") + "\n", {
    headers: {
      "Content-Type": "text/csv; charset=utf-8",
      "Content-Disposition": `attachment; filename="subscribers-${new Date().toISOString().slice(0, 10)}.csv"`,
      "Cache-Control": "no-store",
    },
  });
}
