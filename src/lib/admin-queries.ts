import "server-only";
import { and, asc, desc, eq, gte, ilike, lte, ne, or, sql, type SQL } from "drizzle-orm";
import { getDb } from "@/db";
import { col } from "./sql";
import {
  categories,
  contactMessages,
  customers,
  newsletterSubscribers,
  orderItems,
  orders,
  ORDER_STATUSES,
  products,
  type OrderStatus,
} from "@/db/schema";

export const ADMIN_PAGE_SIZE = 20;
const notCancelled = ne(orders.status, "cancelled");
const escapeLike = (q: string) => `%${q.replace(/[%_\\]/g, (m) => `\\${m}`)}%`;
const pageOf = (p?: string) => Math.max(1, Math.min(10000, Number.parseInt(p ?? "1", 10) || 1));

export function daysAgo(days: number) {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  d.setDate(d.getDate() - (days - 1));
  return d;
}

/** Fill gaps so charts show every day, even days without sales. */
function fillDays(rows: { day: string; revenue: number; orders: number }[], days: number) {
  const byDay = new Map(rows.map((r) => [r.day, r]));
  const out: { day: string; revenue: number; orders: number }[] = [];
  const start = daysAgo(days);
  for (let i = 0; i < days; i++) {
    const d = new Date(start);
    d.setDate(start.getDate() + i);
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
    out.push(byDay.get(key) ?? { day: key, revenue: 0, orders: 0 });
  }
  return out;
}

async function salesByDay(days: number) {
  const db = await getDb();
  const day = sql<string>`to_char(${orders.createdAt}, 'YYYY-MM-DD')`;
  const rows = await db
    .select({ day, revenue: sql<number>`coalesce(sum(${orders.totalCents}),0)::int`, orders: sql<number>`count(*)::int` })
    .from(orders)
    .where(and(notCancelled, gte(orders.createdAt, daysAgo(days))))
    .groupBy(day)
    .orderBy(day);
  return fillDays(rows, days);
}

export async function getDashboard(lowStockThreshold: number) {
  const db = await getDb();
  const [[totals], [today], [counts], recent, lowStock, sales, unread] = await Promise.all([
    db
      .select({ revenue: sql<number>`coalesce(sum(${orders.totalCents}),0)::int`, orders: sql<number>`count(*)::int` })
      .from(orders)
      .where(notCancelled),
    db
      .select({ revenue: sql<number>`coalesce(sum(${orders.totalCents}),0)::int`, orders: sql<number>`count(*)::int` })
      .from(orders)
      .where(and(notCancelled, gte(orders.createdAt, daysAgo(1)))),
    db
      .select({
        customers: sql<number>`(select count(*)::int from ${customers})`,
        products: sql<number>`(select count(*)::int from ${products} where ${col(products.isActive)})`,
        pending: sql<number>`(select count(*)::int from ${orders} where ${col(orders.status)} = 'pending')`,
      })
      .from(sql`(select 1) as one`),
    db.select().from(orders).orderBy(desc(orders.createdAt)).limit(6),
    db
      .select({ id: products.id, name: products.name, stock: products.stock, imageUrl: products.imageUrl })
      .from(products)
      .where(and(eq(products.isActive, true), lte(products.stock, lowStockThreshold)))
      .orderBy(asc(products.stock))
      .limit(6),
    salesByDay(14),
    db.select({ count: sql<number>`count(*)::int` }).from(contactMessages).where(eq(contactMessages.isRead, false)),
  ]);
  return { totals, today, counts, recent, lowStock, sales, unreadMessages: unread[0].count };
}

export async function listOrders(f: { q?: string; status?: string; page?: string }) {
  const db = await getDb();
  const where: SQL[] = [];
  if (f.status && (ORDER_STATUSES as readonly string[]).includes(f.status)) where.push(eq(orders.status, f.status as OrderStatus));
  const q = f.q?.trim().slice(0, 80);
  if (q) {
    const like = escapeLike(q);
    where.push(or(ilike(orders.number, like), ilike(orders.customerName, like), ilike(orders.phone, like), ilike(orders.city, like))!);
  }
  const condition = where.length ? and(...where) : undefined;
  const page = pageOf(f.page);
  const [rows, [{ total }], statusCounts] = await Promise.all([
    db
      .select({
        order: orders,
        itemCount: sql<number>`(select coalesce(sum(${col(orderItems.quantity)}),0)::int from ${orderItems} where ${col(orderItems.orderId)} = ${col(orders.id)})`,
      })
      .from(orders)
      .where(condition)
      .orderBy(desc(orders.createdAt))
      .limit(ADMIN_PAGE_SIZE)
      .offset((page - 1) * ADMIN_PAGE_SIZE),
    db.select({ total: sql<number>`count(*)::int` }).from(orders).where(condition),
    db.select({ status: orders.status, count: sql<number>`count(*)::int` }).from(orders).groupBy(orders.status),
  ]);
  return {
    rows,
    total,
    page,
    pages: Math.max(1, Math.ceil(total / ADMIN_PAGE_SIZE)),
    statusCounts: Object.fromEntries(statusCounts.map((s) => [s.status, s.count])) as Partial<Record<OrderStatus, number>>,
  };
}

export async function getOrder(id: number) {
  const db = await getDb();
  const [order] = await db.select().from(orders).where(eq(orders.id, id));
  if (!order) return null;
  const items = await db
    .select({ item: orderItems, slug: products.slug, icon: products.icon, imageUrl: products.imageUrl })
    .from(orderItems)
    .leftJoin(products, eq(orderItems.productId, products.id))
    .where(eq(orderItems.orderId, id))
    .orderBy(asc(orderItems.id));
  return { order, items };
}

export async function listProducts(f: { q?: string; category?: string; status?: string }) {
  const db = await getDb();
  const where: SQL[] = [];
  const q = f.q?.trim().slice(0, 80);
  if (q) where.push(ilike(products.name, escapeLike(q)));
  const cat = Number.parseInt(f.category ?? "", 10);
  if (Number.isFinite(cat)) where.push(eq(products.categoryId, cat));
  if (f.status === "active") where.push(eq(products.isActive, true));
  if (f.status === "hidden") where.push(eq(products.isActive, false));
  return db
    .select({
      product: products,
      categoryName: categories.name,
      sold: sql<number>`(select coalesce(sum(${col(orderItems.quantity)}),0)::int from ${orderItems} join ${orders} on ${col(orders.id)} = ${col(orderItems.orderId)} where ${col(orderItems.productId)} = ${col(products.id)} and ${col(orders.status)} <> 'cancelled')`,
    })
    .from(products)
    .leftJoin(categories, eq(products.categoryId, categories.id))
    .where(where.length ? and(...where) : undefined)
    .orderBy(desc(products.createdAt), desc(products.id));
}

export async function getProduct(id: number) {
  const db = await getDb();
  const [p] = await db.select().from(products).where(eq(products.id, id));
  return p ?? null;
}

export async function listCategoriesWithCounts() {
  const db = await getDb();
  return db
    .select({
      category: categories,
      productCount: sql<number>`(select count(*)::int from ${products} where ${col(products.categoryId)} = ${col(categories.id)})`,
    })
    .from(categories)
    .orderBy(asc(categories.name));
}

export async function listCustomers(f: { q?: string; page?: string }) {
  const db = await getDb();
  const q = f.q?.trim().slice(0, 80);
  const condition = q
    ? or(ilike(customers.name, escapeLike(q)), ilike(customers.phone, escapeLike(q)), ilike(customers.email, escapeLike(q)))
    : undefined;
  const page = pageOf(f.page);
  const [rows, [{ total }]] = await Promise.all([
    db
      .select({
        customer: customers,
        orderCount: sql<number>`(select count(*)::int from ${orders} where ${col(orders.customerId)} = ${col(customers.id)})`,
        spent: sql<number>`(select coalesce(sum(${col(orders.totalCents)}),0)::int from ${orders} where ${col(orders.customerId)} = ${col(customers.id)} and ${col(orders.status)} <> 'cancelled')`,
        lastOrder: sql<Date | null>`(select max(${col(orders.createdAt)}) from ${orders} where ${col(orders.customerId)} = ${col(customers.id)})`,
      })
      .from(customers)
      .where(condition)
      .orderBy(desc(customers.createdAt))
      .limit(ADMIN_PAGE_SIZE)
      .offset((page - 1) * ADMIN_PAGE_SIZE),
    db.select({ total: sql<number>`count(*)::int` }).from(customers).where(condition),
  ]);
  return { rows, total, page, pages: Math.max(1, Math.ceil(total / ADMIN_PAGE_SIZE)) };
}

export async function getCustomer(id: number) {
  const db = await getDb();
  const [customer] = await db.select().from(customers).where(eq(customers.id, id));
  if (!customer) return null;
  const history = await db.select().from(orders).where(eq(orders.customerId, id)).orderBy(desc(orders.createdAt));
  return { customer, history };
}

export async function getReport(days: number) {
  const db = await getDb();
  const since = daysAgo(days);
  const inRange = and(notCancelled, gte(orders.createdAt, since));
  const [[summary], sales, topProducts, byCategory, byStatus, byCity] = await Promise.all([
    db
      .select({
        revenue: sql<number>`coalesce(sum(${orders.totalCents}),0)::int`,
        orders: sql<number>`count(*)::int`,
        customers: sql<number>`count(distinct ${orders.customerId})::int`,
      })
      .from(orders)
      .where(inRange),
    salesByDay(days),
    db
      .select({
        name: orderItems.productName,
        units: sql<number>`sum(${orderItems.quantity})::int`,
        revenue: sql<number>`sum(${orderItems.quantity} * ${orderItems.unitCents})::int`,
      })
      .from(orderItems)
      .innerJoin(orders, eq(orders.id, orderItems.orderId))
      .where(inRange)
      .groupBy(orderItems.productName)
      .orderBy(desc(sql`sum(${orderItems.quantity} * ${orderItems.unitCents})`))
      .limit(10),
    db
      .select({
        name: sql<string>`coalesce(${categories.name}, 'Uncategorised')`,
        revenue: sql<number>`sum(${orderItems.quantity} * ${orderItems.unitCents})::int`,
      })
      .from(orderItems)
      .innerJoin(orders, eq(orders.id, orderItems.orderId))
      .leftJoin(products, eq(products.id, orderItems.productId))
      .leftJoin(categories, eq(categories.id, products.categoryId))
      .where(inRange)
      .groupBy(categories.name)
      .orderBy(desc(sql`sum(${orderItems.quantity} * ${orderItems.unitCents})`)),
    db
      .select({ status: orders.status, count: sql<number>`count(*)::int` })
      .from(orders)
      .where(gte(orders.createdAt, since))
      .groupBy(orders.status),
    db
      .select({ city: orders.city, count: sql<number>`count(*)::int`, revenue: sql<number>`sum(${orders.totalCents})::int` })
      .from(orders)
      .where(inRange)
      .groupBy(orders.city)
      .orderBy(desc(sql`count(*)`))
      .limit(8),
  ]);
  return { summary, sales, topProducts, byCategory, byStatus, byCity };
}

export async function listMessages() {
  const db = await getDb();
  return db.select().from(contactMessages).orderBy(asc(contactMessages.isRead), desc(contactMessages.createdAt)).limit(200);
}

export async function listSubscribers() {
  const db = await getDb();
  return db.select().from(newsletterSubscribers).orderBy(desc(newsletterSubscribers.createdAt));
}

export async function unreadMessageCount() {
  const db = await getDb();
  const [{ count }] = await db.select({ count: sql<number>`count(*)::int` }).from(contactMessages).where(eq(contactMessages.isRead, false));
  return count;
}
