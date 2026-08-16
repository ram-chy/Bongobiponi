import { CreatePurchasePage } from "@/features/purchases/components/create-purchase-page";

export default async function Page({
  searchParams,
}: {
  searchParams: Promise<{ order_id?: string }>;
}) {
  const { order_id } = await searchParams;
  return <CreatePurchasePage orderId={order_id ? Number(order_id) : undefined} />;
}
