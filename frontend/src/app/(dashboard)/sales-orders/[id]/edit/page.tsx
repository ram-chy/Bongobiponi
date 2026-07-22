import { EditSalesOrderPage } from "@/features/sales-orders/components/edit-sales-order-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <EditSalesOrderPage id={Number(id)} />;
}
