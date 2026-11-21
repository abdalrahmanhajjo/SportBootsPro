"use client";

import Link from "next/link";
import { useActionState, useState } from "react";
import { Minus, Plus, ShoppingBag } from "lucide-react";
import { addToCart, type FormState } from "@/app/actions/shop";
import { FormMessage, SubmitButton } from "@/components/ui";

export function AddToCartForm({
  productId,
  allSizes,
  availableSizes,
  stock,
}: {
  productId: number;
  allSizes: string[];
  availableSizes: string[];
  stock: number;
}) {
  const [state, action] = useActionState<FormState, FormData>(addToCart, {});
  const [size, setSize] = useState<string>("");
  const [qty, setQty] = useState(1);
  const maxQty = Math.max(1, Math.min(10, stock));
  const soldOut = stock <= 0 || availableSizes.length === 0;

  return (
    <form action={action} className="space-y-6">
      <input type="hidden" name="productId" value={productId} />
      <fieldset>
        <div className="mb-3 flex items-center justify-between">
          <legend className="font-semibold">Select size (EU)</legend>
          <Link href="/size-guide" className="text-sm text-neutral-500 underline hover:text-neutral-900">
            Size guide
          </Link>
        </div>
        <div className="grid grid-cols-4 gap-2 sm:grid-cols-7">
          {allSizes.map((s) => {
            const available = availableSizes.includes(s) && !soldOut;
            return (
              <label
                key={s}
                className={`flex h-12 items-center justify-center rounded-xl border text-sm font-semibold transition ${
                  !available
                    ? "cursor-not-allowed border-neutral-200 bg-neutral-50 text-neutral-300 line-through"
                    : size === s
                      ? "cursor-pointer border-neutral-900 bg-neutral-900 text-white"
                      : "cursor-pointer border-neutral-300 hover:border-neutral-900"
                } has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-brand-600`}
              >
                <input
                  type="radio"
                  name="size"
                  value={s}
                  disabled={!available}
                  checked={size === s}
                  onChange={() => setSize(s)}
                  className="sr-only"
                  required
                />
                {s}
              </label>
            );
          })}
        </div>
      </fieldset>

      <div className="flex flex-wrap items-center gap-3">
        <div className="flex items-center rounded-full border border-neutral-300">
          <button
            type="button"
            onClick={() => setQty((q) => Math.max(1, q - 1))}
            className="flex h-12 w-12 items-center justify-center rounded-full hover:bg-neutral-100 disabled:opacity-40"
            disabled={qty <= 1 || soldOut}
            aria-label="Decrease quantity"
          >
            <Minus className="h-4 w-4" />
          </button>
          <input
            name="quantity"
            value={qty}
            readOnly
            className="w-10 border-0 bg-transparent text-center font-semibold focus:outline-none"
            aria-label="Quantity"
          />
          <button
            type="button"
            onClick={() => setQty((q) => Math.min(maxQty, q + 1))}
            className="flex h-12 w-12 items-center justify-center rounded-full hover:bg-neutral-100 disabled:opacity-40"
            disabled={qty >= maxQty || soldOut}
            aria-label="Increase quantity"
          >
            <Plus className="h-4 w-4" />
          </button>
        </div>
        <SubmitButton className="btn-primary h-12 flex-1 px-8 text-base" pendingText="Adding…" disabled={soldOut || !size}>
          <ShoppingBag className="h-5 w-5" /> {soldOut ? "Sold out" : size ? "Add to cart" : "Choose a size"}
        </SubmitButton>
      </div>

      <FormMessage state={state} />
      {state.ok && (
        <div className="flex gap-3">
          <Link href="/cart" className="btn-dark flex-1">
            View cart
          </Link>
          <Link href="/checkout" className="btn-outline flex-1">
            Checkout
          </Link>
        </div>
      )}
    </form>
  );
}
