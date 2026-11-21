import type { Metadata } from "next";
import { Mail, MapPin, MessageCircle, Phone } from "lucide-react";
import { ContactForm } from "@/components/shop/ContactForm";
import { getSettings } from "@/lib/settings";

export const metadata: Metadata = { title: "Contact", description: "Get in touch with SportBootsPro." };

export default async function ContactPage() {
  const s = await getSettings();
  const items = [
    s.whatsapp && { icon: MessageCircle, label: "WhatsApp", value: `+${s.whatsapp}`, href: `https://wa.me/${s.whatsapp}` },
    s.phone && { icon: Phone, label: "Phone", value: s.phone, href: `tel:${s.phone.replace(/\s/g, "")}` },
    s.email && { icon: Mail, label: "Email", value: s.email, href: `mailto:${s.email}` },
    s.address && { icon: MapPin, label: "Location", value: s.address },
  ].filter(Boolean) as { icon: typeof Phone; label: string; value: string; href?: string }[];

  return (
    <div className="container-page py-16">
      <p className="eyebrow">Contact</p>
      <h1 className="mt-2 text-4xl font-black tracking-tight sm:text-5xl">We&apos;re here to help</h1>
      <p className="mt-4 max-w-2xl text-lg text-neutral-600">
        Questions about sizes, stock or an order? Message us — WhatsApp is the fastest way to reach us.
      </p>
      <div className="mt-12 grid gap-10 lg:grid-cols-[1fr_1.4fr]">
        <ul className="space-y-4">
          {items.map((i) => (
            <li key={i.label} className="card flex items-center gap-4 p-5">
              <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <i.icon className="h-5 w-5" />
              </span>
              <div>
                <p className="text-sm text-neutral-500">{i.label}</p>
                {i.href ? (
                  <a href={i.href} className="font-semibold hover:text-brand-700" target={i.href.startsWith("http") ? "_blank" : undefined} rel="noopener noreferrer">
                    {i.value}
                  </a>
                ) : (
                  <p className="font-semibold">{i.value}</p>
                )}
              </div>
            </li>
          ))}
        </ul>
        <div className="card p-6 sm:p-8">
          <ContactForm />
        </div>
      </div>
    </div>
  );
}
