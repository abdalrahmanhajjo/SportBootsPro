import { getTableName, sql, type Column } from "drizzle-orm";

/**
 * Fully-qualified column reference ("table"."column") for use inside raw SQL subqueries.
 * Drizzle renders `${table.column}` inside select-field SQL without the table name, which makes
 * correlated subqueries ambiguous (e.g. `"category_id" = "id"` compares two columns of the SAME table).
 */
export function col(column: Column) {
  return sql.raw(`"${getTableName(column.table)}"."${column.name}"`);
}
