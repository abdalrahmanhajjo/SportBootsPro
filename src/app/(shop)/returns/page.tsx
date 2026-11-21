import type { Metadata } from "next";
import { InfoPage } from "@/components/shop/InfoPage";

export const metadata: Metadata = { title: "Returns & exchanges", description: "Our 14-day exchange policy." };

export default function ReturnsPage() {
  return (
    <InfoPage eyebrow="Help" title="Returns & exchanges" intro="Wrong size? No problem — exchanges are easy.">
      <h2>Exchange policy</h2>
      <ul>
        <li>Exchange within <strong>14 days</strong> of delivery.</li>
        <li>Items must be unworn, clean and in the original box.</li>
        <li>Size exchanges are free; we cover the delivery of the new pair.</li>
      </ul>
      <h2>Refunds</h2>
      <p>
        If a product arrives damaged or is not what you ordered, contact us within 48 hours with your order number and a
        photo. We will replace it or refund you in full.
      </p>
      <h2>How to request an exchange</h2>
      <p>Send us your order number and the size you need on WhatsApp or through the contact form.</p>
    </InfoPage>
  );
}
