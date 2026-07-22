import { EditAuthorPage } from "@/features/authors/components/edit-author-page";

export default async function EditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <EditAuthorPage id={Number(id)} />;
}
