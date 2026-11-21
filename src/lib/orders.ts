import { randomInt } from "node:crypto";
import { eq, inArray, sql } from "drizzle-orm";
import type { DB } from "@/db/client";
import { customers, orderItems, orders, products, type OrderStatus, type Product } from "@/db/schema";
import type { CartItem } from "./cart";
import type { CheckoutInput } from "./validation";
import { shippingFor, type StoreSettings } from "./settings-defaults";

export type CartLine = {
  key: string;
  product: Pick<Product, "id" | "name" | "slug" | "priceCents" | "imageUrl" | "icon" | "stock" | "sizes">;
  size: string;
  quantity: number;
  unitCents: number;
  lineCents: number;
  problem: string | null;
};

export type ResolvedCart = {
  lines: CartLine[];
  count: number;
  subtotalCents: number;
  shippingCents: number;
  totalCents: number;
  hasProblems: boolean;
};

/** Turn cookie items into priced lines using CURRENT database prices and stock. */
export async function resolveCart(
  db: DB,
  items: CartItem[],
  settings: Pick<StoreSettings, "shippingCents" | "freeShippingThresholdCents">,
): Promise<ResolvedCart> {
  const ids = [...new Set(items.map((i) => i.p))];
  const rows = ids.length ? await db.select().from(products).where(inArray(products.id, ids)) : [];
  const byId = new Map(rows.map((p) => [p.id, p]));

  const qtyByProduct = new Map<number, number>();
  for (const i of items) qtyByProduct.set(i.p, (qtyByProduct.get(i.p) ?? 0) + i.q);

  const lines: CartLine[] = [];
  for (const item of items) {
    const p = byId.get(item.p);
    if (!p || !p.isActive) continue; // silently drop deleted / hidden products
    let problem: string | null = null;
    if (!p.sizes.includes(item.s)) problem = `Size ${item.s} is no longer available`;
    else if (p.stock <= 0) problem = "Out of stock";
    else if ((qtyByProduct.get(p.id) ?? 0) > p.stock) problem = `Only ${p.stock} left in stock`;
    lines.push({
      key: `${p.id}:${item.s}`,
      product: p,
      size: item.s,
      quantity: item.q,
      unitCents: p.priceCents,
      lineCents: p.priceCents * item.q,
      problem,
    });
  }
  const subtotalCents = lines.reduce((s, l) => s + l.lineCents, 0);
  const shippingCents = shippingFor(subtotalCents, settings);
  return {
    lines,
    count: lines.reduce((n, l) => n + l.quantity, 0),
    subtotalCents,
    shippingCents,
    totalCents: subtotalCents + shippingCents,
    hasProblems: lines.some((l) => l.problem),
  };
}

const NUMBER_ALPHABET = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // no 0/O/1/I to avoid confusion
export function generateOrderNumber(): string {
  let s = "SBP-";
  for (let i = 0; i < 8; i++) s += NUMBER_ALPHABET[randomInt(NUMBER_ALPHABET.length)];
  return s;
}

export type PlaceOrderResult = { ok: true; number: string; orderId: number } | { ok: false; error: string };

/**
 * Creates an order inside one transaction:
 * locks the product rows, re-checks price/size/stock, decrements stock,
 * upserts the customer (by phone) and writes the order + items.
 */
export async function placeOrder(
  db: DB,
  items: CartItem[],
  input: CheckoutInput,
  settings: Pick<StoreSettings, "shippingCents" | "freeShippingThresholdCents">,
): Promise<PlaceOrderResult> {
  if (items.length === 0) return { ok: false, error: "Your cart is empty." };

  try {
    return await db.transaction(async (tx) => {
      const ids = [...new Set(items.map((i) => i.p))].sort((a, b) => a - b);
      const rows = await tx.select().from(products).where(inArray(products.id, ids)).for("update");
      const byId = new Map(rows.map((p) => [p.id, p]));

      const qtyByProduct = new Map<number, number>();
      for (const i of items) qtyByProduct.set(i.p, (qtyByProduct.get(i.p) ?? 0) + i.q);

      for (const item of items) {
        const p = byId.get(item.p);
        if (!p || !p.isActive) throw new CheckoutError("A product in your cart is no longer available.");
        if (!p.sizes.includes(item.s)) throw new CheckoutError(`${p.name}: size ${item.s} is no longer available.`);
      }
      for (const [id, qty] of qtyByProduct) {
        const p = byId.get(id)!;
        if (p.stock < qty) {
          throw new CheckoutError(
            p.stock === 0 ? `${p.name} is out of stock.` : `Only ${p.stock} × ${p.name} left in stock.`,
          );
        }
      }

      const subtotalCents = items.reduce((s, i) => s + byId.get(i.p)!.priceCents * i.q, 0);
      const shippingCents = shippingFor(subtotalCents, settings);

      for (const [id, qty] of qtyByProduct) {
        await tx
          .update(products)
          .set({ stock: sql`${products.stock} - ${qty}`, updatedAt: new Date() })
          .where(eq(products.id, id));
      }

      const email = input.email || null;
      const [customer] = await tx
        .insert(customers)
        .values({ name: input.name, phone: normalizePhone(input.phone), email, city: input.city, address: input.address })
        .onConflictDoUpdate({
          target: customers.phone,
          set: { name: input.name, email: sql`coalesce(${email}, ${customers.email})`, city: input.city, address: input.address },
        })
        .returning({ id: customers.id });

      const number = generateOrderNumber();
      const [order] = await tx
        .insert(orders)
        .values({
          number,
          customerId: customer.id,
          customerName: input.name,
          phone: normalizePhone(input.phone),
          email,
          city: input.city,
          address: input.address,
          notes: input.notes ?? "",
          subtotalCents,
          shippingCents,
          totalCents: subtotalCents + shippingCents,
        })
        .returning({ id: orders.id });

      await tx.insert(orderItems).values(
        items.map((i) => {
          const p = byId.get(i.p)!;
          return { orderId: order.id, productId: p.id, productName: p.name, size: i.s, unitCents: p.priceCents, quantity: i.q };
        }),
      );

      return { ok: true as const, number, orderId: order.id };
    });
  } catch (err) {
    if (err instanceof CheckoutError) return { ok: false, error: err.message };
    throw err;
  }
}

class CheckoutError extends Error {}

export function normalizePhone(phone: string) {
  return phone.replace(/[\s-]/g, "");
}

export type StatusResult = { ok: true } | { ok: false; error: string };

/** Changes an order's status. Cancelling returns the items to stock; cancelled orders are final. */
export async function updateOrderStatus(db: DB, orderId: number, next: OrderStatus): Promise<StatusResult> {
  return db.transaction(async (tx) => {
    const [order] = await tx.select().from(orders).where(eq(orders.id, orderId)).for("update");
    if (!order) return { ok: false, error: "Order not found." };
    if (order.status === next) return { ok: true };
    if (order.status === "cancelled") return { ok: false, error: "Cancelled orders cannot be reopened." };

    if (next === "cancelled") {
      const items = await tx.select().from(orderItems).where(eq(orderItems.orderId, orderId));
      for (const item of items) {
        if (item.productId == null) continue;
        await tx
          .update(products)
          .set({ stock: sql`${products.stock} + ${item.quantity}`, updatedAt: new Date() })
          .where(eq(products.id, item.productId));
      }
    }
    await tx.update(orders).set({ status: next, updatedAt: new Date() }).where(eq(orders.id, orderId));
    return { ok: true };
  });
}
