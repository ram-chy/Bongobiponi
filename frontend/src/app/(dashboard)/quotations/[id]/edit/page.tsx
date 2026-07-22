import { EditQuotationPage } from "@/features/quotations/components/edit-quotation-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <EditQuotationPage id={Number(id)} />;
}
