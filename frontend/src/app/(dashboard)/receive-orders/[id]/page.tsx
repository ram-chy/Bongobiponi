import { ViewReceiveOrderPage } from "@/features/receive-orders/components/view-receive-order-page";

export default async function Page({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <ViewReceiveOrderPage id={Number(id)} />;
}
