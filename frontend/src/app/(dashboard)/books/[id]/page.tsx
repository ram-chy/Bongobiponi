import { ViewBookPage } from "@/features/books/components/view-book-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewBookPage id={Number(id)} />;
}
