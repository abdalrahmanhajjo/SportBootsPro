import type { Metadata } from "next";
import Link from "next/link";
import { AlertTriangle, Trash2 } from "lucide-react";
import { removeCartLine, updateCartLine } from "@/app/actions/shop";
import { ProductImage } from "@/components/ProductImage";
import { getCart } from "@/lib/catalog";
import { MAX_QTY_PER_LINE } from "@/lib/cart";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata: Metadata = { title: "Your cart", robots: { index: false } };

export default async function CartPage() {
  const [cart, settings] = await Promise.all([getCart(), getSettings()]);
  const money = (c: number) => formatMoney(c, settings.currency);
  const toFree = settings.freeShippingThresholdCents - cart.subtotalCents;

  if (cart.lines.length === 0) {
    return (
      <div className="container-page flex flex-col items-center py-24 text-center">
        <p className="text-6xl" aria-hidden>🛍️</p>
        <h1 className="mt-6 text-3xl font-black">Your cart is empty</h1>
        <p className="mt-2 text-neutral-500">Find your next pair of boots in the shop.</p>
        <Link href="/shop" className="btn-primary mt-8 px-7 py-3">
          Start shopping
        </Link>
      </div>
    );
  }

  return (
    <div className="container-page py-12">
      <h1 className="text-4xl font-black tracking-tight">Your cart</h1>
      <p className="mt-1 text-neutral-500">
        {cart.count} item{cart.count === 1 ? "" : "s"}
      </p>

      <div className="mt-10 grid gap-10 lg:grid-cols-[1fr_380px]">
        <ul className="divide-y divide-neutral-200 border-y border-neutral-200">
          {cart.lines.map((line) => (
            <li key={line.key} className="flex gap-4 py-6">
              <Link href={`/products/${line.product.slug}`} className="h-28 w-28 shrink-0 overflow-hidden rounded-2xl">
                <ProductImage name={line.product.name} imageUrl={line.product.imageUrl} icon={line.product.icon} seed={line.product.id} />
              </Link>
              <div className="flex flex-1 flex-col">
                <div className="flex justify-between gap-4">
                  <div>
                    <Link href={`/products/${line.product.slug}`} className="font-semibold hover:text-brand-700">
                      {line.product.name}
                    </Link>
                    <p className="text-sm text-neutral-500">
                      Size {line.size} · {money(line.unitCents)}
                    </p>
                  </div>
                  <p className="font-semibold">{money(line.lineCents)}</p>
                </div>
                {line.problem && (
                  <p className="mt-2 flex items-center gap-1.5 text-sm font-medium text-amber-700">
                    <AlertTriangle className="h-4 w-4" /> {line.problem}
                  </p>
                )}
                <div className="mt-auto flex items-center gap-3 pt-3">
                  <form action={updateCartLine} className="flex items-center gap-2">
                    <input type="hidden" name="productId" value={line.product.id} />
                    <input type="hidden" name="size" value={line.size} />
                    <label htmlFor={`qty-${line.key}`} className="sr-only">
                      Quantity
                    </label>
                    <select id={`qty-${line.key}`} name="quantity" defaultValue={line.quantity} className="input w-20 py-1.5">
                      {Array.from({ length: MAX_QTY_PER_LINE }, (_, i) => i + 1).map((n) => (
                        <option key={n} value={n}>
                          {n}
                        </option>
                      ))}
                    </select>
                    <button type="submit" className="btn-ghost btn-sm">
                      Update
                    </button>
                  </form>
                  <form action={removeCartLine}>
                    <input type="hidden" name="productId" value={line.product.id} />
                    <input type="hidden" name="size" value={line.size} />
                    <button type="submit" className="btn-ghost btn-sm text-neutral-500 hover:text-red-600">
                      <Trash2 className="h-4 w-4" /> Remove
                    </button>
                  </form>
                </div>
              </div>
            </li>
          ))}
        </ul>

        <aside className="card h-fit space-y-4 p-6 lg:sticky lg:top-24">
          <h2 className="text-lg font-bold">Order summary</h2>
          <dl className="space-y-2 text-sm">
            <div className="flex justify-between">
              <dt className="text-neutral-500">Subtotal</dt>
              <dd>{money(cart.subtotalCents)}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-neutral-500">Delivery</dt>
              <dd>{cart.shippingCents === 0 ? "Free" : money(cart.shippingCents)}</dd>
            </div>
            <div className="flex justify-between border-t border-neutral-200 pt-3 text-base font-bold">
              <dt>Total</dt>
              <dd>{money(cart.totalCents)}</dd>
            </div>
          </dl>
          {toFree > 0 && settings.freeShippingThresholdCents > 0 && (
            <p className="rounded-xl bg-brand-50 px-3 py-2 text-xs font-medium text-brand-700">
              Add {money(toFree)} more for free delivery.
            </p>
          )}
          {cart.hasProblems ? (
            <p className="rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800">
              Please fix the items marked above before checking out.
            </p>
          ) : (
            <Link href="/checkout" className="btn-primary w-full py-3 text-base">
              Checkout
            </Link>
          )}
          <Link href="/shop" className="block text-center text-sm text-neutral-500 hover:text-neutral-900">
            Continue shopping
          </Link>
        </aside>
      </div>
    </div>
  );
}
