import type { ReactNode } from "react";

export function InfoPage({ eyebrow, title, intro, children }: { eyebrow: string; title: string; intro?: string; children: ReactNode }) {
  return (
    <div className="container-page max-w-3xl py-16">
      <p className="eyebrow">{eyebrow}</p>
      <h1 className="mt-2 display text-6xl sm:text-7xl">{title}</h1>
      {intro && <p className="mt-4 text-lg text-neutral-600">{intro}</p>}
      <div className="prose-page mt-6">{children}</div>
    </div>
  );
}
