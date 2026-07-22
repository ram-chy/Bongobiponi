import { ViewInvoicePage } from "@/features/invoices/components/view-invoice-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewInvoicePage id={Number(id)} />;
}
