// Usage: DATABASE_URL=postgres://... npm run db:migrate
import { migratePostgres } from "./client";

const url = process.env.DATABASE_URL;
if (!url) {
  console.error("DATABASE_URL is not set. (Local PGlite databases are migrated automatically by `npm run dev`.)");
  process.exit(1);
}

migratePostgres(url)
  .then(() => console.log("✓ Migrations applied"))
  .catch((err) => {
    console.error("Migration failed:", err);
    process.exit(1);
  });
