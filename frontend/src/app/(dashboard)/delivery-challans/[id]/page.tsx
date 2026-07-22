import { ViewDeliveryChallanPage } from "@/features/delivery-challans/components/view-delivery-challan-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewDeliveryChallanPage id={Number(id)} />;
}
