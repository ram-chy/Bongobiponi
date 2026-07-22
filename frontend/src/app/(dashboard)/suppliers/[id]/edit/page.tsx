import { EditSupplierPage } from "@/features/suppliers/components/edit-supplier-page";

export default async function EditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <EditSupplierPage id={Number(id)} />;
}
