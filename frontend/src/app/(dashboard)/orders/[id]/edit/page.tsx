import { EditOrderPage } from "@/features/orders/components/edit-order-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <EditOrderPage id={Number(id)} />;
}
