import bcrypt from "bcryptjs";
import { sql } from "drizzle-orm";
import type { DB } from "./client";
import { admins, categories, products, settings } from "./schema";
import { unsplash } from "../lib/photos";
import { DEFAULT_SETTINGS } from "../lib/settings-defaults";
import { slugify } from "../lib/slug";

const ALL_SIZES = ["38", "39", "40", "41", "42", "43", "44"];

const CATEGORY_DATA = [
  { name: "Football", description: "Boots for firm ground, soft ground and turf", icon: "⚽", photo: "1570498839593-e565b39455fc" },
  { name: "Running", description: "Road shoes from daily miles to race day", icon: "🏃", photo: "1590333748338-d629e4564ad9" },
  { name: "Basketball", description: "Court shoes with grip, bounce and support", icon: "🏀", photo: "1787461361732-b147a1a2d886" },
  { name: "Tennis", description: "Stable court shoes for every surface", icon: "🎾", photo: "1738165758746-5bfad0dd55ec" },
  { name: "Training", description: "Gym and cross-training shoes", icon: "💪", photo: "1517963628607-235ccdd5476c" },
  { name: "Trail Running", description: "Grip and protection for mountain trails", icon: "🥾", photo: "1580058572462-98e2c0e0e2f0" },
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
  photo: string;
};

const PRODUCT_DATA: SeedProduct[] = [
  { name: "Strike Elite FG", category: "Football", price: 299.99, photo: "1768696082282-df2812a4f7d8", description: "Our fastest firm-ground boot. A thin knit upper gives a barefoot touch on the ball, and a stiff plate snaps you into your first step.", features: "Knit upper, Carbon fibre plate, Firm-ground studs, All-weather touch", badge: "New", stock: 45, featured: true },
  { name: "Apex Control FG", category: "Football", price: 279.99, photo: "1511886929837-354d827aae26", description: "Built for playmakers. Grip zones on the upper add spin and control on passes and shots.", features: "Grip-zone upper, Knit collar, Cushioned insole, Firm-ground studs", stock: 38, featured: true },
  { name: "Velocity SG", category: "Football", price: 229.99, compareAt: 259.99, photo: "1511426463457-0571e247d816", description: "A featherweight boot for wet, soft pitches, with mixed studs for grip when the grass is heavy.", features: "Featherweight upper, Mixed studs, Speed outsole", badge: "Sale", stock: 52 },
  { name: "Blaze Turf TF", category: "Football", price: 149.99, photo: "1529900748604-07564a03e7a6", description: "Rubber-studded turf shoe for 5-a-side and artificial grass — comfortable from the first game.", features: "Rubber turf outsole, Padded heel, Durable toe", stock: 29, sizes: ["39", "40", "41", "42", "43"] },
  { name: "Speed Runner X", category: "Running", price: 189.99, photo: "1542291026-7eec264c27ff", description: "A light, snappy racer for 5K to half-marathon personal bests.", features: "Carbon plate, Responsive foam, Breathable knit", stock: 67, featured: true },
  { name: "Cloud Glide 3", category: "Running", price: 179.99, photo: "1600185365926-3a2ce3cdb9eb", description: "Maximum cushioning for long runs and all-day comfort.", features: "Energy-return foam, Sock-fit upper, Rubber outsole", badge: "New", stock: 89, featured: true },
  { name: "Tempo Daily Trainer", category: "Running", price: 169.99, photo: "1600185365483-26d7a4cc7519", description: "A versatile everyday trainer that still feels quick on tempo days.", features: "Dual-density foam, Engineered mesh, Reflective details", stock: 76 },
  { name: "Marathon Carbon Pro", category: "Running", price: 229.99, photo: "1606107557195-0e29a4b5b4aa", description: "Our marathon racer: a rocker shape and carbon plate keep you efficient when the legs get tired.", features: "Rocker geometry, Carbon plate, Race-fit upper", badge: "Limited", stock: 6, featured: true },
  { name: "Court Master Elite", category: "Basketball", price: 249.99, photo: "1631984564919-1f6b2313a71c", description: "High-top support and full-length cushioning for explosive players.", features: "Full-length cushioning, High-top collar, Lockdown laces", badge: "Limited", stock: 34, featured: true },
  { name: "Skyline Pro 12", category: "Basketball", price: 219.99, photo: "1786379582247-258594160bf4", description: "A responsive all-rounder for guards and wings.", features: "Responsive foam, Herringbone traction, Ankle support", stock: 56 },
  { name: "Rim Rocker 4", category: "Basketball", price: 139.99, compareAt: 159.99, photo: "1605348532760-6753d2c43329", description: "Grippy, versatile court shoe at a great price.", features: "Air-cushioned heel, Multidirectional traction, Midfoot strap", badge: "Sale", stock: 71 },
  { name: "Ace Serve Pro", category: "Tennis", price: 179.99, photo: "1595341888016-a392ef81b7de", description: "Stable and quick for hard-court rallies and fast lateral movement.", features: "Gel cushioning, Durable outsole, Stable heel", stock: 43, featured: true },
  { name: "Baseline Speed", category: "Tennis", price: 149.99, photo: "1599586120429-48281b6f0ece", description: "Light court shoe for players who live at the baseline.", features: "Light foam, Supportive midsole, Toe guard", stock: 62 },
  { name: "Iron Grip Trainer", category: "Training", price: 139.99, photo: "1605408499391-6368c628ef42", description: "For lifting, jumping and sprints in one session.", features: "Woven upper, Flat stable heel, Wide toe box", badge: "New", stock: 88, featured: true },
  { name: "Metro Lift 8", category: "Training", price: 129.99, photo: "1591311630200-ffa9120a540f", description: "A stable training platform for heavy days.", features: "Flat heel, Rope-climb wrap, Breathable mesh", stock: 4 },
  { name: "Ridge Runner GTX", category: "Trail Running", price: 229.99, photo: "1792955104796-31d15ec8c28f", description: "Waterproof trail shoe for wet and muddy mountain runs.", features: "Sticky rubber outsole, Waterproof upper, Rock plate", stock: 37 },
  { name: "Summit Grip 5", category: "Trail Running", price: 139.99, compareAt: 159.99, photo: "1711466067057-d1bd10183924", description: "Maximum-cushion trail runner for long days in the mountains.", features: "Rocker sole, Deep lugs, Wide platform", badge: "Sale", stock: 51, featured: true },
];

const toCents = (n: number) => Math.round(n * 100);

export async function seedDatabase(db: DB, admin?: { email: string; password: string; name?: string }) {
  const insertedCats = await db
    .insert(categories)
    .values(
      CATEGORY_DATA.map(({ photo, ...c }) => ({ ...c, slug: slugify(c.name), imageUrl: unsplash(photo) })),
    )
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
        imageUrl: unsplash(p.photo),
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

  if (admin) await ensureAdmin(db, admin);
}

/** Creates the admin account if that email has none yet. Never changes an existing password. */
export async function ensureAdmin(db: DB, admin: { email: string; password: string; name?: string }) {
  await db
    .insert(admins)
    .values({
      name: admin.name ?? "Store Admin",
      email: admin.email.toLowerCase(),
      passwordHash: await bcrypt.hash(admin.password, 12),
    })
    .onConflictDoNothing();
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
