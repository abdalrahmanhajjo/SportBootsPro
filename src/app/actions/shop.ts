"use server";

import { eq } from "drizzle-orm";
import { cookies, headers } from "next/headers";
import { redirect } from "next/navigation";
import { getDb } from "@/db";
import { contactMessages, newsletterSubscribers, products } from "@/db/schema";
import { addItem, CART_COOKIE, parseCart, removeItem, serializeCart, setItemQty, type CartItem } from "@/lib/cart";
import { placeOrder } from "@/lib/orders";
import { rateLimit } from "@/lib/rate-limit";
import { getSettings } from "@/lib/settings";
import { checkoutSchema, contactSchema, fieldErrors, newsletterSchema } from "@/lib/validation";

export type FormState = {
  ok?: boolean;
  message?: string;
  errors?: Record<string, string>;
  /** Submitted values, echoed back so React's automatic form reset doesn't wipe what the user typed. */
  values?: Record<string, string>;
};

function echo(formData: FormData, keys: string[]): Record<string, string> {
  return Object.fromEntries(keys.map((k) => [k, String(formData.get(k) ?? "").slice(0, 5000)]));
}
const CHECKOUT_FIELDS = ["name", "phone", "email", "city", "address", "notes"];
const CONTACT_FIELDS = ["name", "email", "subject", "message"];

async function readCart() {
  return parseCart((await cookies()).get(CART_COOKIE)?.value);
}

async function writeCart(items: CartItem[]) {
  const jar = await cookies();
  if (items.length === 0) {
    jar.delete(CART_COOKIE);
    return;
  }
  jar.set(CART_COOKIE, serializeCart(items), {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: 60 * 60 * 24 * 30,
  });
}

async function clientIp() {
  const h = await headers();
  return h.get("x-forwarded-for")?.split(",")[0]?.trim() || h.get("x-real-ip") || "unknown";
}

const toInt = (v: FormDataEntryValue | null) => Number.parseInt(String(v ?? ""), 10);

export async function addToCart(_prev: FormState, formData: FormData): Promise<FormState> {
  const productId = toInt(formData.get("productId"));
  const size = String(formData.get("size") ?? "");
  const qty = Math.max(1, Math.min(10, toInt(formData.get("quantity")) || 1));
  if (!size) return { ok: false, message: "Please choose a size." };

  const db = await getDb();
  const [product] = await db.select().from(products).where(eq(products.id, productId));
  if (!product || !product.isActive) return { ok: false, message: "This product is no longer available." };
  if (!product.sizes.includes(size)) return { ok: false, message: "That size is not available." };

  const items = await readCart();
  const inCart = items.filter((i) => i.p === productId).reduce((n, i) => n + i.q, 0);
  if (inCart + qty > product.stock) {
    return {
      ok: false,
      message: product.stock === 0 ? "Sorry, this product is out of stock." : `Only ${product.stock} left in stock.`,
    };
  }
  await writeCart(addItem(items, productId, size, qty));
  return { ok: true, message: `Added ${product.name} (size ${size}) to your cart.` };
}

export async function updateCartLine(formData: FormData) {
  const productId = toInt(formData.get("productId"));
  const size = String(formData.get("size") ?? "");
  const qty = toInt(formData.get("quantity"));
  if (!Number.isFinite(productId) || !size || !Number.isFinite(qty)) return;
  await writeCart(setItemQty(await readCart(), productId, size, qty));
}

export async function removeCartLine(formData: FormData) {
  const productId = toInt(formData.get("productId"));
  const size = String(formData.get("size") ?? "");
  await writeCart(removeItem(await readCart(), productId, size));
}

export async function checkout(_prev: FormState, formData: FormData): Promise<FormState> {
  if (!rateLimit(`checkout:${await clientIp()}`, 10, 10 * 60_000)) {
    return { ok: false, message: "Too many attempts. Please wait a few minutes and try again.", values: echo(formData, CHECKOUT_FIELDS) };
  }
  const parsed = checkoutSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) {
    return { ok: false, errors: fieldErrors(parsed.error), message: "Please fix the highlighted fields.", values: echo(formData, CHECKOUT_FIELDS) };
  }

  const [db, items, settings] = await Promise.all([getDb(), readCart(), getSettings()]);
  const result = await placeOrder(db, items, parsed.data, settings);
  if (!result.ok) return { ok: false, message: result.error, values: echo(formData, CHECKOUT_FIELDS) };

  await writeCart([]);
  redirect(`/order/${result.number}`);
}

export async function sendContactMessage(_prev: FormState, formData: FormData): Promise<FormState> {
  if (String(formData.get("website") ?? "") !== "") return { ok: true, message: "Thanks! We'll get back to you soon." }; // honeypot
  if (!rateLimit(`contact:${await clientIp()}`, 5, 60 * 60_000)) {
    return { ok: false, message: "Too many messages. Please try again later or reach us on WhatsApp.", values: echo(formData, CONTACT_FIELDS) };
  }
  const parsed = contactSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { ok: false, errors: fieldErrors(parsed.error), values: echo(formData, CONTACT_FIELDS) };
  const db = await getDb();
  await db.insert(contactMessages).values(parsed.data);
  return { ok: true, message: "Thanks! Your message was sent — we usually reply within one business day." };
}

export async function subscribeNewsletter(_prev: FormState, formData: FormData): Promise<FormState> {
  if (!rateLimit(`newsletter:${await clientIp()}`, 5, 60 * 60_000)) {
    return { ok: false, message: "Too many attempts. Please try again later." };
  }
  const parsed = newsletterSchema.safeParse({ email: String(formData.get("email") ?? "").trim().toLowerCase() });
  if (!parsed.success) return { ok: false, message: fieldErrors(parsed.error).email };
  const db = await getDb();
  await db.insert(newsletterSubscribers).values({ email: parsed.data.email }).onConflictDoNothing();
  return { ok: true, message: "You're in! Watch your inbox for new drops and offers." };
}
