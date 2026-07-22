import { ViewQuotationPage } from "@/features/quotations/components/view-quotation-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewQuotationPage id={Number(id)} />;
}
