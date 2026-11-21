// Usage: DATABASE_URL=postgres://... ADMIN_EMAIL=you@x.com ADMIN_PASSWORD='long-password' npm run db:seed
// Inserts the demo catalog, default settings and your admin account. Safe to run twice.
import { createDb } from "./client";
import { seedDatabase } from "./seed-data";

async function main() {
  const email = process.env.ADMIN_EMAIL;
  const password = process.env.ADMIN_PASSWORD;
  if (!email || !password || password.length < 10) {
    throw new Error("Set ADMIN_EMAIL and ADMIN_PASSWORD (at least 10 characters) before seeding.");
  }
  const db = await createDb({ seed: false });
  await seedDatabase(db, { email, password, name: process.env.ADMIN_NAME });
  console.log(`✓ Seeded catalog and admin ${email}`);
  process.exit(0);
}

main().catch((err) => {
  console.error(err instanceof Error ? err.message : err);
  process.exit(1);
});
