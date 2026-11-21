"use server";

import bcrypt from "bcryptjs";
import { and, eq, ne, sql } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { headers } from "next/headers";
import { redirect } from "next/navigation";
import { getDb } from "@/db";
import {
  admins,
  categories,
  contactMessages,
  newsletterSubscribers,
  orderItems,
  orders,
  ORDER_STATUSES,
  products,
  settings,
  type OrderStatus,
} from "@/db/schema";
import { createSession, destroySession, requireAdmin } from "@/lib/auth";
import { updateOrderStatus } from "@/lib/orders";
import { rateLimit, resetRateLimit } from "@/lib/rate-limit";
import { slugify } from "@/lib/slug";
import {
  categorySchema,
  fieldErrors,
  loginSchema,
  passwordChangeSchema,
  productSchema,
  settingsSchema,
} from "@/lib/validation";

export type AdminFormState = {
  ok?: boolean;
  message?: string;
  errors?: Record<string, string>;
  email?: string;
  /** Submitted values, echoed back so React's automatic form reset keeps what the admin typed. */
  values?: Record<string, string>;
};

const echo = (formData: FormData) =>
  Object.fromEntries([...formData.entries()].filter(([k, v]) => !k.startsWith("$") && typeof v === "string").map(([k, v]) => [k, String(v).slice(0, 5000)]));

const toId = (v: FormDataEntryValue | null) => {
  const n = Number.parseInt(String(v ?? ""), 10);
  return Number.isFinite(n) && n > 0 ? n : null;
};

// A real bcrypt hash of a random string: used so failed logins take the same time
// whether or not the email exists.
const DUMMY_HASH = "$2b$12$LwmARshZHj8bL6P8t759weakjzYXb3vrSI5w8jIdwpf7so.ee8cau";

/* ───────────────────────── Auth ───────────────────────── */

export async function login(_prev: AdminFormState, formData: FormData): Promise<AdminFormState> {
  const parsed = loginSchema.safeParse({
    email: String(formData.get("email") ?? "").trim().toLowerCase(),
    password: String(formData.get("password") ?? ""),
  });
  const email = String(formData.get("email") ?? "").slice(0, 160);
  if (!parsed.success) return { ok: false, errors: fieldErrors(parsed.error), email };

  const h = await headers();
  const ip = h.get("x-forwarded-for")?.split(",")[0]?.trim() || "unknown";
  const key = `login:${ip}:${parsed.data.email}`;
  if (!rateLimit(key, 5, 15 * 60_000)) {
    return { ok: false, message: "Too many failed attempts. Please wait 15 minutes and try again.", email };
  }

  const db = await getDb();
  const [admin] = await db.select().from(admins).where(eq(admins.email, parsed.data.email));
  const valid = await bcrypt.compare(parsed.data.password, admin?.passwordHash ?? DUMMY_HASH);
  if (!admin || !valid) return { ok: false, message: "Incorrect email or password.", email };

  resetRateLimit(key);
  await createSession({ sub: String(admin.id), email: admin.email, name: admin.name });
  redirect("/admin");
}

export async function logout() {
  await destroySession();
  redirect("/admin/login");
}

export async function changePassword(_prev: AdminFormState, formData: FormData): Promise<AdminFormState> {
  const session = await requireAdmin();
  const parsed = passwordChangeSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { ok: false, errors: fieldErrors(parsed.error) };
  const db = await getDb();
  const [admin] = await db.select().from(admins).where(eq(admins.id, Number(session.sub)));
  if (!admin || !(await bcrypt.compare(parsed.data.current, admin.passwordHash))) {
    return { ok: false, errors: { current: "Current password is incorrect" } };
  }
  await db.update(admins).set({ passwordHash: await bcrypt.hash(parsed.data.next, 12) }).where(eq(admins.id, admin.id));
  return { ok: true, message: "Password updated." };
}

/* ───────────────────────── Products ───────────────────────── */

function readProductForm(formData: FormData) {
  return productSchema.safeParse({
    name: String(formData.get("name") ?? ""),
    categoryId: String(formData.get("categoryId") ?? ""),
    description: String(formData.get("description") ?? ""),
    features: String(formData.get("features") ?? ""),
    price: String(formData.get("price") ?? ""),
    compareAt: String(formData.get("compareAt") ?? ""),
    imageUrl: String(formData.get("imageUrl") ?? "").trim(),
    icon: String(formData.get("icon") ?? "") || "👟",
    badge: String(formData.get("badge") ?? ""),
    sizes: formData.getAll("sizes").map(String),
    stock: String(formData.get("stock") ?? ""),
    isFeatured: formData.get("isFeatured") === "on",
    isActive: formData.get("isActive") === "on",
  });
}

async function uniqueSlug(name: string, ignoreId?: number) {
  const db = await getDb();
  const base = slugify(name) || "product";
  let slug = base;
  for (let i = 2; ; i++) {
    const [clash] = await db
      .select({ id: products.id })
      .from(products)
      .where(ignoreId ? and(eq(products.slug, slug), ne(products.id, ignoreId)) : eq(products.slug, slug));
    if (!clash) return slug;
    slug = `${base}-${i}`;
  }
}

export async function saveProduct(_prev: AdminFormState, formData: FormData): Promise<AdminFormState> {
  await requireAdmin();
  const id = toId(formData.get("id"));
  const parsed = readProductForm(formData);
  if (!parsed.success) return { ok: false, errors: fieldErrors(parsed.error), message: "Please fix the highlighted fields." };
  const p = parsed.data;
  const db = await getDb();

  const [category] = await db.select({ id: categories.id }).from(categories).where(eq(categories.id, p.categoryId));
  if (!category) return { ok: false, errors: { categoryId: "Choose a category" } };

  const values = {
    name: p.name,
    categoryId: p.categoryId,
    description: p.description,
    features: p.features,
    priceCents: p.price,
    compareAtCents: p.compareAt,
    imageUrl: p.imageUrl || null,
    icon: p.icon,
    badge: p.badge || null,
    sizes: [...p.sizes].sort((a, b) => Number(a) - Number(b)),
    stock: p.stock,
    isFeatured: p.isFeatured,
    isActive: p.isActive,
    updatedAt: new Date(),
  };

  if (id) {
    await db
      .update(products)
      .set({ ...values, slug: await uniqueSlug(p.name, id) })
      .where(eq(products.id, id));
  } else {
    await db.insert(products).values({ ...values, slug: await uniqueSlug(p.name) });
  }
  revalidatePath("/", "layout");
  redirect("/admin/products?saved=1");
}

export async function deleteProduct(formData: FormData) {
  await requireAdmin();
  const id = toId(formData.get("id"));
  if (!id) return;
  const db = await getDb();
  const [used] = await db.select({ id: orderItems.id }).from(orderItems).where(eq(orderItems.productId, id)).limit(1);
  if (used) {
    // Keep order history intact: hide the product instead of deleting it.
    await db.update(products).set({ isActive: false, updatedAt: new Date() }).where(eq(products.id, id));
  } else {
    await db.delete(products).where(eq(products.id, id));
  }
  revalidatePath("/", "layout");
  redirect(used ? "/admin/products?hidden=1" : "/admin/products?deleted=1");
}

export async function updateStock(formData: FormData) {
  await requireAdmin();
  const db = await getDb();
  for (const [key, value] of formData.entries()) {
    const m = /^stock_(\d+)$/.exec(key);
    if (!m) continue;
    const stock = Number.parseInt(String(value), 10);
    if (!Number.isFinite(stock) || stock < 0 || stock > 100000) continue;
    await db
      .update(products)
      .set({ stock, updatedAt: new Date() })
      .where(and(eq(products.id, Number(m[1])), ne(products.stock, stock)));
  }
  revalidatePath("/", "layout");
  redirect("/admin/inventory?saved=1");
}

/* ───────────────────────── Categories ───────────────────────── */

export async function saveCategory(_prev: AdminFormState, formData: FormData): Promise<AdminFormState> {
  await requireAdmin();
  const id = toId(formData.get("id"));
  const parsed = categorySchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { ok: false, errors: fieldErrors(parsed.error), values: echo(formData) };
  const slug = slugify(parsed.data.name);
  if (!slug) return { ok: false, errors: { name: "Name must contain letters or numbers" }, values: echo(formData) };
  const db = await getDb();
  const [clash] = await db
    .select({ id: categories.id })
    .from(categories)
    .where(id ? and(eq(categories.slug, slug), ne(categories.id, id)) : eq(categories.slug, slug));
  if (clash) return { ok: false, errors: { name: "A category with this name already exists" }, values: echo(formData) };

  const values = { ...parsed.data, imageUrl: parsed.data.imageUrl || null, slug };
  if (id) await db.update(categories).set(values).where(eq(categories.id, id));
  else await db.insert(categories).values(values);
  revalidatePath("/", "layout");
  return { ok: true, message: id ? "Category updated." : "Category added." };
}

export async function deleteCategory(formData: FormData) {
  await requireAdmin();
  const id = toId(formData.get("id"));
  if (!id) return;
  const db = await getDb();
  const [{ count }] = await db
    .select({ count: sql<number>`count(*)::int` })
    .from(products)
    .where(eq(products.categoryId, id));
  if (count > 0) redirect("/admin/categories?error=in-use");
  await db.delete(categories).where(eq(categories.id, id));
  revalidatePath("/", "layout");
  redirect("/admin/categories");
}

/* ───────────────────────── Orders ───────────────────────── */

export async function setOrderStatus(_prev: AdminFormState, formData: FormData): Promise<AdminFormState> {
  await requireAdmin();
  const id = toId(formData.get("id"));
  const status = String(formData.get("status") ?? "") as OrderStatus;
  if (!id || !ORDER_STATUSES.includes(status)) return { ok: false, message: "Invalid status." };
  const db = await getDb();
  const result = await updateOrderStatus(db, id, status);
  if (!result.ok) return { ok: false, message: result.error };
  revalidatePath("/admin", "layout");
  return { ok: true, message: status === "cancelled" ? "Order cancelled and items returned to stock." : "Status updated." };
}

export async function saveOrderNote(_prev: AdminFormState, formData: FormData): Promise<AdminFormState> {
  await requireAdmin();
  const id = toId(formData.get("id"));
  if (!id) return { ok: false, message: "Order not found." };
  const note = String(formData.get("adminNote") ?? "").slice(0, 2000);
  const db = await getDb();
  await db.update(orders).set({ adminNote: note, updatedAt: new Date() }).where(eq(orders.id, id));
  return { ok: true, message: "Note saved." };
}

/* ───────────────────────── Messages & subscribers ───────────────────────── */

export async function toggleMessageRead(formData: FormData) {
  await requireAdmin();
  const id = toId(formData.get("id"));
  if (!id) return;
  const db = await getDb();
  await db.update(contactMessages).set({ isRead: sql`not ${contactMessages.isRead}` }).where(eq(contactMessages.id, id));
  revalidatePath("/admin", "layout");
}

export async function deleteMessage(formData: FormData) {
  await requireAdmin();
  const id = toId(formData.get("id"));
  if (!id) return;
  const db = await getDb();
  await db.delete(contactMessages).where(eq(contactMessages.id, id));
  revalidatePath("/admin", "layout");
}

export async function deleteSubscriber(formData: FormData) {
  await requireAdmin();
  const id = toId(formData.get("id"));
  if (!id) return;
  const db = await getDb();
  await db.delete(newsletterSubscribers).where(eq(newsletterSubscribers.id, id));
  revalidatePath("/admin/subscribers");
}

/* ───────────────────────── Settings ───────────────────────── */

export async function saveSettings(_prev: AdminFormState, formData: FormData): Promise<AdminFormState> {
  await requireAdmin();
  const parsed = settingsSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) {
    return { ok: false, errors: fieldErrors(parsed.error), message: "Please fix the highlighted fields.", values: echo(formData) };
  }
  const { shipping, freeShippingThreshold, ...rest } = parsed.data;
  const entries: Record<string, string> = {
    ...Object.fromEntries(Object.entries(rest).map(([k, v]) => [k, String(v)])),
    shippingCents: String(shipping),
    freeShippingThresholdCents: String(freeShippingThreshold),
  };
  const db = await getDb();
  for (const [key, value] of Object.entries(entries)) {
    await db.insert(settings).values({ key, value }).onConflictDoUpdate({ target: settings.key, set: { value } });
  }
  revalidatePath("/", "layout");
  return { ok: true, message: "Settings saved." };
}
