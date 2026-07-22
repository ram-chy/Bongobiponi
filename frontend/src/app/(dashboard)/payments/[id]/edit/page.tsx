import { EditPaymentPage } from "@/features/payments/components/edit-payment-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <EditPaymentPage id={Number(id)} />;
}
