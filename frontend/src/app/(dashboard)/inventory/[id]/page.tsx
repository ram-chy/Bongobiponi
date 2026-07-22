import { BookInventoryPage } from "@/features/inventory/components/book-inventory-page";

export default async function Page({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <BookInventoryPage bookId={Number(id)} />;
}
