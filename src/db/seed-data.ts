import bcrypt from "bcryptjs";
import { sql } from "drizzle-orm";
import type { DB } from "./client";
import { admins, categories, products, settings } from "./schema";
import { DEFAULT_SETTINGS } from "../lib/settings-defaults";
import { slugify } from "../lib/slug";

const ALL_SIZES = ["38", "39", "40", "41", "42", "43", "44"];

const CATEGORY_DATA = [
  { name: "Football", description: "Professional football boots for grass and turf", icon: "⚽" },
  { name: "Running", description: "Lightweight running shoes for all distances", icon: "🏃" },
  { name: "Basketball", description: "High-performance basketball shoes", icon: "🏀" },
  { name: "Tennis", description: "Court shoes for all surfaces", icon: "🎾" },
  { name: "Training", description: "Versatile training and gym shoes", icon: "💪" },
  { name: "Trail Running", description: "Rugged shoes for off-road adventures", icon: "🥾" },
];

type SeedProduct = {
  name: string;
  category: string;
  price: number;
  compareAt?: number;
  description: string;
  features: string;
  badge?: string;
  stock: number;
  featured?: boolean;
  sizes?: string[];
};

const PRODUCT_DATA: SeedProduct[] = [
  { name: "Elite Strike Pro", category: "Football", price: 299.99, description: "Professional football boots with an advanced stud configuration for explosive acceleration.", features: "Knit upper, Carbon fibre soleplate, All-weather touch", badge: "New", stock: 45, featured: true },
  { name: "Predator Edge+", category: "Football", price: 279.99, description: "Control the game with precision grip zones and a locked-in fit.", features: "Control zones, Knit collar, Cushioned midsole", stock: 38, featured: true },
  { name: "Mercurial Vapor", category: "Football", price: 229.99, compareAt: 259.99, description: "Built for speed on the pitch with a featherweight upper.", features: "Lightweight upper, Traction zone, Tri-star studs", badge: "Sale", stock: 52 },
  { name: "Phantom GT2", category: "Football", price: 239.99, description: "Precision control football boots for playmakers.", features: "Grip-knit upper, Asymmetric lacing, AG soleplate", stock: 29, sizes: ["39", "40", "41", "42", "43"] },
  { name: "Speed Runner X", category: "Running", price: 189.99, description: "Lightweight racing shoes built for personal bests.", features: "Carbon plate, Responsive foam, Breathable knit", stock: 67, featured: true },
  { name: "UltraBoost 23", category: "Running", price: 179.99, description: "Maximum cushioning for long runs and all-day comfort.", features: "Energy-return midsole, Sock-fit upper, Rubber outsole", badge: "New", stock: 89, featured: true },
  { name: "Pegasus Turbo", category: "Running", price: 169.99, description: "A daily trainer with a race-day feel.", features: "Foam midsole, Air pods, Engineered mesh", stock: 76 },
  { name: "Endorphin Pro 3", category: "Running", price: 229.99, description: "Marathon racing shoes with a propulsive ride.", features: "Rocker geometry, Carbon plate, Form-fit upper", badge: "Limited", stock: 6, featured: true },
  { name: "Court Master Elite", category: "Basketball", price: 249.99, description: "Professional basketball performance with full-length cushioning.", features: "Full-length air unit, Woven upper, Lockdown straps", badge: "Limited", stock: 34, featured: true },
  { name: "Signature Pro 12", category: "Basketball", price: 219.99, description: "Signature-series basketball shoes for every position.", features: "Responsive cushioning, Herringbone traction, Ankle support", stock: 56 },
  { name: "Freak 4", category: "Basketball", price: 139.99, compareAt: 159.99, description: "Versatile court performance at a great price.", features: "Zoom cushioning, Multidirectional traction, Midfoot strap", badge: "Sale", stock: 71 },
  { name: "Ace Serve Pro", category: "Tennis", price: 179.99, description: "Professional tennis court shoes for fast lateral movement.", features: "Gel cushioning, Durable outsole, Stable heel", stock: 43, featured: true },
  { name: "Court Speed FF", category: "Tennis", price: 149.99, description: "Fast court coverage with a light, stable platform.", features: "Light foam, Supportive midsole, Toe guard", stock: 62 },
  { name: "CrossFit Nano X3", category: "Training", price: 139.99, description: "Versatile training shoes for lifting, jumping and sprints.", features: "Woven upper, Energy foam, Wide toe box", badge: "New", stock: 88, featured: true },
  { name: "Metcon 8", category: "Training", price: 129.99, description: "A stable training platform for heavy days.", features: "Flat heel, Rope-climb wrap, Breathable mesh", stock: 4 },
  { name: "Trail Blazer X", category: "Trail Running", price: 229.99, description: "Conquer any terrain with grip and protection.", features: "Sticky rubber outsole, Waterproof upper, Rock plate", stock: 37 },
  { name: "Speedgoat 5", category: "Trail Running", price: 139.99, compareAt: 159.99, description: "Maximum-cushion trail runner for long mountain days.", features: "Rocker sole, Lugged grip, Wide platform", badge: "Sale", stock: 51, featured: true },
];

const toCents = (n: number) => Math.round(n * 100);

export async function seedDatabase(db: DB, admin?: { email: string; password: string; name?: string }) {
  const insertedCats = await db
    .insert(categories)
    .values(CATEGORY_DATA.map((c) => ({ ...c, slug: slugify(c.name) })))
    .onConflictDoNothing()
    .returning();
  const catRows = insertedCats.length ? insertedCats : await db.select().from(categories);
  const catByName = new Map(catRows.map((c) => [c.name, c]));

  await db
    .insert(products)
    .values(
      PRODUCT_DATA.map((p) => ({
        name: p.name,
        slug: slugify(p.name),
        categoryId: catByName.get(p.category)?.id ?? null,
        description: p.description,
        features: p.features,
        priceCents: toCents(p.price),
        compareAtCents: p.compareAt ? toCents(p.compareAt) : null,
        icon: catByName.get(p.category)?.icon ?? "👟",
        badge: p.badge ?? null,
        sizes: p.sizes ?? ALL_SIZES,
        stock: p.stock,
        isFeatured: p.featured ?? false,
      })),
    )
    .onConflictDoNothing();

  await db
    .insert(settings)
    .values(Object.entries(DEFAULT_SETTINGS).map(([key, value]) => ({ key, value: String(value) })))
    .onConflictDoNothing();

  if (admin) {
    await db
      .insert(admins)
      .values({
        name: admin.name ?? "Store Admin",
        email: admin.email.toLowerCase(),
        passwordHash: await bcrypt.hash(admin.password, 12),
      })
      .onConflictDoNothing();
  }
}

/** Development helper: seed a fresh local database with demo data + a demo admin. */
export async function seedIfEmpty(db: DB) {
  const [{ count }] = await db.select({ count: sql<number>`count(*)::int` }).from(categories);
  if (count > 0) return;
  await seedDatabase(db, {
    email: process.env.ADMIN_EMAIL ?? "admin@sportbootspro.com",
    password: process.env.ADMIN_PASSWORD ?? "admin12345",
  });
}
