import { ViewPurchasePage } from "@/features/purchases/components/view-purchase-page";

export default async function Page({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <ViewPurchasePage id={Number(id)} />;
}
