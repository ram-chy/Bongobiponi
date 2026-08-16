import { ViewPurchasePage } from "@/features/purchases/components/view-purchase-page";

export default async function Page({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ from_order?: string }>;
}) {
  const { id } = await params;
  const { from_order } = await searchParams;
  return <ViewPurchasePage id={Number(id)} fromOrderId={from_order ? Number(from_order) : undefined} />;
}
