import "server-only";
import { createDb, type DB } from "./client";

// One shared connection per server process (survives hot reload in dev).
const globalForDb = globalThis as unknown as { __sbpDb?: Promise<DB> };

export function getDb(): Promise<DB> {
  if (!globalForDb.__sbpDb) {
    globalForDb.__sbpDb = createDb().catch((err) => {
      globalForDb.__sbpDb = undefined;
      throw err;
    });
  }
  return globalForDb.__sbpDb;
}

export type { DB };
