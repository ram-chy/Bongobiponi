import { ViewSupplierPage } from "@/features/suppliers/components/view-supplier-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewSupplierPage id={Number(id)} />;
}
