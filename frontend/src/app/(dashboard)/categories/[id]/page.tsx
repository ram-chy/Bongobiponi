import { ViewCategoryPage } from "@/features/categories/components/view-category-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewCategoryPage id={Number(id)} />;
}
