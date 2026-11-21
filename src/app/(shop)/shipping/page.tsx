import type { Metadata } from "next";
import { InfoPage } from "@/components/shop/InfoPage";
import { formatMoney } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata: Metadata = { title: "Shipping", description: "Delivery times and costs across Lebanon." };

export default async function ShippingPage() {
  const s = await getSettings();
  return (
    <InfoPage eyebrow="Help" title="Shipping & delivery" intro="We deliver to every area in Lebanon.">
      <h2>Costs</h2>
      <ul>
        <li>Standard delivery: <strong>{formatMoney(s.shippingCents, s.currency)}</strong></li>
        <li>Free delivery on orders over <strong>{formatMoney(s.freeShippingThresholdCents, s.currency)}</strong></li>
      </ul>
      <h2>Delivery times</h2>
      <ul>
        <li>Tripoli & North: 1–2 working days</li>
        <li>Beirut & Mount Lebanon: 1–3 working days</li>
        <li>South & Bekaa: 2–4 working days</li>
      </ul>
      <h2>How it works</h2>
      <p>
        After you place an order we call or WhatsApp you to confirm. The courier delivers to your address and you pay
        cash on delivery. Please keep your phone reachable on the delivery day.
      </p>
    </InfoPage>
  );
}
