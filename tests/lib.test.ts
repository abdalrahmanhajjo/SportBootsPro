import { afterEach, describe, expect, it, vi } from "vitest";
import { addItem, cartCount, MAX_QTY_PER_LINE, parseCart, removeItem, setItemQty } from "@/lib/cart";
import { formatMoney, parseMoney } from "@/lib/money";
import { parseSettings, shippingFor } from "@/lib/settings-defaults";
import { slugify } from "@/lib/slug";
import { checkoutSchema, productSchema } from "@/lib/validation";
import { signSession, verifySession } from "@/lib/session-token";

describe("money", () => {
  it("parses and formats safely", () => {
    expect(parseMoney("129.99")).toBe(12999);
    expect(parseMoney("$1,200")).toBe(120000);
    expect(parseMoney("12.345")).toBeNull();
    expect(parseMoney("-5")).toBeNull();
    expect(parseMoney("abc")).toBeNull();
    expect(formatMoney(12999)).toBe("$129.99");
  });
});

describe("cart cookie", () => {
  it("ignores tampered or malformed cookies", () => {
    expect(parseCart("not json")).toEqual([]);
    expect(parseCart(JSON.stringify([{ p: 1, s: "42", q: 999 }]))).toEqual([]);
    expect(parseCart(JSON.stringify([{ p: -1, s: "42", q: 1 }]))).toEqual([]);
    expect(parseCart(JSON.stringify([{ p: 1, s: "42", q: 2 }]))).toEqual([{ p: 1, s: "42", q: 2 }]);
  });

  it("merges, caps, updates and removes lines", () => {
    let items = addItem([], 1, "42", 2);
    items = addItem(items, 1, "42", 50);
    expect(items).toEqual([{ p: 1, s: "42", q: MAX_QTY_PER_LINE }]);
    items = addItem(items, 1, "43", 1);
    expect(cartCount(items)).toBe(MAX_QTY_PER_LINE + 1);
    items = setItemQty(items, 1, "43", 0);
    expect(items).toHaveLength(1);
    expect(removeItem(items, 1, "42")).toEqual([]);
  });
});

describe("settings & shipping", () => {
  it("falls back to defaults on bad values", () => {
    const s = parseSettings([{ key: "shippingCents", value: "-3" }, { key: "storeName", value: "X" }, { key: "evil", value: "1" }]);
    expect(s.shippingCents).toBe(500);
    expect(s.storeName).toBe("X");
    expect("evil" in s).toBe(false);
  });
  it("computes shipping", () => {
    const s = { shippingCents: 500, freeShippingThresholdCents: 10000 };
    expect(shippingFor(0, s)).toBe(0);
    expect(shippingFor(9999, s)).toBe(500);
    expect(shippingFor(10000, s)).toBe(0);
  });
});

describe("slugify", () => {
  it("makes URL-safe slugs", () => {
    expect(slugify("Predator Edge+")).toBe("predator-edge-plus");
    expect(slugify("  Café Été!! ")).toBe("cafe-ete");
  });
});

describe("validation", () => {
  it("validates checkout input", () => {
    expect(checkoutSchema.safeParse({ name: "A", phone: "x", email: "bad", city: "", address: "" }).success).toBe(false);
    const ok = checkoutSchema.safeParse({ name: "Rami H", phone: "+961 70 123 456", email: "", city: "Beirut", address: "Hamra st 12" });
    expect(ok.success).toBe(true);
  });

  it("validates products and blocks javascript: image URLs", () => {
    const base = {
      name: "Boot", categoryId: "1", description: "", features: "", price: "100", compareAt: "", imageUrl: "",
      icon: "⚽", badge: "", sizes: ["42"], stock: "5", isFeatured: false, isActive: true,
    };
    expect(productSchema.safeParse(base).success).toBe(true);
    expect(productSchema.safeParse({ ...base, imageUrl: "javascript:alert(1)" }).success).toBe(false);
    expect(productSchema.safeParse({ ...base, compareAt: "90" }).success).toBe(false);
    expect(productSchema.safeParse({ ...base, price: "0" }).success).toBe(false);
    expect(productSchema.safeParse({ ...base, stock: "-1" }).success).toBe(false);
    expect(productSchema.safeParse({ ...base, sizes: ["99"] }).success).toBe(false);
  });
});

describe("admin session token", () => {
  it("round-trips and rejects tampering", async () => {
    const token = await signSession({ sub: "1", email: "a@b.co", name: "A" });
    expect(await verifySession(token)).toEqual({ sub: "1", email: "a@b.co", name: "A" });
    expect(await verifySession(token.slice(0, -2) + "xx")).toBeNull();
    expect(await verifySession(undefined)).toBeNull();
  });
});

describe("session key in production", () => {
  afterEach(() => vi.unstubAllEnvs());

  it("derives a key from the database URL when AUTH_SECRET is missing", async () => {
    vi.stubEnv("NODE_ENV", "production");
    vi.stubEnv("AUTH_SECRET", "");
    vi.stubEnv("DATABASE_URL", "postgres://user:pass@db.example/neondb");
    const token = await signSession({ sub: "1", email: "a@b.co", name: "A" });
    expect(await verifySession(token)).toMatchObject({ sub: "1" });
    vi.stubEnv("DATABASE_URL", "postgres://user:other@db.example/neondb");
    expect(await verifySession(token)).toBeNull(); // a different database means a different key
  });

  it("refuses to sign without any secret", async () => {
    vi.stubEnv("NODE_ENV", "production");
    vi.stubEnv("AUTH_SECRET", "");
    vi.stubEnv("DATABASE_URL", "");
    vi.stubEnv("POSTGRES_URL", "");
    await expect(signSession({ sub: "1", email: "a@b.co", name: "A" })).rejects.toThrow(/AUTH_SECRET/);
  });
});
