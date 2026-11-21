import "server-only";
import { and, asc, desc, eq, gte, ilike, lte, ne, or, sql, type SQL } from "drizzle-orm";
import { cookies } from "next/headers";
import { getDb } from "@/db";
import { col } from "./sql";
import { categories, products } from "@/db/schema";
import { CART_COOKIE, parseCart } from "./cart";
import { resolveCart } from "./orders";
import { getSettings } from "./settings";

export const PAGE_SIZE = 12;

export const SORTS = {
  newest: { label: "Newest", order: [desc(products.createdAt), desc(products.id)] },
  "price-asc": { label: "Price: low to high", order: [asc(products.priceCents), asc(products.id)] },
  "price-desc": { label: "Price: high to low", order: [desc(products.priceCents), asc(products.id)] },
  name: { label: "Name A–Z", order: [asc(products.name), asc(products.id)] },
} as const;
export type SortKey = keyof typeof SORTS;

export async function getCategories() {
  const db = await getDb();
  return db
    .select({
      id: categories.id,
      name: categories.name,
      slug: categories.slug,
      description: categories.description,
      icon: categories.icon,
      productCount: sql<number>`(select count(*)::int from ${products} where ${col(products.categoryId)} = ${col(categories.id)} and ${col(products.isActive)})`,
    })
    .from(categories)
    .orderBy(asc(categories.name));
}

const productCard = {
  id: products.id,
  name: products.name,
  slug: products.slug,
  priceCents: products.priceCents,
  compareAtCents: products.compareAtCents,
  imageUrl: products.imageUrl,
  icon: products.icon,
  badge: products.badge,
  stock: products.stock,
  categoryName: categories.name,
};
export type ProductCardData = {
  id: number;
  name: string;
  slug: string;
  priceCents: number;
  compareAtCents: number | null;
  imageUrl: string | null;
  icon: string;
  badge: string | null;
  stock: number;
  categoryName: string | null;
};

export async function getFeaturedProducts(limit = 8): Promise<ProductCardData[]> {
  const db = await getDb();
  return db
    .select(productCard)
    .from(products)
    .leftJoin(categories, eq(products.categoryId, categories.id))
    .where(and(eq(products.isActive, true), eq(products.isFeatured, true)))
    .orderBy(desc(products.createdAt), asc(products.id))
    .limit(limit);
}

export type ShopFilters = { q?: string; category?: string; sort?: string; min?: string; max?: string; page?: string };

export async function searchProducts(f: ShopFilters) {
  const db = await getDb();
  const where: SQL[] = [eq(products.isActive, true)];

  const q = f.q?.trim().slice(0, 80);
  if (q) {
    const like = `%${q.replace(/[%_\\]/g, (m) => `\\${m}`)}%`;
    where.push(or(ilike(products.name, like), ilike(products.description, like), ilike(categories.name, like))!);
  }
  if (f.category) where.push(eq(categories.slug, f.category));
  const min = Number(f.min);
  const max = Number(f.max);
  if (f.min && Number.isFinite(min) && min >= 0) where.push(gte(products.priceCents, Math.round(min * 100)));
  if (f.max && Number.isFinite(max) && max > 0) where.push(lte(products.priceCents, Math.round(max * 100)));

  const sort: SortKey = f.sort && f.sort in SORTS ? (f.sort as SortKey) : "newest";
  const page = Math.max(1, Math.min(1000, Number.parseInt(f.page ?? "1", 10) || 1));
  const condition = and(...where);

  const [rows, [{ total }]] = await Promise.all([
    db
      .select(productCard)
      .from(products)
      .leftJoin(categories, eq(products.categoryId, categories.id))
      .where(condition)
      .orderBy(...SORTS[sort].order)
      .limit(PAGE_SIZE)
      .offset((page - 1) * PAGE_SIZE),
    db
      .select({ total: sql<number>`count(*)::int` })
      .from(products)
      .leftJoin(categories, eq(products.categoryId, categories.id))
      .where(condition),
  ]);

  return { rows, total, page, pages: Math.max(1, Math.ceil(total / PAGE_SIZE)), sort };
}

export async function getProductBySlug(slug: string) {
  const db = await getDb();
  const [row] = await db
    .select({ product: products, category: categories })
    .from(products)
    .leftJoin(categories, eq(products.categoryId, categories.id))
    .where(and(eq(products.slug, slug), eq(products.isActive, true)))
    .limit(1);
  return row ?? null;
}

export async function getRelatedProducts(productId: number, categoryId: number | null, limit = 4) {
  if (categoryId == null) return [];
  const db = await getDb();
  return db
    .select(productCard)
    .from(products)
    .leftJoin(categories, eq(products.categoryId, categories.id))
    .where(and(eq(products.isActive, true), eq(products.categoryId, categoryId), ne(products.id, productId)))
    .orderBy(desc(products.isFeatured), asc(products.id))
    .limit(limit);
}

export async function readCartItems() {
  return parseCart((await cookies()).get(CART_COOKIE)?.value);
}

export async function getCart() {
  const [db, items, settings] = await Promise.all([getDb(), readCartItems(), getSettings()]);
  return resolveCart(db, items, settings);
}
