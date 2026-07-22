import { CustomerEditPage } from "@/features/customers/components/customer-edit-page";

export default async function EditCustomerPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <CustomerEditPage id={Number(id)} />;
}
