"use client";

import { useState } from "react";
import { saveProduct, type AdminFormState } from "@/app/actions/admin";
import { ProductImage } from "@/components/ProductImage";
import { Field, FormMessage, SubmitButton, useActionNoReset } from "@/components/ui";
import { SIZE_OPTIONS } from "@/lib/validation";

export type ProductFormValues = {
  id?: number;
  name: string;
  categoryId: number | "";
  description: string;
  features: string;
  price: string;
  compareAt: string;
  imageUrl: string;
  icon: string;
  badge: string;
  sizes: string[];
  stock: number;
  isFeatured: boolean;
  isActive: boolean;
};

export function ProductForm({
  initial,
  categories,
}: {
  initial: ProductFormValues;
  categories: { id: number; name: string; icon: string }[];
}) {
  const [state, onSubmit, pending] = useActionNoReset<AdminFormState>(saveProduct, {});
  const [values, setValues] = useState(initial);
  const e = state.errors ?? {};
  const set = <K extends keyof ProductFormValues>(k: K, v: ProductFormValues[K]) => setValues((s) => ({ ...s, [k]: v }));

  return (
    <form onSubmit={onSubmit} className="grid gap-6 xl:grid-cols-3">
      {values.id && <input type="hidden" name="id" value={values.id} />}
      <div className="space-y-6 xl:col-span-2">
        <FormMessage state={state} />
        <section className="card space-y-5 p-6">
          <h2 className="font-bold">Details</h2>
          <Field label="Product name" name="name" error={e.name}>
            <input id="name" name="name" value={values.name} onChange={(ev) => set("name", ev.target.value)} required className="input" />
          </Field>
          <Field label="Description" name="description" error={e.description}>
            <textarea id="description" name="description" rows={4} value={values.description} onChange={(ev) => set("description", ev.target.value)} className="input" />
          </Field>
          <Field label="Key features" name="features" error={e.features} hint="Separate features with commas, e.g. Carbon plate, Knit upper">
            <input id="features" name="features" value={values.features} onChange={(ev) => set("features", ev.target.value)} className="input" />
          </Field>
        </section>

        <section className="card space-y-5 p-6">
          <h2 className="font-bold">Pricing & stock</h2>
          <div className="grid gap-5 sm:grid-cols-3">
            <Field label="Price ($)" name="price" error={e.price}>
              <input id="price" name="price" inputMode="decimal" value={values.price} onChange={(ev) => set("price", ev.target.value)} placeholder="129.99" required className="input" />
            </Field>
            <Field label="Compare-at price ($)" name="compareAt" error={e.compareAt} hint="Optional — shows a sale price">
              <input id="compareAt" name="compareAt" inputMode="decimal" value={values.compareAt} onChange={(ev) => set("compareAt", ev.target.value)} className="input" />
            </Field>
            <Field label="Stock" name="stock" error={e.stock}>
              <input id="stock" name="stock" type="number" min={0} value={values.stock} onChange={(ev) => set("stock", Number(ev.target.value))} required className="input" />
            </Field>
          </div>
          <fieldset>
            <legend className="label">Available sizes (EU)</legend>
            <div className="flex flex-wrap gap-2">
              {SIZE_OPTIONS.map((s) => {
                const on = values.sizes.includes(s);
                return (
                  <label
                    key={s}
                    className={`flex h-10 w-12 cursor-pointer items-center justify-center rounded-lg border text-sm font-semibold has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-brand-600 ${on ? "border-neutral-900 bg-neutral-900 text-white" : "border-neutral-300 hover:border-neutral-900"}`}
                  >
                    <input
                      type="checkbox"
                      name="sizes"
                      value={s}
                      checked={on}
                      onChange={() => set("sizes", on ? values.sizes.filter((x) => x !== s) : [...values.sizes, s])}
                      className="sr-only"
                    />
                    {s}
                  </label>
                );
              })}
            </div>
            {e.sizes && <p className="field-error">{e.sizes}</p>}
            {values.sizes.length === 0 && <p className="mt-1 text-xs text-amber-700">No sizes selected — customers won&apos;t be able to buy this product.</p>}
          </fieldset>
        </section>
      </div>

      <div className="space-y-6">
        <section className="card space-y-5 p-6">
          <h2 className="font-bold">Organisation</h2>
          <Field label="Category" name="categoryId" error={e.categoryId}>
            <select
              id="categoryId"
              name="categoryId"
              value={values.categoryId}
              onChange={(ev) => {
                const cat = categories.find((c) => c.id === Number(ev.target.value));
                setValues((s) => ({ ...s, categoryId: Number(ev.target.value) || "", icon: cat?.icon ?? s.icon }));
              }}
              required
              className="input"
            >
              <option value="">Choose…</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.icon} {c.name}
                </option>
              ))}
            </select>
          </Field>
          <Field label="Badge" name="badge" error={e.badge} hint="Optional, e.g. New, Sale, Limited">
            <input id="badge" name="badge" value={values.badge} onChange={(ev) => set("badge", ev.target.value)} className="input" />
          </Field>
          <label className="flex items-center gap-3 text-sm">
            <input type="checkbox" name="isActive" checked={values.isActive} onChange={(ev) => set("isActive", ev.target.checked)} className="h-4 w-4 accent-brand-600" />
            Visible in the store
          </label>
          <label className="flex items-center gap-3 text-sm">
            <input type="checkbox" name="isFeatured" checked={values.isFeatured} onChange={(ev) => set("isFeatured", ev.target.checked)} className="h-4 w-4 accent-brand-600" />
            Feature on the home page
          </label>
        </section>

        <section className="card space-y-5 p-6">
          <h2 className="font-bold">Image</h2>
          <div className="aspect-square overflow-hidden rounded-xl">
            <ProductImage name={values.name || "Preview"} imageUrl={/^https?:\/\//.test(values.imageUrl) ? values.imageUrl : null} icon={values.icon || "👟"} seed={values.id ?? 0} size="md" />
          </div>
          <Field label="Image URL" name="imageUrl" error={e.imageUrl} hint="Paste a link to a product photo (https://…). Leave empty to use the icon.">
            <input id="imageUrl" name="imageUrl" type="url" value={values.imageUrl} onChange={(ev) => set("imageUrl", ev.target.value)} className="input" />
          </Field>
          <Field label="Icon (emoji)" name="icon" error={e.icon}>
            <input id="icon" name="icon" value={values.icon} onChange={(ev) => set("icon", ev.target.value)} maxLength={8} className="input w-24 text-center text-xl" />
          </Field>
        </section>

        <SubmitButton className="btn-primary w-full py-3" pendingText="Saving…" pending={pending}>
          {values.id ? "Save changes" : "Create product"}
        </SubmitButton>
      </div>
    </form>
  );
}
