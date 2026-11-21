import { mkdirSync } from "node:fs";
import path from "node:path";
import { drizzle as drizzlePg, type NodePgDatabase } from "drizzle-orm/node-postgres";
import * as schema from "./schema";

export type DB = NodePgDatabase<typeof schema>;

const migrationsFolder = path.join(process.cwd(), "drizzle");

/**
 * - DATABASE_URL set   → real PostgreSQL (Neon, Supabase, Vercel Postgres, local…).
 * - DATABASE_URL unset → PGlite, an embedded PostgreSQL stored in ./.data/pglite.
 *   It is migrated and seeded automatically so `npm run dev` works with zero setup.
 */
export async function createDb(options: { url?: string; pgliteDir?: string; seed?: boolean } = {}): Promise<DB> {
  // Vercel's Neon integration sets DATABASE_URL (and POSTGRES_URL).
  const url = options.url ?? (process.env.DATABASE_URL || process.env.POSTGRES_URL);

  if (url) {
    const { Pool } = await import("pg");
    const pool = new Pool({ connectionString: url, max: Number(process.env.DB_POOL_MAX ?? 5) });
    return drizzlePg(pool, { schema });
  }

  if (process.env.NODE_ENV === "production" && !process.env.ALLOW_PGLITE) {
    throw new Error("DATABASE_URL is required in production.");
  }

  const { PGlite } = await import("@electric-sql/pglite");
  const { drizzle: drizzleLite } = await import("drizzle-orm/pglite");
  const { migrate } = await import("drizzle-orm/pglite/migrator");

  const dir = options.pgliteDir ?? process.env.PGLITE_DIR ?? path.join(process.cwd(), ".data", "pglite");
  if (dir !== "memory://") mkdirSync(dir, { recursive: true });
  const client = dir === "memory://" ? new PGlite() : new PGlite(dir);
  const db = drizzleLite(client, { schema });
  await migrate(db, { migrationsFolder });

  if (options.seed !== false) {
    const { seedIfEmpty } = await import("./seed-data");
    await seedIfEmpty(db as unknown as DB);
  }
  return db as unknown as DB;
}

export async function migratePostgres(url: string) {
  const { Pool } = await import("pg");
  const { migrate } = await import("drizzle-orm/node-postgres/migrator");
  const pool = new Pool({ connectionString: url, max: 1 });
  try {
    await migrate(drizzlePg(pool, { schema }), { migrationsFolder });
  } finally {
    await pool.end();
  }
}
