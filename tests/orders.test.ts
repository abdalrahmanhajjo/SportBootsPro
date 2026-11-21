import { beforeEach, describe, expect, it } from "vitest";
import { eq } from "drizzle-orm";
import { createDb, type DB } from "@/db/client";
import { customers, orderItems, orders, products } from "@/db/schema";
import { placeOrder, resolveCart, updateOrderStatus } from "@/lib/orders";
import type { CheckoutInput } from "@/lib/validation";

const settings = { shippingCents: 500, freeShippingThresholdCents: 15000 };
const buyer: CheckoutInput = {
  name: "Rami Haddad",
  phone: "+961 70 123 456",
  email: "rami@example.com",
  city: "Tripoli",
  address: "Mina street, building 4",
  notes: "",
};

let db: DB;
let product: typeof products.$inferSelect;

beforeEach(async () => {
  db = await createDb({ pgliteDir: "memory://" }); // fresh, migrated + seeded database
  [product] = await db.select().from(products).where(eq(products.slug, "metro-lift-8")); // stock 4, $129.99
});

describe("resolveCart", () => {
  it("prices lines from the database, not from the cookie", async () => {
    const cart = await resolveCart(db, [{ p: product.id, s: "42", q: 1 }], settings);
    expect(cart.subtotalCents).toBe(12999);
    expect(cart.shippingCents).toBe(500);
    expect(cart.totalCents).toBe(13499);
    expect(cart.hasProblems).toBe(false);
  });

  it("gives free shipping above the threshold", async () => {
    const cart = await resolveCart(db, [{ p: product.id, s: "42", q: 2 }], settings);
    expect(cart.shippingCents).toBe(0);
  });

  it("flags unavailable sizes and too-high quantities", async () => {
    const bad = await resolveCart(db, [{ p: product.id, s: "99", q: 1 }], settings);
    expect(bad.lines[0].problem).toMatch(/no longer available/);
    const tooMany = await resolveCart(db, [{ p: product.id, s: "42", q: 5 }], settings);
    expect(tooMany.lines[0].problem).toMatch(/Only 4 left/);
  });

  it("drops unknown products", async () => {
    const cart = await resolveCart(db, [{ p: 999999, s: "42", q: 1 }], settings);
    expect(cart.lines).toHaveLength(0);
  });
});

describe("placeOrder", () => {
  it("creates the order, items and customer and decrements stock", async () => {
    const res = await placeOrder(db, [{ p: product.id, s: "42", q: 2 }, { p: product.id, s: "43", q: 1 }], buyer, settings);
    expect(res.ok).toBe(true);
    if (!res.ok) return;
    expect(res.number).toMatch(/^SBP-[A-Z2-9]{8}$/);

    const [order] = await db.select().from(orders).where(eq(orders.id, res.orderId));
    expect(order.totalCents).toBe(12999 * 3); // free shipping
    expect(order.status).toBe("pending");
    expect(order.phone).toBe("+96170123456");

    const items = await db.select().from(orderItems).where(eq(orderItems.orderId, res.orderId));
    expect(items).toHaveLength(2);

    const [after] = await db.select().from(products).where(eq(products.id, product.id));
    expect(after.stock).toBe(1);

    const allCustomers = await db.select().from(customers);
    expect(allCustomers).toHaveLength(1);
  });

  it("refuses to oversell and leaves stock untouched", async () => {
    const res = await placeOrder(db, [{ p: product.id, s: "42", q: 3 }, { p: product.id, s: "40", q: 2 }], buyer, settings);
    expect(res).toEqual({ ok: false, error: "Only 4 × Metro Lift 8 left in stock." });
    const [after] = await db.select().from(products).where(eq(products.id, product.id));
    expect(after.stock).toBe(4);
    expect(await db.select().from(orders)).toHaveLength(0);
  });

  it("rejects an empty cart, hidden products and invalid sizes", async () => {
    expect(await placeOrder(db, [], buyer, settings)).toEqual({ ok: false, error: "Your cart is empty." });
    expect((await placeOrder(db, [{ p: product.id, s: "99", q: 1 }], buyer, settings)).ok).toBe(false);
    await db.update(products).set({ isActive: false }).where(eq(products.id, product.id));
    expect((await placeOrder(db, [{ p: product.id, s: "42", q: 1 }], buyer, settings)).ok).toBe(false);
  });

  it("reuses the same customer for the same phone number", async () => {
    await placeOrder(db, [{ p: product.id, s: "42", q: 1 }], buyer, settings);
    await placeOrder(db, [{ p: product.id, s: "42", q: 1 }], { ...buyer, phone: "+96170123456", email: "" }, settings);
    const all = await db.select().from(customers);
    expect(all).toHaveLength(1);
    expect(all[0].email).toBe("rami@example.com"); // empty email does not wipe the saved one
  });
});

describe("updateOrderStatus", () => {
  it("returns stock when an order is cancelled and blocks reopening", async () => {
    const res = await placeOrder(db, [{ p: product.id, s: "42", q: 3 }], buyer, settings);
    if (!res.ok) throw new Error(res.error);

    expect(await updateOrderStatus(db, res.orderId, "processing")).toEqual({ ok: true });
    expect(await updateOrderStatus(db, res.orderId, "cancelled")).toEqual({ ok: true });

    const [after] = await db.select().from(products).where(eq(products.id, product.id));
    expect(after.stock).toBe(4);

    expect(await updateOrderStatus(db, res.orderId, "pending")).toEqual({
      ok: false,
      error: "Cancelled orders cannot be reopened.",
    });
    expect(await updateOrderStatus(db, 999999, "shipped")).toEqual({ ok: false, error: "Order not found." });
  });
});
