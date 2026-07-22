import { ViewOrderPage } from "@/features/orders/components/view-order-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewOrderPage id={Number(id)} />;
}
