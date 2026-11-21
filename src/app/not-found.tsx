import Link from "next/link";

export default function NotFound() {
  return (
    <main className="flex flex-1 flex-col items-center justify-center px-6 py-24 text-center">
      <p className="display text-9xl text-brand-600">404</p>
      <h1 className="display mt-4 text-5xl">Page not found</h1>
      <p className="mt-2 text-neutral-500">The page you&apos;re looking for doesn&apos;t exist or was moved.</p>
      <div className="mt-8 flex gap-3">
        <Link href="/" className="btn-dark">
          Go home
        </Link>
        <Link href="/shop" className="btn-outline">
          Browse the shop
        </Link>
      </div>
    </main>
  );
}
