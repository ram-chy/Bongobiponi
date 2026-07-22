import { ViewSalesOrderPage } from "@/features/sales-orders/components/view-sales-order-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewSalesOrderPage id={Number(id)} />;
}
