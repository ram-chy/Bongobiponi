import { ViewAuthorPage } from "@/features/authors/components/view-author-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewAuthorPage id={Number(id)} />;
}
