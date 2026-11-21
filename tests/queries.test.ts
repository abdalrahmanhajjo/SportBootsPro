import { beforeAll, describe, expect, it } from "vitest";
import { eq } from "drizzle-orm";
import { getDb } from "@/db";
import { products } from "@/db/schema";
import { getDashboard, getReport, listCategoriesWithCounts, listCustomers, listOrders, listProducts } from "@/lib/admin-queries";
import { getCategories, searchProducts } from "@/lib/catalog";
import { placeOrder, updateOrderStatus } from "@/lib/orders";

const settings = { shippingCents: 500, freeShippingThresholdCents: 15000 };
const buyer = { name: "Nour K", phone: "+96171000000", email: "", city: "Beirut", address: "Hamra street 10", notes: "" };

let metconId: number;
let cancelledOrderId: number;

beforeAll(async () => {
  const db = await getDb();
  [{ id: metconId }] = await db.select({ id: products.id }).from(products).where(eq(products.slug, "metro-lift-8"));
  const a = await placeOrder(db, [{ p: metconId, s: "42", q: 2 }], buyer, settings);
  const b = await placeOrder(db, [{ p: metconId, s: "41", q: 1 }], buyer, settings);
  if (!a.ok || !b.ok) throw new Error("order failed");
  cancelledOrderId = b.orderId;
  await updateOrderStatus(db, cancelledOrderId, "cancelled");
});

describe("storefront queries", () => {
  it("counts products per category correctly", async () => {
    const cats = await getCategories();
    const counts = Object.fromEntries(cats.map((c) => [c.name, c.productCount]));
    expect(counts).toMatchObject({ Football: 4, Running: 4, Basketball: 3, Tennis: 2, Training: 2, "Trail Running": 2 });
  });

  it("searches, filters, sorts and paginates", async () => {
    const running = await searchProducts({ category: "running", sort: "price-asc" });
    expect(running.total).toBe(4);
    const prices = running.rows.map((r) => r.priceCents);
    expect(prices).toEqual([...prices].sort((a, b) => a - b));

    expect((await searchProducts({ q: "metro lift" })).rows.map((r) => r.slug)).toEqual(["metro-lift-8"]);
    expect((await searchProducts({ min: "200", max: "250" })).rows.every((r) => r.priceCents >= 20000 && r.priceCents <= 25000)).toBe(true);
    expect((await searchProducts({ q: "%_" })).total).toBe(0); // LIKE wildcards are escaped
    const page2 = await searchProducts({ page: "2" });
    expect(page2.page).toBe(2);
    expect(page2.rows.length).toBe(17 - 12);
  });
});

describe("admin queries", () => {
  it("counts products per category", async () => {
    const rows = await listCategoriesWithCounts();
    expect(rows.find((r) => r.category.name === "Football")?.productCount).toBe(4);
  });

  it("reports units sold per product, excluding cancelled orders", async () => {
    const rows = await listProducts({});
    expect(rows.find((r) => r.product.id === metconId)?.sold).toBe(2);
    expect(rows.filter((r) => r.product.id !== metconId).every((r) => r.sold === 0)).toBe(true);
  });

  it("lists orders with item counts and status totals", async () => {
    const data = await listOrders({});
    expect(data.total).toBe(2);
    expect(data.rows.map((r) => r.itemCount).sort()).toEqual([1, 2]);
    expect(data.statusCounts).toMatchObject({ pending: 1, cancelled: 1 });
    expect((await listOrders({ status: "cancelled" })).total).toBe(1);
    expect((await listOrders({ q: "Nour" })).total).toBe(2);
  });

  it("aggregates customers without counting cancelled spend", async () => {
    const { rows } = await listCustomers({});
    expect(rows).toHaveLength(1);
    expect(rows[0].orderCount).toBe(2);
    expect(rows[0].spent).toBe(12999 * 2 + 0); // free delivery over $150
  });

  it("builds the dashboard and reports", async () => {
    const d = await getDashboard(10);
    expect(d.totals).toEqual({ revenue: 25998, orders: 1 });
    expect(d.counts).toEqual({ customers: 1, products: 17, pending: 1 });
    expect(d.sales).toHaveLength(14);
    expect(d.lowStock.some((p) => p.name === "Metro Lift 8")).toBe(true);

    const r = await getReport(30);
    expect(r.summary).toEqual({ revenue: 25998, orders: 1, customers: 1 });
    expect(r.sales).toHaveLength(30);
    expect(r.topProducts[0]).toEqual({ name: "Metro Lift 8", units: 2, revenue: 25998 });
    expect(r.byCategory[0]).toEqual({ name: "Training", revenue: 25998 });
  });
});
