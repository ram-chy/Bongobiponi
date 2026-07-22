import { EditDeliveryChallanPage } from "@/features/delivery-challans/components/edit-delivery-challan-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <EditDeliveryChallanPage id={Number(id)} />;
}
