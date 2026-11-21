import { z } from "zod";
import { parseMoney } from "./money";

const trimmed = (max: number) => z.string().trim().max(max);
const required = (label: string, max: number) => trimmed(max).min(1, `${label} is required`);

export const SIZE_OPTIONS = ["36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46"] as const;

export const checkoutSchema = z.object({
  name: required("Full name", 120).min(2, "Please enter your full name"),
  phone: required("Phone", 30).regex(/^\+?[0-9\s-]{7,20}$/, "Please enter a valid phone number"),
  email: z.union([z.literal(""), z.email("Please enter a valid email").max(160)]),
  city: required("City", 80),
  address: required("Address", 500).min(5, "Please enter your full address"),
  notes: trimmed(500).optional().default(""),
});
export type CheckoutInput = z.infer<typeof checkoutSchema>;

export const contactSchema = z.object({
  name: required("Name", 120),
  email: z.email("Please enter a valid email").max(160),
  subject: required("Subject", 200),
  message: required("Message", 5000).min(10, "Message is too short"),
});

export const newsletterSchema = z.object({ email: z.email("Please enter a valid email").max(160) });

export const loginSchema = z.object({
  email: z.email("Enter a valid email"),
  password: z.string().min(1, "Password is required").max(200),
});

const moneyField = (label: string) =>
  z.string().transform((v, ctx) => {
    const cents = parseMoney(v);
    if (cents == null) {
      ctx.addIssue({ code: "custom", message: `${label} must be a valid amount` });
      return z.NEVER;
    }
    return cents;
  });

const optionalMoneyField = (label: string) =>
  z.string().transform((v, ctx) => {
    if (v.trim() === "") return null;
    const cents = parseMoney(v);
    if (cents == null) {
      ctx.addIssue({ code: "custom", message: `${label} must be a valid amount` });
      return z.NEVER;
    }
    return cents;
  });

export const productSchema = z
  .object({
    name: required("Name", 150),
    categoryId: z.coerce.number().int().positive("Choose a category"),
    description: trimmed(5000).default(""),
    features: trimmed(2000).default(""),
    price: moneyField("Price"),
    compareAt: optionalMoneyField("Compare-at price"),
    imageUrl: z.union([z.literal(""), z.url("Image URL must be a valid http(s) URL").max(1000)]),
    icon: trimmed(16).default("👟"),
    badge: trimmed(30).default(""),
    sizes: z.array(z.enum(SIZE_OPTIONS)).default([]),
    stock: z.coerce.number().int("Stock must be a whole number").min(0, "Stock cannot be negative").max(100000),
    isFeatured: z.boolean(),
    isActive: z.boolean(),
  })
  .refine((p) => p.price > 0, { message: "Price must be greater than 0", path: ["price"] })
  .refine((p) => p.compareAt == null || p.compareAt > p.price, {
    message: "Compare-at price must be higher than the price",
    path: ["compareAt"],
  })
  .refine((p) => p.imageUrl === "" || /^https?:\/\//i.test(p.imageUrl), {
    message: "Image URL must start with http:// or https://",
    path: ["imageUrl"],
  });

export const categorySchema = z.object({
  name: required("Name", 80),
  description: trimmed(500).default(""),
  icon: trimmed(16).default("👟"),
  imageUrl: z
    .union([z.literal(""), z.url("Image URL must be a valid http(s) URL").max(1000)])
    .default("")
    .refine((v) => v === "" || /^https?:\/\//i.test(v), { message: "Image URL must start with http:// or https://" }),
});

export const settingsSchema = z.object({
  storeName: required("Store name", 80),
  tagline: trimmed(200),
  currency: z.enum(["USD", "LBP", "EUR"]),
  shipping: moneyField("Delivery fee"),
  freeShippingThreshold: moneyField("Free-delivery threshold"),
  whatsapp: trimmed(20).regex(/^[0-9]*$/, "WhatsApp number must contain digits only (e.g. 96176536462)"),
  phone: trimmed(30),
  email: z.union([z.literal(""), z.email("Enter a valid email")]),
  address: trimmed(200),
  lowStockThreshold: z.coerce.number().int().min(0).max(1000),
});

export const passwordChangeSchema = z
  .object({
    current: z.string().min(1, "Enter your current password"),
    next: z.string().min(10, "New password must be at least 10 characters").max(200),
    confirm: z.string(),
  })
  .refine((v) => v.next === v.confirm, { message: "Passwords do not match", path: ["confirm"] });

/** Flatten zod issues into { field: firstMessage } for forms. */
export function fieldErrors(error: z.ZodError): Record<string, string> {
  const out: Record<string, string> = {};
  for (const issue of error.issues) {
    const key = String(issue.path[0] ?? "form");
    if (!out[key]) out[key] = issue.message;
  }
  return out;
}
