import type { Metadata } from "next";
import Link from "next/link";
import { Feather, Footprints, Gauge, ShieldCheck, Wind, Zap } from "lucide-react";

export const metadata: Metadata = { title: "Technology", description: "The materials and design behind performance footwear." };

const tech = [
  { icon: Feather, title: "Lightweight uppers", text: "Knit and synthetic uppers cut weight while hugging your foot for a precise touch." },
  { icon: Zap, title: "Energy-return foam", text: "Modern midsole foams give back energy on every stride and soften impact on hard surfaces." },
  { icon: Footprints, title: "Traction patterns", text: "Stud layouts and outsole patterns designed for firm ground, artificial turf, indoor courts and trails." },
  { icon: Gauge, title: "Carbon plates", text: "Stiff plates in racing shoes and boots help you push off faster and stay efficient when tired." },
  { icon: Wind, title: "Breathability", text: "Engineered mesh zones move air where your foot heats up most — important in Lebanese summers." },
  { icon: ShieldCheck, title: "Support & stability", text: "Heel counters, lockdown lacing and wide platforms keep you stable through cuts and landings." },
];

export default function TechnologyPage() {
  return (
    <div className="container-page py-16">
      <div className="max-w-3xl">
        <p className="eyebrow">Technology</p>
        <h1 className="mt-2 text-4xl font-black tracking-tight sm:text-5xl">What makes a performance shoe</h1>
        <p className="mt-4 text-lg text-neutral-600">
          Every product in our store is chosen for how it performs. Here is what to look for when you pick your next pair.
        </p>
      </div>
      <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {tech.map((t) => (
          <div key={t.title} className="card p-6">
            <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
              <t.icon className="h-6 w-6" />
            </span>
            <h2 className="mt-4 text-lg font-bold">{t.title}</h2>
            <p className="mt-2 text-sm leading-relaxed text-neutral-600">{t.text}</p>
          </div>
        ))}
      </div>
      <div className="mt-12">
        <Link href="/shop" className="btn-primary px-7 py-3">
          Shop performance footwear
        </Link>
      </div>
    </div>
  );
}
