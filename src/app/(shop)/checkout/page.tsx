import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { ProductImage } from "@/components/ProductImage";
import { CheckoutForm } from "@/components/shop/CheckoutForm";
import { getCart } from "@/lib/catalog";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata: Metadata = { title: "Checkout", robots: { index: false } };

export default async function CheckoutPage() {
  const [cart, settings] = await Promise.all([getCart(), getSettings()]);
  if (cart.lines.length === 0) redirect("/cart");
  const money = (c: number) => formatMoney(c, settings.currency);

  return (
    <div className="container-page py-12">
      <h1 className="display text-6xl">Checkout</h1>
      <div className="mt-10 grid gap-10 lg:grid-cols-[1fr_400px]">
        <section aria-label="Delivery details" className="card p-6 sm:p-8">
          <h2 className="mb-6 text-lg font-bold">Delivery details</h2>
          {cart.hasProblems ? (
            <p className="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">
              Some items in your cart changed. Please <Link href="/cart" className="font-semibold underline">review your cart</Link> first.
            </p>
          ) : (
            <CheckoutForm totalLabel={money(cart.totalCents)} />
          )}
        </section>
        <aside className="card h-fit p-6 lg:sticky lg:top-24">
          <h2 className="text-lg font-bold">Your order</h2>
          <ul className="mt-4 space-y-4">
            {cart.lines.map((l) => (
              <li key={l.key} className="flex items-center gap-3">
                <div className="h-14 w-14 shrink-0 overflow-hidden rounded-xl">
                  <ProductImage name={l.product.name} imageUrl={l.product.imageUrl} icon={l.product.icon} seed={l.product.id} />
                </div>
                <div className="flex-1 text-sm">
                  <p className="font-medium">{l.product.name}</p>
                  <p className="text-neutral-500">
                    Size {l.size} × {l.quantity}
                  </p>
                </div>
                <p className="text-sm font-semibold">{money(l.lineCents)}</p>
              </li>
            ))}
          </ul>
          <dl className="mt-6 space-y-2 border-t border-neutral-200 pt-4 text-sm">
            <div className="flex justify-between">
              <dt className="text-neutral-500">Subtotal</dt>
              <dd>{money(cart.subtotalCents)}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-neutral-500">Delivery</dt>
              <dd>{cart.shippingCents === 0 ? "Free" : money(cart.shippingCents)}</dd>
            </div>
            <div className="flex justify-between pt-2 text-base font-bold">
              <dt>Total</dt>
              <dd>{money(cart.totalCents)}</dd>
            </div>
          </dl>
        </aside>
      </div>
    </div>
  );
}
