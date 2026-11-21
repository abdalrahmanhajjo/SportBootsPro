import { PageHeader } from "@/components/admin/bits";
import { PasswordForm, StoreSettingsForm } from "@/components/admin/SettingsForms";
import { centsToInput } from "@/lib/money";
import { getSettings } from "@/lib/settings";

export const metadata = { title: "Settings" };

export default async function SettingsPage() {
  const s = await getSettings();
  return (
    <>
      <PageHeader title="Settings" description="Store details, delivery pricing and your account" />
      <div className="grid gap-6 xl:grid-cols-[1fr_380px]">
        <section className="card p-6">
          <h2 className="mb-5 font-bold">Store</h2>
          <StoreSettingsForm
            initial={{
              storeName: s.storeName,
              tagline: s.tagline,
              currency: s.currency,
              shipping: centsToInput(s.shippingCents),
              freeShippingThreshold: centsToInput(s.freeShippingThresholdCents),
              lowStockThreshold: String(s.lowStockThreshold),
              whatsapp: s.whatsapp,
              phone: s.phone,
              email: s.email,
              address: s.address,
            }}
          />
        </section>
        <section className="card h-fit p-6">
          <h2 className="mb-5 font-bold">Change password</h2>
          <PasswordForm />
        </section>
      </div>
    </>
  );
}
