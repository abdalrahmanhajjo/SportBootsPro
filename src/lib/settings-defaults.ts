export const DEFAULT_SETTINGS = {
  storeName: "SportBootsPro",
  tagline: "Lebanon's home for performance sportswear and athletic footwear.",
  currency: "USD",
  shippingCents: 500,
  freeShippingThresholdCents: 15000,
  whatsapp: "96176536462",
  phone: "+961 76 536 462",
  email: "info@sportbootspro.com",
  address: "Tripoli, Lebanon",
  lowStockThreshold: 10,
};

export type StoreSettings = typeof DEFAULT_SETTINGS;

const NUMERIC_KEYS = new Set<keyof StoreSettings>(["shippingCents", "freeShippingThresholdCents", "lowStockThreshold"]);

export function parseSettings(rows: { key: string; value: string }[]): StoreSettings {
  const out: StoreSettings = { ...DEFAULT_SETTINGS };
  for (const { key, value } of rows) {
    if (!(key in DEFAULT_SETTINGS)) continue;
    const k = key as keyof StoreSettings;
    if (NUMERIC_KEYS.has(k)) {
      const n = Number(value);
      if (Number.isFinite(n) && n >= 0) (out[k] as number) = Math.round(n);
    } else {
      (out[k] as string) = value;
    }
  }
  return out;
}

export function shippingFor(subtotalCents: number, s: Pick<StoreSettings, "shippingCents" | "freeShippingThresholdCents">) {
  if (subtotalCents <= 0) return 0;
  if (s.freeShippingThresholdCents > 0 && subtotalCents >= s.freeShippingThresholdCents) return 0;
  return s.shippingCents;
}
