import { z } from "zod";

// The cart lives in an httpOnly cookie as a compact list of {product, size, quantity}.
// Prices are NEVER stored in the cookie — they are always read fresh from the database.

export const CART_COOKIE = "sbp_cart";
export const MAX_QTY_PER_LINE = 10;
export const MAX_LINES = 30;

const cartItemSchema = z.object({
  p: z.number().int().positive(),
  s: z.string().min(1).max(10),
  q: z.number().int().min(1).max(MAX_QTY_PER_LINE),
});
export type CartItem = z.infer<typeof cartItemSchema>;

export function parseCart(raw: string | undefined | null): CartItem[] {
  if (!raw) return [];
  try {
    const parsed = z.array(cartItemSchema).max(MAX_LINES).safeParse(JSON.parse(raw));
    return parsed.success ? parsed.data : [];
  } catch {
    return [];
  }
}

export const serializeCart = (items: CartItem[]) => JSON.stringify(items);

export function addItem(items: CartItem[], productId: number, size: string, qty: number): CartItem[] {
  const existing = items.find((i) => i.p === productId && i.s === size);
  if (existing) {
    return items.map((i) => (i === existing ? { ...i, q: Math.min(MAX_QTY_PER_LINE, i.q + qty) } : i));
  }
  if (items.length >= MAX_LINES) return items;
  return [...items, { p: productId, s: size, q: Math.min(MAX_QTY_PER_LINE, Math.max(1, qty)) }];
}

export function setItemQty(items: CartItem[], productId: number, size: string, qty: number): CartItem[] {
  if (qty <= 0) return removeItem(items, productId, size);
  return items.map((i) => (i.p === productId && i.s === size ? { ...i, q: Math.min(MAX_QTY_PER_LINE, qty) } : i));
}

export function removeItem(items: CartItem[], productId: number, size: string): CartItem[] {
  return items.filter((i) => !(i.p === productId && i.s === size));
}

export const cartCount = (items: CartItem[]) => items.reduce((n, i) => n + i.q, 0);
