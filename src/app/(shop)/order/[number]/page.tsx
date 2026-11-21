import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { eq } from "drizzle-orm";
import { CheckCircle2 } from "lucide-react";
import { getDb } from "@/db";
import { orderItems, orders } from "@/db/schema";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata: Metadata = { title: "Order confirmed", robots: { index: false } };

export default async function OrderConfirmationPage({ params }: PageProps<"/order/[number]">) {
  const { number } = await params;
  if (!/^SBP-[A-Z0-9]{8}$/.test(number)) notFound();
  const db = await getDb();
  const [order] = await db.select().from(orders).where(eq(orders.number, number));
  if (!order) notFound();
  const [items, settings] = await Promise.all([
    db.select().from(orderItems).where(eq(orderItems.orderId, order.id)),
    getSettings(),
  ]);
  const money = (c: number) => formatMoney(c, settings.currency);
  const whatsappText = encodeURIComponent(`Hi! I just placed order ${order.number}.`);

  return (
    <div className="container-page max-w-3xl py-16">
      <div className="text-center">
        <CheckCircle2 className="mx-auto h-16 w-16 text-emerald-500" />
        <h1 className="mt-4 text-4xl font-black tracking-tight">Thank you, {order.customerName.split(" ")[0]}!</h1>
        <p className="mt-2 text-neutral-500">
          Your order <span className="font-mono font-semibold text-neutral-900">{order.number}</span> has been received.
          We&apos;ll contact you on {order.phone} to confirm delivery.
        </p>
      </div>

      <div className="card mt-10 p-6 sm:p-8">
        <ul className="divide-y divide-neutral-100">
          {items.map((i) => (
            <li key={i.id} className="flex justify-between py-3 text-sm">
              <span>
                {i.productName} <span className="text-neutral-500">· size {i.size} × {i.quantity}</span>
              </span>
              <span className="font-medium">{money(i.unitCents * i.quantity)}</span>
            </li>
          ))}
        </ul>
        <dl className="mt-4 space-y-1.5 border-t border-neutral-200 pt-4 text-sm">
          <div className="flex justify-between">
            <dt className="text-neutral-500">Subtotal</dt>
            <dd>{money(order.subtotalCents)}</dd>
          </div>
          <div className="flex justify-between">
            <dt className="text-neutral-500">Delivery</dt>
            <dd>{order.shippingCents === 0 ? "Free" : money(order.shippingCents)}</dd>
          </div>
          <div className="flex justify-between pt-1 text-base font-bold">
            <dt>Total (cash on delivery)</dt>
            <dd>{money(order.totalCents)}</dd>
          </div>
        </dl>
        <div className="mt-6 rounded-xl bg-neutral-50 p-4 text-sm">
          <p className="font-semibold">Delivering to</p>
          <p className="mt-1 text-neutral-600">
            {order.city} — {order.address}
          </p>
        </div>
      </div>

      <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
        <Link href="/shop" className="btn-dark px-7 py-3">
          Continue shopping
        </Link>
        {settings.whatsapp && (
          <a href={`https://wa.me/${settings.whatsapp}?text=${whatsappText}`} target="_blank" rel="noopener noreferrer" className="btn-outline px-7 py-3">
            Message us on WhatsApp
          </a>
        )}
      </div>
    </div>
  );
}
