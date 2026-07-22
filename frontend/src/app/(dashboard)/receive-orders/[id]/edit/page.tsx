import { EditReceiveOrderPage } from "@/features/receive-orders/components/edit-receive-order-page";

export default async function EditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <EditReceiveOrderPage id={Number(id)} />;
}
