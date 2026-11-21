import { deleteCategory } from "@/app/actions/admin";
import { Notice, PageHeader } from "@/components/admin/bits";
import { CategoryForm } from "@/components/admin/CategoryForm";
import { ConfirmButton } from "@/components/admin/ConfirmButton";
import { listCategoriesWithCounts } from "@/lib/admin-queries";

export const metadata = { title: "Categories" };

export default async function CategoriesPage({ searchParams }: PageProps<"/admin/categories">) {
  const sp = await searchParams;
  const cats = await listCategoriesWithCounts();
  return (
    <>
      <PageHeader title="Categories" description="Group products by sport. Customers filter the shop by these." />
      {sp.error === "in-use" && <Notice tone="error">This category still has products. Move or delete them first.</Notice>}
      <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
        <ul className="space-y-4">
          {cats.map(({ category: c, productCount }) => (
            <li key={c.id} className="card p-5">
              <div className="mb-3 flex items-center justify-between">
                <p className="text-sm text-neutral-500">
                  /{c.slug} · {productCount} product{productCount === 1 ? "" : "s"}
                </p>
                {productCount === 0 && (
                  <form action={deleteCategory}>
                    <input type="hidden" name="id" value={c.id} />
                    <ConfirmButton message={`Delete the "${c.name}" category?`} className="btn-danger btn-sm">
                      Delete
                    </ConfirmButton>
                  </form>
                )}
              </div>
              <CategoryForm category={c} />
            </li>
          ))}
        </ul>
        <aside className="card h-fit p-5">
          <h2 className="mb-4 font-bold">New category</h2>
          <CategoryForm />
        </aside>
      </div>
    </>
  );
}
