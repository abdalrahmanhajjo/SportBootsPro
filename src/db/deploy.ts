// Runs on every deploy (see vercel.json → "npm run vercel-build"), before `next build`.
// 1. Applies database migrations.
// 2. Adds the demo catalog and default settings the first time (safe to run again).
// 3. Creates the admin account from ADMIN_EMAIL + ADMIN_PASSWORD when they are set.
import { sql } from "drizzle-orm";
import { createDb, migratePostgres } from "./client";
import { categories } from "./schema";
import { ensureAdmin, seedDatabase } from "./seed-data";

async function main() {
  const url = process.env.DATABASE_URL || process.env.POSTGRES_URL;
  if (!url) {
    console.log("[deploy] No DATABASE_URL — skipping database setup.");
    return;
  }
  // Migrations need a direct (non-pooled) connection when one is available.
  const direct = process.env.DATABASE_URL_UNPOOLED || process.env.POSTGRES_URL_NON_POOLING || url;
  await migratePostgres(direct);
  console.log("[deploy] ✓ Migrations applied");

  const db = await createDb({ url, seed: false });
  const [{ count }] = await db.select({ count: sql<number>`count(*)::int` }).from(categories);

  const email = process.env.ADMIN_EMAIL?.trim();
  const password = process.env.ADMIN_PASSWORD;
  const admin = email && password && password.length >= 10 ? { email, password, name: process.env.ADMIN_NAME } : undefined;
  if (email && !admin) console.warn("[deploy] ADMIN_PASSWORD must be at least 10 characters — admin not created.");

  if (count === 0) {
    await seedDatabase(db);
    console.log("[deploy] ✓ Demo catalog and settings added");
  }
  if (admin) {
    await ensureAdmin(db, admin);
    console.log(`[deploy] ✓ Admin account ready: ${admin.email}`);
  }
  if (!admin) console.log("[deploy] Tip: set ADMIN_EMAIL and ADMIN_PASSWORD, then redeploy, to create your admin login.");
}

main()
  .then(() => process.exit(0))
  .catch((err) => {
    console.error("[deploy] Database setup failed:", err);
    process.exit(1);
  });
