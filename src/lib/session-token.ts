import { SignJWT, jwtVerify } from "jose";

export const SESSION_COOKIE = "sbp_admin";
export const SESSION_MAX_AGE = 60 * 60 * 24 * 7; // 7 days

export type SessionPayload = { sub: string; email: string; name: string };

const DEV_SECRET = "dev-only-secret-change-me-in-production-0000";

/**
 * Signing key for admin sessions.
 * 1. AUTH_SECRET (recommended, at least 32 characters).
 * 2. Otherwise, in production, a key derived from the database URL — a secret only the server knows —
 *    so a fresh deploy works without extra setup. (Changing the DB password then signs admins out.)
 * 3. Otherwise (local development) a fixed dev key.
 */
async function secretKey(): Promise<Uint8Array> {
  const secret = process.env.AUTH_SECRET;
  if (secret && secret.length >= 32) return new TextEncoder().encode(secret);
  const dbUrl = process.env.DATABASE_URL || process.env.POSTGRES_URL;
  if (process.env.NODE_ENV === "production") {
    if (!dbUrl) throw new Error("Set AUTH_SECRET (32+ random characters) or DATABASE_URL.");
    const digest = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(`sportbootspro-session:${dbUrl}`));
    return new Uint8Array(digest);
  }
  return new TextEncoder().encode(DEV_SECRET);
}

export async function signSession(payload: SessionPayload): Promise<string> {
  return new SignJWT({ email: payload.email, name: payload.name })
    .setProtectedHeader({ alg: "HS256" })
    .setSubject(payload.sub)
    .setIssuedAt()
    .setExpirationTime(`${SESSION_MAX_AGE}s`)
    .sign(await secretKey());
}

export async function verifySession(token: string | undefined): Promise<SessionPayload | null> {
  if (!token) return null;
  try {
    const { payload } = await jwtVerify(token, await secretKey(), { algorithms: ["HS256"] });
    if (!payload.sub || typeof payload.email !== "string") return null;
    return { sub: payload.sub, email: payload.email, name: String(payload.name ?? "") };
  } catch {
    return null;
  }
}
