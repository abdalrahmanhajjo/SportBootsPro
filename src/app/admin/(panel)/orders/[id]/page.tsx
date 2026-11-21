import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft, MessageCircle, Phone } from "lucide-react";
import { formatDateTime, StatusBadge } from "@/components/admin/bits";
import { OrderNoteForm, OrderStatusForm } from "@/components/admin/OrderControls";
import { ProductImage } from "@/components/ProductImage";
import { getOrder } from "@/lib/admin-queries";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata = { title: "Order details" };

export default async function OrderDetailPage({ params }: PageProps<"/admin/orders/[id]">) {
  const id = Number.parseInt((await params).id, 10);
  if (!Number.isFinite(id)) notFound();
  const [data, settings] = await Promise.all([getOrder(id), getSettings()]);
  if (!data) notFound();
  const { order, items } = data;
  const money = (c: number) => formatMoney(c, settings.currency);
  const waPhone = order.phone.replace(/[^0-9]/g, "");
  const waText = encodeURIComponent(`Hello ${order.customerName.split(" ")[0]}, this is ${settings.storeName} about your order ${order.number}.`);

  return (
    <>
      <Link href="/admin/orders" className="mb-4 inline-flex items-center gap-1 text-sm text-neutral-500 hover:text-neutral-900">
        <ArrowLeft className="h-4 w-4" /> All orders
      </Link>
      <div className="mb-8 flex flex-wrap items-center gap-3">
        <h1 className="font-mono text-2xl font-black">{order.number}</h1>
        <StatusBadge status={order.status} />
        <span className="text-sm text-neutral-500">Placed {formatDateTime(order.createdAt)}</span>
      </div>

      <div className="grid gap-6 xl:grid-cols-3">
        <div className="space-y-6 xl:col-span-2">
          <section className="card overflow-hidden">
            <h2 className="p-6 pb-2 font-bold">Items</h2>
            <ul className="divide-y divide-neutral-100">
              {items.map(({ item, slug, icon, imageUrl }) => (
                <li key={item.id} className="flex items-center gap-4 px-6 py-4">
                  <div className="h-14 w-14 shrink-0 overflow-hidden rounded-xl">
                    <ProductImage name={item.productName} imageUrl={imageUrl} icon={icon ?? "👟"} seed={item.productId ?? 0} />
                  </div>
                  <div className="flex-1">
                    {slug ? (
                      <Link href={`/admin/products/${item.productId}`} className="font-medium hover:text-brand-700">
                        {item.productName}
                      </Link>
                    ) : (
                      <span className="font-medium">{item.productName}</span>
                    )}
                    <p className="text-sm text-neutral-500">
                      Size {item.size} · {money(item.unitCents)} × {item.quantity}
                    </p>
                  </div>
                  <p className="font-semibold">{money(item.unitCents * item.quantity)}</p>
                </li>
              ))}
            </ul>
            <dl className="space-y-1.5 border-t border-neutral-200 p-6 text-sm">
              <div className="flex justify-between">
                <dt className="text-neutral-500">Subtotal</dt>
                <dd>{money(order.subtotalCents)}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-neutral-500">Delivery</dt>
                <dd>{order.shippingCents === 0 ? "Free" : money(order.shippingCents)}</dd>
              </div>
              <div className="flex justify-between pt-1 text-base font-bold">
                <dt>Total · cash on delivery</dt>
                <dd>{money(order.totalCents)}</dd>
              </div>
            </dl>
          </section>

          {order.notes && (
            <section className="card p-6">
              <h2 className="font-bold">Customer note</h2>
              <p className="mt-2 text-sm whitespace-pre-line text-neutral-700">{order.notes}</p>
            </section>
          )}
        </div>

        <div className="space-y-6">
          <section className="card p-6">
            <OrderStatusForm id={order.id} status={order.status} />
          </section>

          <section className="card p-6 text-sm">
            <h2 className="font-bold">Customer</h2>
            <p className="mt-3 font-medium">
              {order.customerId ? (
                <Link href={`/admin/customers/${order.customerId}`} className="hover:text-brand-700">
                  {order.customerName}
                </Link>
              ) : (
                order.customerName
              )}
            </p>
            <p className="text-neutral-500">{order.phone}</p>
            {order.email && <p className="text-neutral-500">{order.email}</p>}
            <h3 className="mt-4 font-semibold">Delivery address</h3>
            <p className="mt-1 whitespace-pre-line text-neutral-600">
              {order.city}
              {"\n"}
              {order.address}
            </p>
            <div className="mt-4 flex gap-2">
              <a href={`https://wa.me/${waPhone}?text=${waText}`} target="_blank" rel="noopener noreferrer" className="btn-outline btn-sm">
                <MessageCircle className="h-4 w-4" /> WhatsApp
              </a>
              <a href={`tel:${order.phone}`} className="btn-outline btn-sm">
                <Phone className="h-4 w-4" /> Call
              </a>
            </div>
          </section>

          <section className="card p-6">
            <OrderNoteForm id={order.id} note={order.adminNote} />
          </section>
        </div>
      </div>
    </>
  );
}
