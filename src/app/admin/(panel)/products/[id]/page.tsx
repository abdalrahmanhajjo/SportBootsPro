import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft, ExternalLink } from "lucide-react";
import { deleteProduct } from "@/app/actions/admin";
import { PageHeader } from "@/components/admin/bits";
import { ConfirmButton } from "@/components/admin/ConfirmButton";
import { ProductForm } from "@/components/admin/ProductForm";
import { getProduct, listCategoriesWithCounts } from "@/lib/admin-queries";
import { centsToInput } from "@/lib/money";

export const metadata = { title: "Edit product" };

export default async function EditProductPage({ params }: PageProps<"/admin/products/[id]">) {
  const id = Number.parseInt((await params).id, 10);
  if (!Number.isFinite(id)) notFound();
  const [p, cats] = await Promise.all([getProduct(id), listCategoriesWithCounts()]);
  if (!p) notFound();

  return (
    <>
      <Link href="/admin/products" className="mb-4 inline-flex items-center gap-1 text-sm text-neutral-500 hover:text-neutral-900">
        <ArrowLeft className="h-4 w-4" /> Products
      </Link>
      <PageHeader
        title={p.name}
        actions={
          <>
            {p.isActive && (
              <Link href={`/products/${p.slug}`} target="_blank" className="btn-outline">
                <ExternalLink className="h-4 w-4" /> View in store
              </Link>
            )}
            <form action={deleteProduct}>
              <input type="hidden" name="id" value={p.id} />
              <ConfirmButton message="Delete this product? If it has orders it will be hidden instead.">Delete</ConfirmButton>
            </form>
          </>
        }
      />
      <ProductForm
        categories={cats.map(({ category: c }) => ({ id: c.id, name: c.name, icon: c.icon }))}
        initial={{
          id: p.id,
          name: p.name,
          categoryId: p.categoryId ?? "",
          description: p.description,
          features: p.features,
          price: centsToInput(p.priceCents),
          compareAt: centsToInput(p.compareAtCents),
          imageUrl: p.imageUrl ?? "",
          icon: p.icon,
          badge: p.badge ?? "",
          sizes: p.sizes,
          stock: p.stock,
          isFeatured: p.isFeatured,
          isActive: p.isActive,
        }}
      />
    </>
  );
}
