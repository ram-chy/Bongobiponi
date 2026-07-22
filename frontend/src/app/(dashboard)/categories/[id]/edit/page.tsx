import { EditCategoryPage } from "@/features/categories/components/edit-category-page";

export default async function EditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <EditCategoryPage id={Number(id)} />;
}
