import "server-only";
import { cache } from "react";
import { getDb } from "@/db";
import { settings } from "@/db/schema";
import { parseSettings } from "./settings-defaults";

export const getSettings = cache(async () => {
  const db = await getDb();
  return parseSettings(await db.select().from(settings));
});
