import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { PageHeader } from "@/components/admin/bits";
import { ProductForm } from "@/components/admin/ProductForm";
import { listCategoriesWithCounts } from "@/lib/admin-queries";

export const metadata = { title: "New product" };

export default async function NewProductPage() {
  const cats = await listCategoriesWithCounts();
  return (
    <>
      <Link href="/admin/products" className="mb-4 inline-flex items-center gap-1 text-sm text-neutral-500 hover:text-neutral-900">
        <ArrowLeft className="h-4 w-4" /> Products
      </Link>
      <PageHeader title="New product" />
      <ProductForm
        categories={cats.map(({ category: c }) => ({ id: c.id, name: c.name, icon: c.icon }))}
        initial={{
          name: "",
          categoryId: "",
          description: "",
          features: "",
          price: "",
          compareAt: "",
          imageUrl: "",
          icon: "👟",
          badge: "",
          sizes: ["38", "39", "40", "41", "42", "43", "44"],
          stock: 0,
          isFeatured: false,
          isActive: true,
        }}
      />
    </>
  );
}
