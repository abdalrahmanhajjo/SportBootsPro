import type { Metadata } from "next";
import { InfoPage } from "@/components/shop/InfoPage";

export const metadata: Metadata = { title: "FAQ", description: "Answers to common questions about orders, delivery and sizes." };

const faqs = [
  ["How do I pay?", "You pay cash on delivery. The courier collects the total shown on your order confirmation."],
  ["How long does delivery take?", "Most orders arrive within 1–3 working days anywhere in Lebanon. We contact you before delivery to confirm."],
  ["How much is delivery?", "Delivery is a flat fee and becomes free above the threshold shown in your cart."],
  ["What if the size doesn't fit?", "You can exchange unworn items for a different size within 14 days. See our Returns page."],
  ["Are your products authentic?", "Yes. We only source from trusted suppliers and inspect every pair before it ships."],
  ["Can I change or cancel my order?", "Yes — message us on WhatsApp with your order number before it ships and we'll update it."],
];

export default function FaqPage() {
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: faqs.map(([q, a]) => ({ "@type": "Question", name: q, acceptedAnswer: { "@type": "Answer", text: a } })),
  };
  return (
    <InfoPage eyebrow="Help" title="Frequently asked questions">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd).replace(/</g, "\\u003c") }} />
      <div className="mt-8 divide-y divide-neutral-200 border-y border-neutral-200">
        {faqs.map(([q, a]) => (
          <details key={q} className="group py-5">
            <summary className="flex cursor-pointer list-none items-center justify-between font-semibold">
              {q}
              <span className="text-xl text-neutral-400 transition group-open:rotate-45" aria-hidden>
                +
              </span>
            </summary>
            <p className="mt-3">{a}</p>
          </details>
        ))}
      </div>
    </InfoPage>
  );
}
