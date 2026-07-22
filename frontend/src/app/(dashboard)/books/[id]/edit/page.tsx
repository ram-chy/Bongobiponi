import { EditBookPage } from "@/features/books/components/edit-book-page";

export default async function EditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <EditBookPage id={Number(id)} />;
}
