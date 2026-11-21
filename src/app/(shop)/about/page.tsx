import type { Metadata } from "next";
import Link from "next/link";
import { InfoPage } from "@/components/shop/InfoPage";

export const metadata: Metadata = { title: "About us", description: "Who we are and why we started SportBootsPro." };

export default function AboutPage() {
  return (
    <InfoPage
      eyebrow="About us"
      title="Made for players in Lebanon"
      intro="SportBootsPro started with a simple idea: athletes in Lebanon deserve the same choice of performance footwear as anywhere else — without the hassle."
    >
      <h2>What we do</h2>
      <p>
        We bring together football boots, running shoes, basketball, tennis, training and trail footwear in one place, and
        deliver them to your door anywhere in Lebanon. You pay cash when your order arrives.
      </p>
      <h2>What we stand for</h2>
      <ul>
        <li><strong>Authentic products</strong> from trusted suppliers.</li>
        <li><strong>Honest prices</strong> shown upfront, with delivery costs before you order.</li>
        <li><strong>Real support</strong> on WhatsApp from people who play sport themselves.</li>
        <li><strong>Easy exchanges</strong> when the size isn&apos;t right.</li>
      </ul>
      <h2>Get in touch</h2>
      <p>
        Questions about a product or an order? <Link href="/contact" className="font-semibold text-brand-700 underline">Contact us</Link> —
        we usually reply within one business day.
      </p>
    </InfoPage>
  );
}
