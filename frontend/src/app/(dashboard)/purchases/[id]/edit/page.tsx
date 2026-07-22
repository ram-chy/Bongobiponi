import { EditPurchasePage } from "@/features/purchases/components/edit-purchase-page";

export default async function EditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <EditPurchasePage id={Number(id)} />;
}
